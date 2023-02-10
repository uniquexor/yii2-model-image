<?php
    use yii\db\Migration;

    class m230209_153121_initial extends Migration {

        public function safeUp() {

            $tableOptions = null;
            if ( $this->db->driverName === 'mysql' ) {

                $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ENGINE=InnoDB';
            }

            // Create user images
            $this->createTable( 'images', array(
                'id' => 'pk',
                'group' => 'string',
                'name' => 'string not null',
                'extension' => 'varchar(10) not null',
                'is_temp' => 'smallint default 0',
                'uploaded_at' => 'int not null',
                'mime_type' => 'string',
                'width' => 'int null',
                'height' => 'int null',
                'size' => 'int null',
            ), $tableOptions );

            // Create image_versions table
            $this->createTable( 'image_versions', array(
                'id' => 'pk',
                'image_id' => 'int',
                'version' => 'varchar(45) not null',
                'width' => 'int not null',
                'height' => 'int not null',
                'size' => 'int not null',
            ), $tableOptions );

            $this->addForeignKey( 'image_versions_image_id', 'image_versions', 'image_id', 'images', 'id', 'CASCADE', 'CASCADE' );
        }

        public function safeDown() {

            $this->dropTable( 'images_versions' );
            $this->dropTable( 'images' );
        }
    }
