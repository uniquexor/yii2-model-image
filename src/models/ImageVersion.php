<?php
    namespace unique\yii2modelimage\models;

    use unique\yii2modelimage\components\ImageResizer;
    use unique\yii2modelimage\ModelImageModule;
    use unique\yii2modelimage\models\data\ImageDimensions;
    use Imagine\Image\ImageInterface;
    use Yii;

    /**
     * This is the model class for table "image_versions".
     *
     * @property int $id
     * @property int $image_id
     * @property string $version
     * @property int $width
     * @property int $height
     * @property int $size
     *
     * @property Image $image
     */
    class ImageVersion extends \yii\db\ActiveRecord {

        protected string|null $image_src = null;
        protected string|null $mime_type = null;

        /**
         * @inheritdoc
         */
        public static function tableName() {

            return 'image_versions';
        }

        /**
         * @inheritdoc
         */
        public function rules() {

            return [
                [ [ 'image_id', 'version', 'width', 'height', 'size' ], 'required' ],
                [ [ 'image_id', 'width', 'height', 'size' ], 'integer' ],
                [ [ 'version' ], 'string', 'max' => 45 ],
                [ [ 'image_id' ], 'exist', 'skipOnError' => true, 'targetClass' => ModelImageModule::getInstance()->image_class, 'targetAttribute' => [ 'image_id' => 'id' ] ],
            ];
        }

        /**
         * @inheritdoc
         */
        public function attributeLabels() {

            return [
                'id' => 'ID',
                'image_id' => 'Image ID',
                'version' => 'Version',
                'width' => 'Width',
                'height' => 'Height',
                'size' => 'Size',
            ];
        }

        /**
         * Gets query for [[Image]].
         *
         * @return \yii\db\ActiveQuery
         */
        public function getImage() {

            return $this->hasOne( ModelImageModule::getInstance()->image_class, [ 'id' => 'image_id' ] );
        }

        /**
         * @inheritdoc
         */
        public function afterDelete() {

            parent::afterDelete();
            $this->deleteImage();
        }

        public static function createByImageDimensions( ImageInterface $image, Image $image_model, ImageDimensions $size ) {

            $image_version = new self();
            $image_version->image_id = $image_model->id;
            $image_version->version = $size->version;

            $file_name = $image_model->getImagePath( $size->version );
            try {

                $resizer = new ImageResizer( $image );
                $thumb = $resizer->generate( $size );

                $resized_size = $thumb->getSize();
                $image_version->width = $resized_size->getWidth();
                $image_version->height = $resized_size->getHeight();

                $thumb->save(
                    $file_name,
                    [ 'quality' => $size->quality ]
                );

                $image_version->size = filesize( $file_name );
                if ( !$image_version->save() ) {

                    $image_version->deleteImage();
                }
            } catch ( \Throwable $error ) {

                if ( !$image_version->isNewRecord ) {

                    $image_version->delete();
                } else {

                    $image_version->deleteImage();
                }

                $image_model->addError( 'id', 'Could not create a `' . $size->version . '` of the image: ' . $error->getMessage() );
            }

            return $image_version;
        }

        /**
         * Returns a path or URL to this image's version.
         * @param bool $as_url - Should a path or a url be returned?
         * @return string|null
         * @throws \Exception
         */
        public function getImagePath( bool $as_url = false ): ?string {

            if ( $this->image_src ) {

                return $as_url
                    ? Yii::$app->urlManager->baseUrl . str_replace( '\\', '/', $this->image_src )
                    : Yii::getAlias( '@webroot' ) . '/' . $this->image_src;
            } else {

                return $this->image->getImagePath( $this->version, $as_url );
            }
        }

        /**
         * Deletes an image version only from the disk.
         * @return bool
         * @throws \Exception
         */
        public function deleteImage() {

            $filename = $this->getImagePath();
            if ( !file_exists( $filename ) ) {

                return false;
            }

            return unlink( $filename );
        }

        /**
         * Sets a custom image source. When set, this file will be used to generate url/path, instead of the default logic.
         * @param string|null $relative_path - Relative path from app's web root directory to file, i.e.: 'img/image.jpg'
         */
        public function setImageSrc( string|null $relative_path ) {

            $this->image_src = $relative_path;
        }

        /**
         * Sets a custom mime type to use.
         * @param string $mime_type
         */
        public function setMimeType( string $mime_type ) {

            $this->mime_type = $mime_type;
        }

        /**
         * Returns image's mime type
         * @return string
         */
        public function getMimeType(): string {

            if ( $this->mime_type ) {

                return $this->mime_type;
            }

            return $this->image->mime_type;
        }

        /**
         * @inheritdoc
         */
        public function fields() {

            return array_merge( parent::fields(), [
                'url' => fn( self $model ) => $model->getImagePath( true ),
            ] );
        }
    }
