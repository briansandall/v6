ALTER TABLE `CubeCart_inventory` CHANGE COLUMN `featured` `featured` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT 'Featured product'; #EOQ
ALTER TABLE `CubeCart_inventory` ADD COLUMN `latest` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT 'Included on Homepage' AFTER `featured`; #EOQ
UPDATE `CubeCart_inventory` SET `latest`=`featured`; #EOQ
ALTER TABLE `CubeCart_manufacturers` ADD COLUMN `lead_time` TINYINT UNSIGNED NOT NULL DEFAULT 14 COMMENT 'Lead time when out of stock, in days' AFTER `image`; #EOQ
ALTER TABLE `CubeCart_inventory` ADD COLUMN `lead_time` TINYINT UNSIGNED NULL DEFAULT NULL COMMENT 'Overrides manufacturer lead time' AFTER `manufacturer`; #EOQ