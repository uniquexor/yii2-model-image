<?php
    namespace unique\yii2modelimage\images\behaviors;

    use unique\yii2modelimage\images\ModelImageModule;
    use unique\yii2modelimage\images\models\Image;
    use yii\base\Behavior;
    use yii\base\Event;
    use yii\db\ActiveRecord;
    use yii\web\UploadedFile;

    class ImageBehavior extends Behavior {

        public string $uploaded_file_attribute = '';

        public string|null $group = null;

        public string|null $image_attribute = null;

        public bool $delete_old_image = true;

        /**
         * @inheritdoc
         */
        public function init() {

            parent::init();

            if ( !$this->uploaded_file_attribute ) {

                throw new \Exception( '`uploaded_file_attribute` is not set on ImageBehavior.' );
            }

            if ( $this->image_attribute === null ) {

                throw new \Exception( '`image_attribute` is not set on ImageBehavior.' );
            }

            if ( $this->group === null ) {

                throw new \Exception( '`group` is not set on ImageBehavior.' );
            }
        }

        /**
         * @inheritdoc
         */
        public function events() {

            return [
                ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
                ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
            ];
        }

        /**
         * @inheritdoc
         */
        public function afterSave( Event $event ) {

            /**
             * @var ActiveRecord $model
             */
            $model = $this->owner;

            $uploaded_file = UploadedFile::getInstance( $model, $this->uploaded_file_attribute );
            if ( $uploaded_file === null ) {

                return;
            }

            $class = ModelImageModule::getInstance()->image_class;
            $image_model = $class::createFromUploadedFile( $this->group, $uploaded_file );
            if ( $image_model->getErrors() ) {

                $model->addErrors( [ $this->image_attribute => $image_model->getFirstErrors() ] );
                return;
            }

            $old_image_id = $model->{$this->image_attribute};
            $model->updateAttributes( [ $this->image_attribute => $image_model->id ] );

            if ( $this->delete_old_image ) {

                $class::findOne( $old_image_id )?->delete();
            }
        }
    }