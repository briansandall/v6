ALTER TABLE `CubeCart_order_summary` ADD `weight` DECIMAL(16,3) NOT NULL DEFAULT '0.000' AFTER `ship_method`; #EOQ
UPDATE `CubeCart_customer` SET `order_count` = (SELECT COUNT(`id`) FROM `CubeCart_order_summary` WHERE `CubeCart_order_summary`.`customer_id` = `CubeCart_customer`.`customer_id`); #EOQ
ALTER TABLE `CubeCart_inventory` ADD COLUMN `product_width` DECIMAL(10,3) NULL DEFAULT NULL COMMENT 'Product Width' AFTER `product_weight`; #EOQ
ALTER TABLE `CubeCart_inventory` ADD COLUMN `product_height` DECIMAL(10,3) NULL DEFAULT NULL COMMENT 'Product Height' AFTER `product_weight`; #EOQ
ALTER TABLE `CubeCart_inventory` ADD COLUMN `product_length` DECIMAL(10,3) NULL DEFAULT NULL COMMENT 'Product Length' AFTER `product_weight`; #EOQALTER TABLE `CubeCart_inventory` ADD COLUMN `product_length` DECIMAL(10,3) NULL DEFAULT NULL COMMENT 'Product Length' AFTER `product_weight`; #EOQALTER TABLE `CubeCart_option_assign` ADD COLUMN `option_width` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `option_weight`; #EOQ
ALTER TABLE `CubeCart_option_assign` ADD COLUMN `option_width` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `option_weight`; #EOQ
ALTER TABLE `CubeCart_option_assign` ADD COLUMN `option_height` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `option_weight`; #EOQ
ALTER TABLE `CubeCart_option_assign` ADD COLUMN `option_length` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `option_weight`; #EOQ
ALTER TABLE `CubeCart_option_matrix` ADD COLUMN `price` decimal(16,2) unsigned NULL DEFAULT NULL COMMENT 'Retail Price for this option combination' AFTER `cached_name`;
ALTER TABLE `CubeCart_option_matrix` ADD COLUMN `sale_price` decimal(16,2) unsigned NULL DEFAULT NULL COMMENT 'Sale Price for this option combination' AFTER `price`;
ALTER TABLE `CubeCart_option_matrix` ADD COLUMN `set_enabled` TINYINT(1) unsigned NOT NULL DEFAULT 1 COMMENT 'Whether this combination is enabled' AFTER `cached_name`;