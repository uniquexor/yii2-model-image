<?php
    namespace unique\yii2modelimage;

    use unique\yii2modelimage\models\Image;
    use Yii;
    use yii\base\BootstrapInterface;
    use yii\base\Module;
    use yii\base\StaticInstanceTrait;

    class ModelImageModule extends Module implements BootstrapInterface {

        public string $images_path = '@app/www/images';

        public string $image_class = Image::class;

        public array $group_versions = [];

        private static ModelImageModule|null $_instance = null;

        /**
         * Returns a path to where an images are stored. If $return_as_url is true, returns a URL.
         * @param bool $return_as_url - If true, will return a URL.
         * @param bool $create_if_not_exists - Should the image path be created in case it does not exist?
         * @return string
         * @throws \Exception
         */
        public function getImagePath( bool $return_as_url = false, bool $create_if_not_exists = true ) {

            $path = Yii::getAlias( $this->images_path ) . DIRECTORY_SEPARATOR;

            if ( !file_exists( $path ) ) {

                if ( $create_if_not_exists && !mkdir( $path ) ) {

                    throw new \Exception( 'Unable to create Image directory.' );
                } else {

                    throw new \Exception( 'Image directory does not exist.' );
                }
            }

            $path = realpath( $path );

            if ( $return_as_url ) {

                $base_url = realpath( Yii::getAlias( '@webroot' ) );
                if ( !str_starts_with( $path, $base_url ) ) {

                    throw new \Exception( 'Image directory is not located in a webroot path. Cannot generate a URL.' );
                }

                $path = substr( $path, strlen( $base_url ) );

                $path = Yii::$app->urlManager->baseUrl . str_replace( '\\', '/', $path );
            }

            return $path;
        }

        public function bootstrap( $app ) {

            if ( self::$_instance !== null ) {

                throw new \Exception( 'Only one instance of ModelImageModule can be initialized.' );
            }

            self::$_instance = $this;
        }
    }