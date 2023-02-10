--
-- Table structure for table `images`
--

CREATE TABLE `images` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `group` varchar(255) DEFAULT NULL,
    `name` varchar(255) NOT NULL,
    `extension` varchar(10) NOT NULL,
    `is_temp` tinyint(1) NOT NULL DEFAULT '0',
    `uploaded_at` int unsigned NOT NULL,
    `mime_type` varchar(45) DEFAULT NULL,
    `width` int unsigned DEFAULT NULL,
    `height` int unsigned DEFAULT NULL,
    `size` int unsigned DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `image_versions`
--

CREATE TABLE `image_versions` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `image_id` int unsigned NOT NULL,
    `version` varchar(45) NOT NULL,
    `width` int unsigned NOT NULL,
    `height` int unsigned NOT NULL,
    `size` int unsigned NOT NULL,
    PRIMARY KEY (`id`),
    KEY `image_versions_image_id` (`image_id`),
    CONSTRAINT `image_versions_image_id` FOREIGN KEY (`image_id`) REFERENCES `images` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;