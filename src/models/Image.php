<?php
    namespace unique\yii2modelimage\models;

    use unique\yii2modelimage\components\ImageResizer;
    use unique\yii2modelimage\exceptions\ModelImageException;
    use unique\yii2modelimage\ModelImageModule;
    use unique\yii2modelimage\models\data\File;
    use unique\yii2modelimage\models\data\ImageDimensions;
    use Imagine\Filter\Basic\Autorotate;
    use Imagine\Image\ImageInterface;
    use Yii;
    use yii\web\UploadedFile;

    /**
     * This is the model class for table "images".
     *
     * @property int $id
     * @property string $group
     * @property string $unique_id
     * @property string $name
     * @property string $extension
     * @property bool $is_temp
     * @property int $uploaded_at
     * @property string|null $mime_type
     * @property int|null $width Original image width
     * @property int|null $height Original image height
     * @property int|null $size Original image size in bytes.
     *
     * @property ImageVersion[] $versions
     */
    class Image extends \yii\db\ActiveRecord {

        /**
         * @inheritdoc
         */
        public static function tableName() {

            return 'images';
        }

        /**
         * @inheritdoc
         */
        public function rules() {

            return [
                [ [ 'name' ], 'required' ],
                [ [ 'name' ], 'string', 'max' => 255 ],
                [ [ 'extension' ], 'string', 'max' => 10 ],
            ];
        }

        /**
         * @inheritdoc
         */
        public function attributeLabels() {

            return [
                'id' => 'ID',
                'name' => 'Name',
                'extension' => 'Extension',
                'is_temp' => 'Is temporary upload',
            ];
        }

        /**
         * Get query for [[ImageVersion]]
         * @return \yii\db\ActiveQuery
         */
        public function getVersions() {

            return $this->hasMany( ImageVersion::class, [ 'image_id' => 'id' ] );
        }

        /**
         * Generates all the image versions.
         * If no image is given from which to generate the versions, it will be opened from {@see Image::getImagePath()}.
         * Returns weather the versions were successfully generated.
         *
         * @param ImageDimensions[] $versions
         * @param ImageInterface|null $image
         * @return bool
         * @throws \Exception
         */
        public function generateVersions( $versions, ?ImageInterface $image = null ) {

            if ( $image === null ) {

                $image = \yii\imagine\Image::getImagine()->open( $this->getImagePath() );
            }

            foreach ( $versions as $size ) {

                $version = ImageVersion::createByImageDimensions( $image, $this, $size );
                if ( $version->getErrors() ) {

                    $this->deleteVersions( $versions );

                    $errors = [];
                    foreach ( $version->getFirstErrors() as $error ) {

                        $errors[] = $size->version . ': ' . $error;
                    }

                    $this->addError( 'name', $errors );
                    return false;
                }
            }

            return true;
        }

        /**
         * Copies image to the storage directory and creates given image versions.
         * @param string|null $group
         * @param File $file - Data about file
         * @param bool $is_temp - Is the given file a temp file (Will be automatically deleted after some time)
         * @return static
         * @throws \yii\db\StaleObjectException
         */
        public static function createFromFile( string|null $group, File $file, bool $is_temp = false ): static {

            $module = ModelImageModule::getInstance();
            $image = \yii\imagine\Image::getImagine()->open( $file->path );
            $size = $image->getSize();

            $filter = new Autorotate();
            $filter->apply( $image );

            $image_model = new static();
            $image_model->group = $group;
            $image_model->is_temp = $is_temp;
            $image_model->unique_id = $image_model->generateRandomKey();
            $image_model->setNameAndExtension( $file->name );
            $image_model->mime_type = $file->mime_type;
            $image_model->width = $size->getWidth();
            $image_model->height = $size->getHeight();
            $image_model->size = $file->size;

            if ( $file->uploaded_at !== null ) {

                $image_model->uploaded_at = $file->uploaded_at;
            }

            if ( !$image_model->save() ) {

                return $image_model;
            }

            try {

                $image->save( $image_model->getImagePath() );
            } catch ( \Throwable $error ) {

                $image_model->delete();
                $image_model->addError( 'name', $error->getMessage() );

                return $image_model;
            }

            if ( $group ) {

                if ( !$image_model->generateVersions( $module->group_versions[ $group ] ) ) {

                    $image_model->delete();
                }
            }

            return $image_model;
        }

        /**
         * @param string|null $group
         * @param UploadedFile $uploaded_file
         * @param bool $is_temp - Is this a temporary file?
         * @return static
         * @throws \yii\db\StaleObjectException
         */
        public static function createFromUploadedFile( ?string $group, UploadedFile $uploaded_file, bool $is_temp = false ): static {

            $file = new File(
                $uploaded_file->tempName,
                $uploaded_file->name,
                $uploaded_file->type,
                $uploaded_file->size
            );

            return static::createFromFile( $group, $file, $is_temp );
        }

        /**
         * Regenerate original image with the given dimensions.
         * This will overwrite existing file and update this model to DB.
         * @param ImageDimensions $dimensions
         * @return void
         * @throws \Exception
         */
        public function regenerateOriginal( ImageDimensions $dimensions ): void {

            $file_name = $this->getImagePath();

            $img = \yii\imagine\Image::getImagine()->open( $file_name );
            $resizer = new ImageResizer( $img );
            $img_resized = $resizer->generate( $dimensions );

            $img_resized->save(
                $file_name,
                [ 'quality' => $dimensions->quality ]
            );

            $resized_size = $img_resized->getSize();
            $this->width = $resized_size->getWidth();
            $this->height = $resized_size->getHeight();
            $this->size = filesize( $file_name );
            $this->save( false );
        }

        /**
         * Find and return a particular version of the Image. If version is not found, null is returned.
         * @param string $version - Version Name.
         * @return ImageVersion|null
         */
        public function getVersion( string $version ): ImageVersion|null {

            foreach ( $this->versions as $v => $model ) {

                if ( $version === $v ) {

                    return $model;
                }
            }

            return null;
        }

        /**
         * Returns a path or URL to this image.
         * @param string|array|null $version - Version of the image, or a list of versions (will return first existing), or null if it is an original.
         * @param bool $as_url - Should a path or a url be returned?
         * @return string|null
         * @throws \Exception
         */
        public function getImagePath( $version = null, bool $as_url = false ): ?string {

            /**
             * @var ModelImageModule $module
             */
            $module = ModelImageModule::getInstance();

            $base_path = $module->getImagePath( false, false ) . '/' . $this->id;
            if ( $version ) {

                foreach ( ( is_array( $version ) ? $version : [ $version ] ) as $v ) {

                    if ( file_exists( $base_path . '_' . $v . '.' . $this->extension ) ) {

                        return $module->getImagePath( $as_url, false ) . ( $as_url ? DIRECTORY_SEPARATOR : '/' ) .
                            $this->id . '_' . $this->unique_id . '_' . $v . '.' . $this->extension;
                    }
                }
            }

            return $module->getImagePath( $as_url, false ) . ( $as_url ? DIRECTORY_SEPARATOR : '/' ) .
                $this->id . '_' . $this->unique_id . ( $version && !is_array( $version ) ? '_' . $version : '' ) . '.' . $this->extension;
        }

        public function generateRandomKey() {

            return Yii::$app->security->generateRandomString();
        }

        /**
         * From the provided file name retrieves it's basename and extension and sets them accordingly.
         * @param string $name - File name (without a path)
         */
        public function setNameAndExtension( string $name ) {

            $name = explode( '.', $name );

            $this->extension = array_pop( $name );
            $this->name = implode( '.', $name );
        }

        public function beforeSave( $insert ) {

            if ( $insert ) {

                if ( $this->uploaded_at === null ) {

                    $this->uploaded_at = time();
                }
            }

            return parent::beforeSave( $insert );
        }

        /**
         * Removes either all saved image versions, when $version === true or the given versions when $versions is an array.
         * @param ImageDimensions[]|bool $versions
         * @throws \yii\db\StaleObjectException
         */
        public function deleteVersions( array|bool $versions = true ) {

            /**
             * @var ImageVersion[] $existing_versions
             */
            $existing_versions = $this->getVersions()->indexBy( 'version' )->all();
            if ( $versions === true ) {

                $versions = $existing_versions;
            }

            foreach ( $versions ?? [] as $version ) {

                $version = $existing_versions[ $version->version ] ?? null;
                if ( $version !== null ) {

                    $version->delete();
                }
            }
        }

        /**
         * Deletes an image version only from the disk.
         * @return bool
         */
        public function deleteImage() {

            $filename = $this->getImagePath();
            if ( !file_exists( $filename ) ) {

                return false;
            }

            return unlink( $filename );
        }

        /**
         * @inheritdoc
         */
        public function beforeDelete() {

            if ( !parent::beforeDelete() ) {

                return false;
            }

            $this->deleteVersions();
            $this->deleteImage();

            return true;
        }

        public function extraFields() {

            return [ 'versions' ];
        }

        /**
         * Rotates current image by the given angle. Also regenerates all the image versions.
         * @param int $angle
         * @return bool
         * @throws \yii\db\StaleObjectException
         */
        public function rotate( int $angle ): bool {

            $module = ModelImageModule::getInstance();
            if ( $module === null ) {

                throw new ModelImageException( 'ModelImageModule was not initialized.' );
            }

            $versions = $module->group_versions[ $this->group ] ?? [];

            $this->deleteVersions();

            $path = $this->getImagePath( null, false );
            $image = \yii\imagine\Image::getImagine()->open( $path );
            $image
                ->rotate( $angle )
                ->save( $path );

            return $this->generateVersions( $versions, $image );
        }

        public function fields() {

            return array_merge( parent::fields(), [
                'url' => fn( self $model ) => $model->getImagePath( null, true ),
            ] );
        }
    }
