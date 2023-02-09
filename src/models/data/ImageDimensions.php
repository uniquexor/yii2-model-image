<?php
    namespace unique\yii2modelimage\images\models\data;

    class ImageDimensions {

        public string $version;
        public int|null $width = null;
        public int|null $height = null;
        public int $quality = 90;

        /**
         * @var bool Should the image be cropped to fit the given dimensions? If true - the image will be resized, cropping it to fit the exact dimensions,
         *           if false - will be resized to fit the dimension, optionally filling empty space with a given background color.
         */
        public bool $crop_to_fit = true;

        /**
         * @var bool Should an image be enlarged if it is smaller than the given dimensions? If false and background_color is set, then empty space will be
         *           filled by a given color.
         */
        public bool $enlarge_smaller_images = true;

        /**
         * @var string Color to use to fill empty space in the image. If null, do not fill empty space.
         */
        public string|null $background_color = null;

        /**
         * If both width and height are null, means no resizing needs to be done.
         *
         * @param string $version - A given string to use as an image version, i.e.: "small", "large", "thumb", etc...
         * @param int|null $width - Width in pixels to resize the image to, or null to calculate new width by aspect ratio.
         * @param int|null $height - Height in pixels to resize the image to, or null to calculate new height by aspect ratio.
         */
        public function __construct( string $version, int|null $width = null, int|null $height = null ) {

            $this->version = $version;
            $this->width = $width;
            $this->height = $height;
        }

        /**
         * Image will be resized to completely fill the given dimensions (width and height), cropping any overflow.
         * (By default, will enlarge smaller images, however this can be overriden by using {@see enlarge()})
         *
         * @return $this
         */
        public function asCropped(): ImageDimensions {

            $this->crop_to_fit = true;
            $this->enlarge_smaller_images = true;

            return $this;
        }

        /**
         * Image will be resized to fit inside the given dimensions.
         * (By default, will enlarge smaller images, however this can be overriden by using {@see enlarge()})
         *
         * If the original picture aspect ratio is different from the given one, then:
         * - if background_color is null: the resulting image will maintain it's original aspect ratio (not the one provided)
         * - if background_color is provided: the resulting image will have the exact dimensions provided, filling blank space with a given color.
         *
         * @param string|null $background_color - Background color to fill empty space in: either a color code, i.e. "#000000" or null to not fill.
         * @return $this
         */
        public function asResized( string|null $background_color = null ): ImageDimensions {

            $this->crop_to_fit = false;
            $this->enlarge_smaller_images = true;
            $this->background_color = $background_color;

            return $this;
        }

        /**
         * Should smaller images be enlarged to fit or fill the given dimensions?
         * @param bool $value
         * @return $this
         */
        public function enlarge( bool $value = false ): ImageDimensions {

            $this->enlarge_smaller_images = $value;

            return $this;
        }

        /**
         * Resulting image quality setting.
         *
         * @param int $value
         * @return $this
         */
        public function setQuality( int $value = 90 ): ImageDimensions {

            $this->quality = $value;

            return $this;
        }
    }