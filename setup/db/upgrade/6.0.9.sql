ALTER TABLE `CubeCart_order_summary` ADD `weight` DECIMAL(16,3) NOT NULL DEFAULT '0.000' AFTER `ship_method`; #EOQ
ALTER TABLE `CubeCart_inventory` ADD COLUMN `product_width` DECIMAL(10,3) NULL DEFAULT NULL COMMENT 'Product Width' AFTER `product_weight`; #EOQ
ALTER TABLE `CubeCart_inventory` ADD COLUMN `product_height` DECIMAL(10,3) NULL DEFAULT NULL COMMENT 'Product Height' AFTER `product_weight`; #EOQ
ALTER TABLE `CubeCart_inventory` ADD COLUMN `product_length` DECIMAL(10,3) NULL DEFAULT NULL COMMENT 'Product Length' AFTER `product_weight`; #EOQALTER TABLE `CubeCart_inventory` ADD COLUMN `product_length` DECIMAL(10,3) NULL DEFAULT NULL COMMENT 'Product Length' AFTER `product_weight`; #EOQALTER TABLE `CubeCart_option_assign` ADD COLUMN `option_width` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `option_weight`; #EOQ
ALTER TABLE `CubeCart_option_assign` ADD COLUMN `option_width` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `option_weight`; #EOQ
ALTER TABLE `CubeCart_option_assign` ADD COLUMN `option_height` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `option_weight`; #EOQ
ALTER TABLE `CubeCart_option_assign` ADD COLUMN `option_length` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `option_weight`; #EOQ