<?php
    namespace unique\yii2modelimage\images\models\data;
    class File {

        /**
         * @var string Path to a file
         */
        public string $path;

        /**
         * @var string File name (without path, but with extension)
         */
        public string $name;

        /**
         * @var string Mime Type of the file
         */
        public string $mime_type;

        /**
         * @var int Size in bytes
         */
        public int $size;

        /**
         * @var int|null Timestamp of when the file was uploaded (if it was)
         */
        public int|null $uploaded_at = null;

        public function __construct( string $path, string $name, string $mime_type, int $size, int|null $uploaded_at = null ) {

            $this->path = $path;
            $this->name = $name;
            $this->mime_type = $mime_type;
            $this->size = $size;
            $this->uploaded_at = $uploaded_at;
        }
    }