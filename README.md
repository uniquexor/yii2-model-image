Yii2 ModelImage
========================

A behavior for Yii2 Framework model class, that allows to easily attach an image, when saving a model.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
composer require unique/yii2-model-image
```

or add

```
"unique/yii2-model-image": "@dev"
```

to the require section of your `composer.json` file.

To create DB tables run migration file:

```
./yii migrate --migrationPath="vendor/unique/yii2-model-image/src/migrations"
```

Usage
-----

In order to use the behavior, you first need to load the module. Module can only be loaded once.
Add the following to your config file:

```php
<?php
    [
        // ...
        'modules' => [
            'images' => [
                'class' => \unique\yii2modelimage\ModelImageModule::class,
                
                // Defines an alias friendly path, where to store all the images
                'images_path' => '@app/www/images',
                
                // specify an Image class to use for generating images
                // should either extend the default class or implement methods needed
                'image_class' => \unique\yii2modelimage\models\Image::class,
                
                // Should list ImageDimensions for all image version groups, i.e.:    
                'group_versions' => [
                    // 'user_profile_photo' => [
                    //     'thumb_small' => ( new ImageDimensions( 'thumb_small', null, 80 ) )->asResized()->setQuality( 80 ),
                    //     'thumb_large' => ( new ImageDimensions( 'thumb_large', null, 800 ) )->asResized()->setQuality( 80 ),
                    // ],
                ],
            ],
            // ...
        ]   
    ]
?>
```

Then your model can implement a behavior like so:

```php
    class Profile extends \yii\db\ActiveRecord {
    
        // ...
        public $profile_photo;
    
        public function behaviors() {

            return array_merge( parent::behaviors(), [
                'image' => [
                    'class' => ImageBehavior::class,
                    'uploaded_file_attribute' => 'profile_photo',
                    'image_attribute' => 'profile_photo_id',
                    'group' => 'user_profile_photo',
                ]
            ] );
        }
        
        public function rules() {
        
            return [
                // ...
                [ [ 'profile_photo' ], 'file', 'extensions' => 'jpg' ],
            ];
        }
    }
```

Now, when saving Profile model, an uploaded image will be saved automatically, also
generating the two associated versions of the image called `thumb_small` and `thumb_large`.
If an error occurs,
while saving the image, `Profile::save()` will return false and an error will be set on the 
`Profile::$profile_photo` attribute. 