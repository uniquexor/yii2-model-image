<?php
    namespace unique\yii2modelimage\components;

    use unique\yii2modelimage\models\data\ImageDimensions;
    use Imagine\Exception\InvalidArgumentException;
    use Imagine\Image\AbstractImage;
    use Imagine\Image\Box;
    use Imagine\Image\BoxInterface;
    use Imagine\Image\ImageInterface;
    use Imagine\Image\ManipulatorInterface;
    use Imagine\Image\Point;
    use yii\imagine\Image;

    class ImageResizer {

        protected $image;

        public function __construct( ImageInterface $image ) {

            $this->image = $image;
        }

        /**
         * Generate a copy of the image, resizing it to given ImageDimensions.
         * @param ImageDimensions $dimensions
         * @return \Imagine\Gd\Image|\Imagine\Gmagick\Image|ImageInterface|\Imagine\Imagick\Image|void
         */
        public function generate( ImageDimensions $dimensions ) {

            $mode = $dimensions->crop_to_fit
                ? ManipulatorInterface::THUMBNAIL_OUTBOUND
                : ManipulatorInterface::THUMBNAIL_INSET;

            $width = $dimensions->width;
            $height = $dimensions->height;

            $original_size = $this->image->getSize();

            if ( $dimensions->width === null && $dimensions->height === null ) {

                return $this->image->copy();
            }

            if ( $dimensions->width === null ) {

                $width = ceil( $original_size->getWidth() * ( $dimensions->height / $original_size->getHeight() ) );
            } elseif ( $dimensions->height === null ) {

                $height = ceil( $original_size->getHeight() * ( $dimensions->width / $original_size->getWidth() ) );
            }

            $thumb = $this->thumbnail(
                new Box( $width, $height ),
                $mode,
                ImageInterface::FILTER_UNDEFINED,
                $dimensions->enlarge_smaller_images
            );

            if ( $dimensions->background_color === null ) {

                return $thumb;
            } elseif ( !$thumb->getSize()->contains( new Box( $width, $height ) ) ) {

                $palette = $thumb->palette();

                return Image::getImagine()->create( new Box( $width, $height ), $palette->color( $dimensions->background_color ) )
                    ->paste(
                        $thumb,
                        new Point(
                            round( ( $width - $thumb->getSize()->getWidth() ) / 2 ),
                            round( ( $height - $thumb->getSize()->getHeight() ) / 2 )
                        )
                    );
            }
        }

        /**
         * Almost the same method as {@see AbstractImage::thumbnail()} except allows to enlarge smaller images.
         * @param BoxInterface $size
         * @param string $mode
         * @param string $filter
         * @param false $enlarge
         * @return ImageInterface
         */
        protected function thumbnail(
            BoxInterface $size,
            $mode = ImageInterface::THUMBNAIL_INSET,
            $filter = ImageInterface::FILTER_UNDEFINED,
            $enlarge = false
        ) {

            if ( $mode !== ImageInterface::THUMBNAIL_INSET &&
                $mode !== ImageInterface::THUMBNAIL_OUTBOUND
            ) {

                throw new InvalidArgumentException( 'Invalid mode specified' );
            }

            $imageSize = $this->image->getSize();
            $ratios = array(
                $size->getWidth() / $imageSize->getWidth(),
                $size->getHeight() / $imageSize->getHeight()
            );

            $thumbnail = $this->image->copy();

            $thumbnail->usePalette( $this->image->palette() );
            $thumbnail->strip();
            // if target width is larger than image width
            // AND target height is longer than image height
            if ( $size->contains( $imageSize ) && !$enlarge ) {

                return $thumbnail;
            }

            if ( $mode === ImageInterface::THUMBNAIL_INSET ) {

                $ratio = min( $ratios );
            } else {

                $ratio = max( $ratios );
            }

            if ( $mode === ImageInterface::THUMBNAIL_OUTBOUND ) {

                if ( !$imageSize->contains( $size ) && !$enlarge ) {

                    $size = new Box(
                        min( $imageSize->getWidth(), $size->getWidth() ),
                        min( $imageSize->getHeight(), $size->getHeight() )
                    );
                } else {

                    $imageSize = $thumbnail->getSize()->scale( $ratio );
                    $thumbnail->resize( $imageSize, $filter );
                }
                $thumbnail->crop( new Point(
                    max( 0, round( ( $imageSize->getWidth() - $size->getWidth() ) / 2 ) ),
                    max( 0, round( ( $imageSize->getHeight() - $size->getHeight() ) / 2 ) )
                ), $size );
            } else {

                if ( !$imageSize->contains( $size ) && !$enlarge ) {

                    $imageSize = $imageSize->scale( $ratio );
                    $thumbnail->resize( $imageSize, $filter );
                } else {

                    $imageSize = $thumbnail->getSize()->scale( $ratio );
                    $thumbnail->resize( $imageSize, $filter );
                }
            }

            return $thumbnail;
        }
    }