<?php
    use yii\db\Migration;

    class m230424_144025_filename extends Migration {

        public function safeUp() {

            $module = \unique\yii2modelimage\ModelImageModule::getInstance();
            if ( $module === null ) {

                throw new \Exception( 'ModelImageModule must be loaded before migrating.' );
            }

            $this->addColumn( 'images', 'unique_id', 'string not null before `name`' );

            $query = \unique\yii2modelimage\models\Image::find();

            foreach ( $query->each() as $image ) {

                /**
                 * @var \unique\yii2modelimage\models\Image $image
                 */

                $unique_id = $image->generateRandomKey();
                $image->updateAttributes( [ 'unique_id' => $unique_id ] );

                $base_path = $module->getImagePath( false, false ) . '/';
                foreach ( array_merge( [ null ], $image->versions ) as $version ) {

                    $existing_file_name = $base_path . $image->id . ( $version ? '_' . $version->version : '' ) . '.' . $image->extension;
                    if ( file_exists( $existing_file_name ) ) {

                        $new_file_name = $base_path . '_' . $image->id . '_' . $unique_id . ( $version ? '_' . $version->version : '' ) . '.' . $image->extension;
                        if ( !rename( $existing_file_name, $new_file_name ) ) {

                            throw new \Exception( 'Failed to rename `' . $existing_file_name . '` to `' . $new_file_name . '`' );
                        }
                    }
                }
            }
        }

        public function safeDown() {

            $this->dropColumn( 'images', 'unique_id' );
        }
    }
