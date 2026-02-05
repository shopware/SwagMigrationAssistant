SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP DATABASE IF EXISTS `shopware57`;
CREATE DATABASE `shopware57`;
USE `shopware57`;

DROP TABLE IF EXISTS `s_addon_premiums`;
CREATE TABLE `s_addon_premiums` (
  `id` int NOT NULL AUTO_INCREMENT,
  `startprice` double NOT NULL DEFAULT '0',
  `ordernumber` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '0',
  `ordernumber_export` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `subshopID` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_article_configurator_dependencies`;
CREATE TABLE `s_article_configurator_dependencies` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `configurator_set_id` int unsigned NOT NULL,
  `parent_id` int unsigned DEFAULT NULL,
  `child_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `configurator_set_id` (`configurator_set_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_article_configurator_groups`;
CREATE TABLE `s_article_configurator_groups` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `position` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_article_configurator_groups` (`id`, `name`, `description`, `position`) VALUES
(5,	'Farbe',	NULL,	1),
(6,	'Größe',	NULL,	2),
(7,	'Ausstattung',	NULL,	3),
(8,	'Design',	NULL,	4);

DROP TABLE IF EXISTS `s_article_configurator_groups_attributes`;
CREATE TABLE `s_article_configurator_groups_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `groupID` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `groupID` (`groupID`),
  CONSTRAINT `s_article_configurator_groups_attributes_ibfk_1` FOREIGN KEY (`groupID`) REFERENCES `s_article_configurator_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_article_configurator_option_relations`;
CREATE TABLE `s_article_configurator_option_relations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `article_id` int unsigned NOT NULL,
  `option_id` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `article_id` (`article_id`,`option_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_article_configurator_option_relations` (`id`, `article_id`, `option_id`) VALUES
(47,	4,	8),
(4,	7,	4),
(50,	14,	11),
(2,	15,	2),
(8,	16,	1),
(9,	16,	5),
(5,	17,	5),
(6,	18,	6),
(7,	19,	7),
(10,	20,	1),
(11,	20,	7),
(12,	21,	2),
(13,	21,	5),
(14,	22,	2),
(15,	22,	7),
(16,	23,	3),
(17,	23,	5),
(18,	24,	3),
(19,	24,	7),
(21,	25,	2),
(23,	26,	1),
(24,	26,	4),
(25,	27,	1),
(26,	27,	5),
(27,	28,	1),
(28,	28,	6),
(29,	29,	1),
(30,	29,	7),
(31,	30,	2),
(32,	30,	4),
(33,	31,	2),
(34,	31,	5),
(35,	32,	2),
(36,	32,	6),
(37,	33,	2),
(38,	33,	7),
(39,	34,	3),
(40,	34,	4),
(41,	35,	3),
(42,	35,	5),
(43,	36,	3),
(44,	36,	6),
(45,	37,	3),
(46,	37,	7),
(48,	38,	9),
(49,	39,	10),
(51,	40,	12),
(52,	41,	13);

DROP TABLE IF EXISTS `s_article_configurator_options`;
CREATE TABLE `s_article_configurator_options` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int unsigned DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `position` int NOT NULL,
  `media_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_id` (`group_id`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_article_configurator_options` (`id`, `group_id`, `name`, `position`, `media_id`) VALUES
(1,	5,	'Rot',	1,	NULL),
(2,	5,	'Blau',	2,	NULL),
(3,	5,	'Weiß',	3,	NULL),
(4,	6,	'S',	1,	NULL),
(5,	6,	'M',	2,	NULL),
(6,	6,	'L',	3,	NULL),
(7,	6,	'XL',	4,	NULL),
(8,	7,	'Zero',	1,	NULL),
(9,	7,	'Half',	2,	NULL),
(10,	7,	'Full',	3,	NULL),
(11,	8,	'Standard',	1,	NULL),
(12,	8,	'Stripes',	2,	NULL),
(13,	8,	'Shopware',	3,	NULL);

DROP TABLE IF EXISTS `s_article_configurator_options_attributes`;
CREATE TABLE `s_article_configurator_options_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `optionID` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `optionID` (`optionID`),
  CONSTRAINT `s_article_configurator_options_attributes_ibfk_1` FOREIGN KEY (`optionID`) REFERENCES `s_article_configurator_options` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_article_configurator_price_variations`;
CREATE TABLE `s_article_configurator_price_variations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `configurator_set_id` int unsigned NOT NULL,
  `variation` decimal(10,3) NOT NULL,
  `options` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `is_gross` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `configurator_set_id` (`configurator_set_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_article_configurator_set_group_relations`;
CREATE TABLE `s_article_configurator_set_group_relations` (
  `set_id` int unsigned NOT NULL DEFAULT '0',
  `group_id` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`set_id`,`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_article_configurator_set_group_relations` (`set_id`, `group_id`) VALUES
(3,	5),
(3,	6),
(4,	6),
(5,	5),
(5,	6),
(6,	7),
(7,	8);

DROP TABLE IF EXISTS `s_article_configurator_set_option_relations`;
CREATE TABLE `s_article_configurator_set_option_relations` (
  `set_id` int unsigned NOT NULL DEFAULT '0',
  `option_id` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`set_id`,`option_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_article_configurator_set_option_relations` (`set_id`, `option_id`) VALUES
(3,	1),
(3,	2),
(3,	3),
(3,	5),
(3,	7),
(4,	4),
(4,	5),
(4,	6),
(4,	7),
(5,	1),
(5,	2),
(5,	3),
(5,	4),
(5,	5),
(5,	6),
(5,	7),
(6,	8),
(6,	9),
(6,	10),
(7,	11),
(7,	12),
(7,	13);

DROP TABLE IF EXISTS `s_article_configurator_sets`;
CREATE TABLE `s_article_configurator_sets` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `public` tinyint(1) NOT NULL,
  `type` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_article_configurator_sets` (`id`, `name`, `public`, `type`) VALUES
(3,	'Set-SW10005',	0,	0),
(4,	'Set-SW10007',	0,	0),
(5,	'Set-SW10009',	0,	1),
(6,	'Set-SW10004',	0,	0),
(7,	'Set-SW10014',	0,	2);

DROP TABLE IF EXISTS `s_article_configurator_template_prices`;
CREATE TABLE `s_article_configurator_template_prices` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `template_id` int unsigned DEFAULT NULL,
  `customer_group_key` varchar(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `from` int unsigned NOT NULL,
  `to` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `price` double NOT NULL DEFAULT '0',
  `pseudoprice` double DEFAULT NULL,
  `regulation_price` double DEFAULT NULL,
  `percent` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pricegroup_2` (`customer_group_key`,`from`),
  KEY `pricegroup` (`customer_group_key`,`to`),
  KEY `template_id` (`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_article_configurator_template_prices` (`id`, `template_id`, `customer_group_key`, `from`, `to`, `price`, `pseudoprice`, `regulation_price`, `percent`) VALUES
(1,	1,	'EK',	1,	'beliebig',	16.798319327731,	0,	NULL,	0.00),
(2,	2,	'EK',	1,	'beliebig',	16.798319327731,	0,	NULL,	0.00),
(3,	3,	'EK',	1,	'beliebig',	21.008403361345,	0,	NULL,	0.00),
(4,	4,	'EK',	1,	'beliebig',	21.008403361345,	0,	NULL,	0.00),
(5,	5,	'EK',	1,	'beliebig',	218.47899159664,	0,	NULL,	0.00);

DROP TABLE IF EXISTS `s_article_configurator_template_prices_attributes`;
CREATE TABLE `s_article_configurator_template_prices_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `template_price_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `priceID` (`template_price_id`),
  CONSTRAINT `s_article_configurator_template_prices_attributes_ibfk_1` FOREIGN KEY (`template_price_id`) REFERENCES `s_article_configurator_template_prices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_article_configurator_templates`;
CREATE TABLE `s_article_configurator_templates` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `article_id` int unsigned NOT NULL DEFAULT '0',
  `order_number` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `suppliernumber` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `additionaltext` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `impressions` int NOT NULL DEFAULT '0',
  `sales` int NOT NULL DEFAULT '0',
  `active` int unsigned NOT NULL DEFAULT '0',
  `instock` int DEFAULT NULL,
  `stockmin` int unsigned DEFAULT NULL,
  `laststock` tinyint NOT NULL DEFAULT '0',
  `weight` decimal(10,3) unsigned DEFAULT NULL,
  `position` int unsigned NOT NULL,
  `width` decimal(10,3) unsigned DEFAULT NULL,
  `height` decimal(10,3) unsigned DEFAULT NULL,
  `length` decimal(10,3) unsigned DEFAULT NULL,
  `ean` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `unit_id` int unsigned DEFAULT NULL,
  `purchasesteps` int unsigned DEFAULT NULL,
  `maxpurchase` int unsigned DEFAULT NULL,
  `minpurchase` int unsigned DEFAULT NULL,
  `purchaseunit` decimal(11,4) unsigned DEFAULT NULL,
  `referenceunit` decimal(10,3) unsigned DEFAULT NULL,
  `packunit` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `releasedate` date DEFAULT NULL,
  `shippingfree` int unsigned NOT NULL DEFAULT '0',
  `shippingtime` varchar(11) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `purchaseprice` double NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `articleID` (`article_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_article_configurator_templates` (`id`, `article_id`, `order_number`, `suppliernumber`, `additionaltext`, `impressions`, `sales`, `active`, `instock`, `stockmin`, `laststock`, `weight`, `position`, `width`, `height`, `length`, `ean`, `unit_id`, `purchasesteps`, `maxpurchase`, `minpurchase`, `purchaseunit`, `referenceunit`, `packunit`, `releasedate`, `shippingfree`, `shippingtime`, `purchaseprice`) VALUES
(1,	5,	'SW10005',	'',	'',	0,	0,	1,	50,	5,	0,	0.500,	0,	NULL,	NULL,	NULL,	'',	9,	NULL,	NULL,	1,	1.0000,	1.000,	'',	NULL,	0,	'',	0),
(2,	7,	'SW10007',	'',	'',	0,	0,	1,	50,	5,	0,	0.000,	0,	NULL,	NULL,	NULL,	'',	9,	NULL,	NULL,	1,	1.0000,	1.000,	'',	NULL,	0,	'',	0),
(3,	9,	'SW10009',	'',	'',	0,	0,	1,	75,	5,	0,	0.550,	0,	NULL,	NULL,	NULL,	'',	9,	NULL,	NULL,	1,	1.0000,	1.000,	'',	NULL,	0,	'',	0),
(4,	4,	'SW10004',	'',	'',	0,	0,	1,	10,	0,	0,	0.000,	0,	NULL,	NULL,	NULL,	'',	9,	NULL,	NULL,	1,	1.0000,	1.000,	'',	NULL,	0,	'',	0),
(5,	14,	'SW10014',	'',	'',	0,	0,	1,	100,	10,	0,	0.000,	0,	NULL,	NULL,	NULL,	'',	9,	NULL,	NULL,	1,	1.0000,	1.000,	'',	NULL,	0,	'',	0);

DROP TABLE IF EXISTS `s_article_configurator_templates_attributes`;
CREATE TABLE `s_article_configurator_templates_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `template_id` int unsigned DEFAULT NULL,
  `attr1` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attr2` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attr3` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attr4` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attr5` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attr6` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attr7` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attr8` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attr9` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attr10` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attr11` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attr12` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attr13` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attr14` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attr15` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attr16` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attr17` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attr18` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attr19` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attr20` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `templateID` (`template_id`),
  CONSTRAINT `s_article_configurator_templates_attributes_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `s_article_configurator_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_article_configurator_templates_attributes` (`id`, `template_id`, `attr1`, `attr2`, `attr3`, `attr4`, `attr5`, `attr6`, `attr7`, `attr8`, `attr9`, `attr10`, `attr11`, `attr12`, `attr13`, `attr14`, `attr15`, `attr16`, `attr17`, `attr18`, `attr19`, `attr20`) VALUES
(1,	1,	'',	'',	'',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL),
(2,	2,	'',	'',	'',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL),
(3,	4,	'',	'',	'',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL),
(4,	5,	'',	'',	'',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL);

DROP TABLE IF EXISTS `s_article_img_mapping_rules`;
CREATE TABLE `s_article_img_mapping_rules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mapping_id` int NOT NULL,
  `option_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `mapping_id` (`mapping_id`),
  KEY `option_id` (`option_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_article_img_mapping_rules` (`id`, `mapping_id`, `option_id`) VALUES
(9,	6,	1),
(10,	7,	2),
(11,	5,	3),
(12,	8,	3),
(13,	9,	1),
(14,	10,	2),
(15,	11,	10),
(16,	12,	9),
(17,	13,	8),
(18,	14,	11),
(19,	15,	12),
(21,	17,	13);

DROP TABLE IF EXISTS `s_article_img_mappings`;
CREATE TABLE `s_article_img_mappings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `image_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `image_id` (`image_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_article_img_mappings` (`id`, `image_id`) VALUES
(5,	5),
(6,	19),
(7,	20),
(8,	31),
(9,	32),
(10,	33),
(11,	46),
(12,	47),
(13,	48),
(14,	52),
(15,	53),
(17,	58);

DROP TABLE IF EXISTS `s_articles`;
CREATE TABLE `s_articles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `supplierID` int unsigned DEFAULT NULL,
  `name` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `description_long` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `shippingtime` varchar(11) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `datum` date DEFAULT NULL,
  `active` int unsigned NOT NULL DEFAULT '0',
  `taxID` int unsigned DEFAULT NULL,
  `pseudosales` int NOT NULL DEFAULT '0',
  `topseller` int unsigned NOT NULL DEFAULT '0',
  `metaTitle` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `keywords` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `changetime` datetime NOT NULL,
  `pricegroupID` int unsigned DEFAULT NULL,
  `pricegroupActive` int unsigned NOT NULL,
  `filtergroupID` int unsigned DEFAULT NULL,
  `laststock` int NOT NULL,
  `crossbundlelook` int unsigned NOT NULL,
  `notification` int unsigned NOT NULL COMMENT 'send notification',
  `template` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `mode` int NOT NULL,
  `main_detail_id` int unsigned DEFAULT NULL,
  `available_from` datetime DEFAULT NULL,
  `available_to` datetime DEFAULT NULL,
  `configurator_set_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `main_detailID` (`main_detail_id`),
  KEY `datum` (`datum`),
  KEY `name` (`name`),
  KEY `supplierID` (`supplierID`),
  KEY `shippingtime` (`shippingtime`),
  KEY `changetime` (`changetime`),
  KEY `configurator_set_id` (`configurator_set_id`),
  KEY `articles_by_category_sort_release` (`datum`,`id`),
  KEY `articles_by_category_sort_name` (`name`,`id`),
  KEY `product_newcomer` (`active`,`datum`),
  KEY `get_category_filters` (`active`,`filtergroupID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_articles` (`id`, `supplierID`, `name`, `description`, `description_long`, `shippingtime`, `datum`, `active`, `taxID`, `pseudosales`, `topseller`, `metaTitle`, `keywords`, `changetime`, `pricegroupID`, `pricegroupActive`, `filtergroupID`, `laststock`, `crossbundlelook`, `notification`, `template`, `mode`, `main_detail_id`, `available_from`, `available_to`, `configurator_set_id`) VALUES
(1,	3,	'Hauptartikel',	'',	'<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.</p>',	NULL,	'2017-10-05',	1,	1,	0,	0,	'',	'',	'2017-10-10 09:44:42',	NULL,	0,	3,	0,	0,	0,	'',	0,	1,	NULL,	NULL,	NULL),
(2,	3,	'Hauptartikel mit E-Mail-Benachrichtigung',	'',	'<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.</p>',	NULL,	'2017-10-05',	1,	1,	0,	0,	'',	'',	'2017-10-10 10:04:49',	NULL,	0,	3,	1,	0,	1,	'',	0,	2,	NULL,	NULL,	NULL),
(3,	3,	'Hauptartikel mit ESD Download',	'',	'<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.</p>',	NULL,	'2017-10-05',	1,	1,	0,	0,	'',	'',	'2017-10-10 10:04:32',	NULL,	0,	3,	0,	0,	0,	'',	0,	3,	NULL,	NULL,	NULL),
(4,	3,	'Artikel mit Standard-Konfigurator',	'',	'<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.</p>',	NULL,	'2017-10-05',	1,	1,	0,	0,	'',	'',	'2017-12-07 12:56:07',	NULL,	0,	4,	0,	0,	0,	'',	0,	39,	NULL,	NULL,	6),
(5,	1,	'Variantenartikel',	'',	'<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.</p>',	NULL,	'2017-10-05',	1,	1,	0,	0,	'',	'',	'2017-10-10 09:42:07',	NULL,	0,	1,	0,	0,	0,	'',	0,	23,	NULL,	NULL,	3),
(6,	1,	'Hauptartikel mit Kennzeichnung Versandkostenfrei und Hervorhebung',	'',	'<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.</p>',	NULL,	'2017-10-05',	1,	1,	0,	1,	'',	'',	'2017-10-10 10:04:10',	NULL,	0,	1,	0,	0,	0,	'',	0,	6,	NULL,	NULL,	NULL),
(7,	1,	'Hauptartikel mit Eigenschaften',	'',	'<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.</p>',	NULL,	'2017-10-05',	1,	1,	0,	0,	'',	'',	'2017-10-10 10:03:41',	NULL,	0,	1,	0,	0,	0,	'',	0,	7,	NULL,	NULL,	4),
(8,	1,	'Hauptartikel mit Ressourcen',	'',	'<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.</p>',	NULL,	'2017-10-05',	1,	1,	0,	0,	'',	'',	'2017-10-10 09:40:51',	NULL,	0,	1,	0,	0,	0,	'',	0,	8,	NULL,	NULL,	NULL),
(9,	1,	'Artikel mit Auswahl-Konfigurator',	'',	'<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.</p>',	NULL,	'2017-10-05',	1,	1,	0,	0,	'',	'',	'2017-10-10 09:59:59',	NULL,	0,	1,	0,	0,	0,	'',	0,	34,	NULL,	NULL,	5),
(10,	2,	'Hauptartikel mit Grundpreisberechnung',	'',	'<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.</p>',	NULL,	'2017-10-05',	1,	1,	0,	0,	'',	'',	'2017-10-10 10:02:56',	NULL,	0,	2,	0,	0,	0,	'',	0,	10,	NULL,	NULL,	NULL),
(11,	2,	'Hauptartikel mit Abverkauf',	'',	'<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.</p>',	NULL,	'2017-10-05',	1,	1,	0,	0,	'',	'',	'2018-03-09 11:46:35',	NULL,	0,	2,	1,	0,	0,	'',	0,	11,	NULL,	NULL,	NULL),
(12,	2,	'Hauptartikel mit Cross-Selling',	'',	'<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.</p>',	NULL,	'2017-10-05',	1,	1,	0,	0,	'',	'',	'2017-10-10 10:01:40',	NULL,	0,	2,	0,	0,	0,	'',	0,	12,	NULL,	NULL,	NULL),
(13,	2,	'Hauptartikel mit Bewertungen',	'',	'<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.</p>',	NULL,	'2017-10-05',	1,	1,	0,	0,	'',	'',	'2017-10-10 09:43:51',	NULL,	0,	2,	0,	0,	0,	'',	0,	13,	NULL,	NULL,	NULL),
(14,	1,	'Artikel mit Bild-Konfigurator',	'',	'<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.</p>',	NULL,	'2017-10-05',	1,	1,	0,	0,	'',	'',	'2017-11-08 09:29:38',	NULL,	0,	1,	0,	0,	0,	'',	0,	14,	NULL,	NULL,	7);

DROP TABLE IF EXISTS `s_articles_also_bought_ro`;
CREATE TABLE `s_articles_also_bought_ro` (
  `id` int NOT NULL AUTO_INCREMENT,
  `article_id` int unsigned NOT NULL,
  `related_article_id` int NOT NULL,
  `sales` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `bought_combination` (`article_id`,`related_article_id`),
  KEY `related_article_id` (`related_article_id`),
  KEY `article_id` (`article_id`),
  KEY `get_also_bought_articles` (`article_id`,`sales`,`related_article_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_articles_attributes`;
CREATE TABLE `s_articles_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `articledetailsID` int unsigned DEFAULT NULL,
  `attr1` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attr2` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attr3` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attr4` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attr5` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attr6` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attr7` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attr8` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attr9` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attr10` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attr11` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attr12` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attr13` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attr14` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attr15` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attr16` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attr17` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attr18` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attr19` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attr20` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `articledetailsID` (`articledetailsID`),
  CONSTRAINT `s_articles_attributes_ibfk_2` FOREIGN KEY (`articledetailsID`) REFERENCES `s_articles_details` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_articles_attributes` (`id`, `articledetailsID`, `attr1`, `attr2`, `attr3`, `attr4`, `attr5`, `attr6`, `attr7`, `attr8`, `attr9`, `attr10`, `attr11`, `attr12`, `attr13`, `attr14`, `attr15`, `attr16`, `attr17`, `attr18`, `attr19`, `attr20`) VALUES
(1,	1,	'',	'',	'',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL),
(2,	2,	'',	'',	'',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL),
(3,	3,	'',	'',	'',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL),
(4,	4,	'',	'',	'',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL),
(6,	6,	'',	'',	'',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL),
(7,	7,	'',	'',	'',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL),
(8,	8,	'',	'',	'',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL),
(10,	10,	'',	'',	'',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL),
(11,	11,	'',	'',	'',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL),
(12,	12,	'',	'',	'',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL),
(13,	13,	'',	'',	'',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL),
(14,	14,	'',	'',	'',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL),
(16,	16,	'',	'',	'',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL),
(17,	17,	'',	'',	'',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL),
(18,	18,	'',	'',	'',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL),
(19,	19,	'',	'',	'',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL),
(20,	20,	'',	'',	'',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL),
(21,	21,	'',	'',	'',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL),
(22,	22,	'',	'',	'',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL),
(23,	23,	'',	'',	'',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL),
(24,	24,	'',	'',	'',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL),
(26,	34,	'',	'',	'',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL),
(27,	38,	'',	'',	'',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL),
(28,	39,	'',	'',	'',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL),
(29,	40,	'',	'',	'',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL),
(30,	41,	'',	'',	'',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL);

DROP TABLE IF EXISTS `s_articles_avoid_customergroups`;
CREATE TABLE `s_articles_avoid_customergroups` (
  `articleID` int NOT NULL,
  `customergroupID` int NOT NULL,
  PRIMARY KEY (`articleID`,`customergroupID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_articles_categories`;
CREATE TABLE `s_articles_categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `articleID` int unsigned NOT NULL,
  `categoryID` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `articleID` (`articleID`,`categoryID`),
  KEY `categoryID` (`categoryID`),
  KEY `articleID_2` (`articleID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_articles_categories` (`id`, `articleID`, `categoryID`) VALUES
(138,	1,	7),
(154,	2,	7),
(153,	3,	7),
(160,	4,	7),
(130,	5,	11),
(152,	6,	12),
(151,	7,	12),
(126,	8,	11),
(127,	8,	12),
(145,	9,	12),
(150,	10,	10),
(161,	11,	8),
(147,	12,	9),
(134,	13,	10),
(158,	14,	11);

DROP TABLE IF EXISTS `s_articles_categories_ro`;
CREATE TABLE `s_articles_categories_ro` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `articleID` int unsigned NOT NULL,
  `categoryID` int unsigned NOT NULL,
  `parentCategoryID` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `articleID` (`articleID`,`categoryID`,`parentCategoryID`),
  KEY `categoryID` (`categoryID`),
  KEY `articleID_2` (`articleID`),
  KEY `categoryID_2` (`categoryID`,`parentCategoryID`),
  KEY `category_id_by_article_id` (`articleID`,`id`),
  KEY `elastic_search` (`categoryID`,`articleID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_articles_categories_ro` (`id`, `articleID`, `categoryID`, `parentCategoryID`) VALUES
(99,	1,	3,	7),
(98,	1,	7,	7),
(97,	2,	3,	7),
(96,	2,	7,	7),
(95,	3,	3,	7),
(94,	3,	7,	7),
(72,	4,	3,	7),
(71,	4,	7,	7),
(43,	5,	3,	11),
(42,	5,	6,	11),
(41,	5,	11,	11),
(93,	6,	3,	12),
(92,	6,	6,	12),
(91,	6,	12,	12),
(90,	7,	3,	12),
(89,	7,	6,	12),
(88,	7,	12,	12),
(58,	8,	3,	11),
(87,	8,	3,	12),
(57,	8,	6,	11),
(86,	8,	6,	12),
(56,	8,	11,	11),
(85,	8,	12,	12),
(75,	9,	3,	12),
(74,	9,	6,	12),
(73,	9,	12,	12),
(84,	10,	3,	10),
(83,	10,	5,	10),
(82,	10,	10,	10),
(40,	11,	3,	8),
(39,	11,	5,	8),
(38,	11,	8,	8),
(81,	12,	3,	9),
(80,	12,	5,	9),
(79,	12,	9,	9),
(78,	13,	3,	10),
(77,	13,	5,	10),
(76,	13,	10,	10),
(52,	14,	3,	11),
(51,	14,	6,	11),
(50,	14,	11,	11);

DROP TABLE IF EXISTS `s_articles_categories_seo`;
CREATE TABLE `s_articles_categories_seo` (
  `id` int NOT NULL AUTO_INCREMENT,
  `shop_id` int NOT NULL,
  `article_id` int NOT NULL,
  `category_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `shop_article` (`shop_id`,`article_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_articles_details`;
CREATE TABLE `s_articles_details` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `articleID` int unsigned NOT NULL DEFAULT '0',
  `ordernumber` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `suppliernumber` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `kind` int NOT NULL DEFAULT '0',
  `additionaltext` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `sales` int NOT NULL DEFAULT '0',
  `active` int unsigned NOT NULL DEFAULT '0',
  `instock` int,
  `stockmin` int unsigned DEFAULT NULL,
  `laststock` int NOT NULL DEFAULT '0',
  `weight` decimal(10,3) unsigned DEFAULT NULL,
  `position` int unsigned NOT NULL,
  `width` decimal(10,3) unsigned DEFAULT NULL,
  `height` decimal(10,3) unsigned DEFAULT NULL,
  `length` decimal(10,3) unsigned DEFAULT NULL,
  `ean` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `unitID` int unsigned DEFAULT NULL,
  `purchasesteps` int unsigned DEFAULT NULL,
  `maxpurchase` int unsigned DEFAULT NULL,
  `minpurchase` int unsigned NOT NULL DEFAULT '1',
  `purchaseunit` decimal(11,4) unsigned DEFAULT NULL,
  `referenceunit` decimal(10,3) unsigned DEFAULT NULL,
  `packunit` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `releasedate` date DEFAULT NULL,
  `shippingfree` int unsigned NOT NULL DEFAULT '0',
  `shippingtime` varchar(11) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `purchaseprice` double NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ordernumber` (`ordernumber`),
  KEY `articleID` (`articleID`),
  KEY `releasedate` (`releasedate`),
  KEY `articles_by_category_sort_popularity` (`sales`,`articleID`),
  KEY `get_similar_articles` (`kind`,`sales`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_articles_details` (`id`, `articleID`, `ordernumber`, `suppliernumber`, `kind`, `additionaltext`, `sales`, `active`, `instock`, `stockmin`, `laststock`, `weight`, `position`, `width`, `height`, `length`, `ean`, `unitID`, `purchasesteps`, `maxpurchase`, `minpurchase`, `purchaseunit`, `referenceunit`, `packunit`, `releasedate`, `shippingfree`, `shippingtime`, `purchaseprice`) VALUES
(1,	1,	'SW10001',	'',	1,	'',	0,	1,	NULL,	2,	0,	0.170,	0,	NULL,	NULL,	NULL,	'',	9,	NULL,	NULL,	1,	1.0000,	1.000,	'',	NULL,	0,	'3',	0),
(2,	2,	'SW10002',	'',	1,	'',	0,	1,	0,	0,	0,	45.000,	0,	1.000,	1.000,	1.000,	'',	9,	NULL,	NULL,	1,	1.0000,	1.000,	'',	NULL,	1,	'7',	0),
(3,	3,	'SW10003',	'',	1,	'',	0,	1,	0,	0,	0,	0.000,	0,	NULL,	NULL,	NULL,	'',	9,	NULL,	NULL,	1,	1.0000,	1.000,	'',	NULL,	1,	'0',	0),
(4,	4,	'SW10004',	'',	2,	'',	0,	1,	10,	0,	0,	0.000,	0,	NULL,	NULL,	NULL,	'',	9,	NULL,	NULL,	1,	1.0000,	1.000,	'',	NULL,	0,	'',	0),
(6,	6,	'SW10006',	'',	1,	'',	0,	1,	50,	3,	0,	0.150,	0,	NULL,	NULL,	NULL,	'',	9,	NULL,	NULL,	1,	1.0000,	1.000,	'',	NULL,	1,	'',	0),
(7,	7,	'SW10007',	'',	1,	'',	0,	1,	50,	5,	0,	0.000,	0,	NULL,	NULL,	NULL,	'',	9,	NULL,	NULL,	1,	1.0000,	1.000,	'',	NULL,	0,	'',	0),
(8,	8,	'SW10008',	'',	1,	'',	0,	1,	100,	10,	0,	0.100,	0,	NULL,	NULL,	NULL,	'',	9,	NULL,	NULL,	1,	1.0000,	1.000,	'',	NULL,	0,	'',	0),
(10,	10,	'SW10010',	'',	1,	'',	0,	1,	200,	20,	0,	1.000,	0,	NULL,	NULL,	NULL,	'',	1,	NULL,	NULL,	1,	0.5000,	1.000,	'',	NULL,	0,	'',	0),
(11,	11,	'SW10011',	'',	1,	'',	0,	1,	0,	0,	1,	0.000,	0,	NULL,	NULL,	NULL,	'',	9,	NULL,	NULL,	1,	1.0000,	1.000,	'',	NULL,	0,	'',	0),
(12,	12,	'SW10012',	'',	1,	'',	0,	1,	10,	1,	0,	0.000,	0,	NULL,	NULL,	NULL,	'',	2,	NULL,	NULL,	1,	500.0000,	500.000,	'',	NULL,	0,	'',	0),
(13,	13,	'SW10013',	'',	1,	'',	0,	1,	40,	4,	0,	0.000,	0,	NULL,	NULL,	NULL,	'',	2,	NULL,	NULL,	1,	250.0000,	250.000,	'',	NULL,	0,	'',	0),
(14,	14,	'SW10014',	'',	1,	'',	0,	1,	100,	10,	0,	0.000,	0,	NULL,	NULL,	NULL,	'',	9,	NULL,	NULL,	1,	1.0000,	1.000,	'',	NULL,	0,	'',	0),
(16,	5,	'SW10005',	'',	2,	'',	0,	1,	50,	5,	0,	0.500,	0,	NULL,	NULL,	NULL,	'',	9,	NULL,	NULL,	1,	1.0000,	1.000,	'',	NULL,	0,	'',	0),
(17,	7,	'SW10007.1',	'',	2,	'',	0,	1,	50,	5,	0,	0.000,	0,	NULL,	NULL,	NULL,	'',	9,	NULL,	NULL,	1,	1.0000,	1.000,	'',	NULL,	0,	'',	0),
(18,	7,	'SW10007.2',	'',	2,	'',	0,	1,	50,	5,	0,	0.000,	0,	NULL,	NULL,	NULL,	'',	9,	NULL,	NULL,	1,	1.0000,	1.000,	'',	NULL,	0,	'',	0),
(19,	7,	'SW10007.3',	'',	2,	'',	0,	1,	50,	5,	0,	0.000,	0,	NULL,	NULL,	NULL,	'',	9,	NULL,	NULL,	1,	1.0000,	1.000,	'',	NULL,	0,	'',	0),
(20,	5,	'SW10005.1',	'',	2,	'',	0,	1,	50,	5,	0,	0.500,	0,	NULL,	NULL,	NULL,	'',	9,	NULL,	NULL,	1,	1.0000,	1.000,	'',	NULL,	0,	'',	0),
(21,	5,	'SW10005.2',	'',	2,	'',	0,	1,	50,	5,	0,	0.500,	0,	NULL,	NULL,	NULL,	'',	9,	NULL,	NULL,	1,	1.0000,	1.000,	'',	NULL,	0,	'',	0),
(22,	5,	'SW10005.3',	'',	2,	'',	0,	1,	50,	5,	0,	0.500,	0,	NULL,	NULL,	NULL,	'',	9,	NULL,	NULL,	1,	1.0000,	1.000,	'',	NULL,	0,	'',	0),
(23,	5,	'SW10005.4',	'',	1,	'',	0,	1,	50,	5,	0,	0.500,	0,	NULL,	NULL,	NULL,	'',	9,	NULL,	NULL,	1,	1.0000,	1.000,	'',	NULL,	0,	'',	0),
(24,	5,	'SW10005.5',	'',	2,	'',	0,	1,	50,	5,	0,	0.500,	0,	NULL,	NULL,	NULL,	'',	9,	NULL,	NULL,	1,	1.0000,	1.000,	'',	NULL,	0,	'',	0),
(26,	9,	'SW10009',	'',	2,	'',	0,	1,	75,	5,	0,	0.550,	0,	NULL,	NULL,	NULL,	'',	9,	NULL,	NULL,	1,	1.0000,	1.000,	'',	NULL,	0,	'',	0),
(27,	9,	'SW10009.1',	'',	2,	'',	0,	1,	75,	5,	0,	0.550,	0,	NULL,	NULL,	NULL,	'',	9,	NULL,	NULL,	1,	1.0000,	1.000,	'',	NULL,	0,	'',	0),
(28,	9,	'SW10009.2',	'',	2,	'',	0,	1,	75,	5,	0,	0.550,	0,	NULL,	NULL,	NULL,	'',	9,	NULL,	NULL,	1,	1.0000,	1.000,	'',	NULL,	0,	'',	0),
(29,	9,	'SW10009.3',	'',	2,	'',	0,	1,	75,	5,	0,	0.550,	0,	NULL,	NULL,	NULL,	'',	9,	NULL,	NULL,	1,	1.0000,	1.000,	'',	NULL,	0,	'',	0),
(30,	9,	'SW10009.4',	'',	2,	'',	0,	1,	75,	5,	0,	0.550,	0,	NULL,	NULL,	NULL,	'',	9,	NULL,	NULL,	1,	1.0000,	1.000,	'',	NULL,	0,	'',	0),
(31,	9,	'SW10009.5',	'',	2,	'',	0,	1,	75,	5,	0,	0.550,	0,	NULL,	NULL,	NULL,	'',	9,	NULL,	NULL,	1,	1.0000,	1.000,	'',	NULL,	0,	'',	0),
(32,	9,	'SW10009.6',	'',	2,	'',	0,	1,	75,	5,	0,	0.550,	0,	NULL,	NULL,	NULL,	'',	9,	NULL,	NULL,	1,	1.0000,	1.000,	'',	NULL,	0,	'',	0),
(33,	9,	'SW10009.7',	'',	2,	'',	0,	1,	75,	5,	0,	0.550,	0,	NULL,	NULL,	NULL,	'',	9,	NULL,	NULL,	1,	1.0000,	1.000,	'',	NULL,	0,	'',	0),
(34,	9,	'SW10009.8',	'',	1,	'',	0,	1,	75,	5,	0,	0.550,	0,	NULL,	NULL,	NULL,	'',	9,	NULL,	NULL,	1,	1.0000,	1.000,	'',	NULL,	0,	'',	0),
(35,	9,	'SW10009.9',	'',	2,	'',	0,	1,	75,	5,	0,	0.550,	0,	NULL,	NULL,	NULL,	'',	9,	NULL,	NULL,	1,	1.0000,	1.000,	'',	NULL,	0,	'',	0),
(36,	9,	'SW10009.10',	'',	2,	'',	0,	1,	75,	5,	0,	0.550,	0,	NULL,	NULL,	NULL,	'',	9,	NULL,	NULL,	1,	1.0000,	1.000,	'',	NULL,	0,	'',	0),
(37,	9,	'SW10009.11',	'',	2,	'',	0,	1,	75,	5,	0,	0.550,	0,	NULL,	NULL,	NULL,	'',	9,	NULL,	NULL,	1,	1.0000,	1.000,	'',	NULL,	0,	'',	0),
(38,	4,	'SW10004.1',	'',	2,	'',	0,	1,	10,	0,	0,	0.000,	0,	NULL,	NULL,	NULL,	'',	9,	NULL,	NULL,	1,	1.0000,	1.000,	'',	NULL,	0,	'',	0),
(39,	4,	'SW10004.2',	'',	1,	'',	0,	1,	10,	0,	0,	0.000,	0,	NULL,	NULL,	NULL,	'',	9,	NULL,	NULL,	1,	1.0000,	1.000,	'',	NULL,	0,	'',	0),
(40,	14,	'SW10014.1',	'',	2,	'',	0,	1,	100,	10,	0,	0.000,	0,	NULL,	NULL,	NULL,	'',	9,	NULL,	NULL,	1,	1.0000,	1.000,	'',	NULL,	0,	'',	0),
(41,	14,	'SW10014.2',	'',	2,	'',	0,	0,	100,	10,	0,	0.000,	0,	NULL,	NULL,	NULL,	'',	9,	NULL,	NULL,	1,	1.0000,	1.000,	'',	NULL,	0,	'',	0);

DROP TABLE IF EXISTS `s_articles_downloads`;
CREATE TABLE `s_articles_downloads` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `articleID` int unsigned NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `filename` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `size` double NOT NULL,
  PRIMARY KEY (`id`),
  KEY `articleID` (`articleID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_articles_downloads` (`id`, `articleID`, `description`, `filename`, `size`) VALUES
(3,	8,	'Socken Bild Datei',	'media/image/socken.jpg',	0);

DROP TABLE IF EXISTS `s_articles_downloads_attributes`;
CREATE TABLE `s_articles_downloads_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `downloadID` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `downloadID` (`downloadID`),
  CONSTRAINT `s_articles_downloads_attributes_ibfk_1` FOREIGN KEY (`downloadID`) REFERENCES `s_articles_downloads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_articles_esd`;
CREATE TABLE `s_articles_esd` (
  `id` int NOT NULL AUTO_INCREMENT,
  `articleID` int NOT NULL DEFAULT '0',
  `articledetailsID` int NOT NULL DEFAULT '0',
  `file` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `serials` int NOT NULL DEFAULT '0',
  `notification` int NOT NULL DEFAULT '0',
  `maxdownloads` int NOT NULL DEFAULT '0',
  `datum` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `articleID` (`articleID`),
  KEY `articledetailsID` (`articledetailsID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_articles_esd` (`id`, `articleID`, `articledetailsID`, `file`, `serials`, `notification`, `maxdownloads`, `datum`) VALUES
(2,	3,	3,	'ErsteSchritte.pdf',	0,	0,	0,	'2017-10-05 15:11:01');

DROP TABLE IF EXISTS `s_articles_esd_attributes`;
CREATE TABLE `s_articles_esd_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `esdID` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `esdID` (`esdID`),
  CONSTRAINT `s_articles_esd_attributes_ibfk_1` FOREIGN KEY (`esdID`) REFERENCES `s_articles_esd` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_articles_esd_serials`;
CREATE TABLE `s_articles_esd_serials` (
  `id` int NOT NULL AUTO_INCREMENT,
  `serialnumber` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `esdID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `esdID` (`esdID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_articles_img`;
CREATE TABLE `s_articles_img` (
  `id` int NOT NULL AUTO_INCREMENT,
  `articleID` int DEFAULT NULL,
  `img` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `main` int NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `position` int NOT NULL,
  `width` int NOT NULL,
  `height` int NOT NULL,
  `relations` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `extension` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `parent_id` int unsigned DEFAULT NULL,
  `article_detail_id` int unsigned DEFAULT NULL,
  `media_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `artikel_id` (`articleID`),
  KEY `article_detail_id` (`article_detail_id`),
  KEY `parent_id` (`parent_id`),
  KEY `media_id` (`media_id`),
  KEY `article_images_query` (`articleID`,`position`),
  KEY `article_cover_image_query` (`articleID`,`main`,`position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_articles_img` (`id`, `articleID`, `img`, `main`, `description`, `position`, `width`, `height`, `relations`, `extension`, `parent_id`, `article_detail_id`, `media_id`) VALUES
(1,	1,	'mobile',	1,	'',	1,	0,	0,	'',	'jpg',	NULL,	NULL,	8),
(2,	2,	'waschmaschine',	1,	'',	1,	0,	0,	'',	'jpg',	NULL,	NULL,	13),
(3,	3,	'download',	1,	'',	1,	0,	0,	'',	'jpg',	NULL,	NULL,	2),
(5,	5,	'shirt',	1,	'',	1,	0,	0,	'',	'jpg',	NULL,	NULL,	14),
(6,	6,	'handschuh',	1,	'',	1,	0,	0,	'',	'jpg',	NULL,	NULL,	4),
(7,	7,	'hemd',	1,	'',	1,	0,	0,	'',	'jpg',	NULL,	NULL,	6),
(9,	10,	'tube',	1,	'',	1,	0,	0,	'',	'jpg',	NULL,	NULL,	12),
(10,	11,	'brot',	1,	'',	1,	0,	0,	'',	'jpg',	NULL,	NULL,	1),
(11,	12,	'fisch',	1,	'',	1,	0,	0,	'',	'jpg',	NULL,	NULL,	3),
(12,	13,	'schokolade',	1,	'',	1,	0,	0,	'',	'jpg',	NULL,	NULL,	10),
(19,	5,	'shirt_red',	2,	'',	2,	0,	0,	'',	'jpg',	NULL,	NULL,	16),
(20,	5,	'shirt_blue',	2,	'',	3,	0,	0,	'',	'jpg',	NULL,	NULL,	15),
(24,	NULL,	NULL,	2,	'',	2,	0,	0,	'',	'jpg',	19,	16,	NULL),
(25,	NULL,	NULL,	2,	'',	2,	0,	0,	'',	'jpg',	19,	20,	NULL),
(26,	NULL,	NULL,	2,	'',	3,	0,	0,	'',	'jpg',	20,	21,	NULL),
(27,	NULL,	NULL,	2,	'',	3,	0,	0,	'',	'jpg',	20,	22,	NULL),
(28,	NULL,	NULL,	1,	'',	1,	0,	0,	'',	'jpg',	5,	23,	NULL),
(29,	NULL,	NULL,	1,	'',	1,	0,	0,	'',	'jpg',	5,	24,	NULL),
(30,	8,	'socken',	1,	'',	1,	0,	0,	'',	'jpg',	NULL,	NULL,	11),
(31,	9,	'hose_white',	1,	'',	1,	0,	0,	'',	'jpg',	NULL,	NULL,	19),
(32,	9,	'hose_red',	2,	'',	2,	0,	0,	'',	'jpg',	NULL,	NULL,	18),
(33,	9,	'hose_blue',	2,	'',	3,	0,	0,	'',	'jpg',	NULL,	NULL,	17),
(34,	NULL,	NULL,	1,	'',	1,	0,	0,	'',	'jpg',	31,	34,	NULL),
(35,	NULL,	NULL,	1,	'',	1,	0,	0,	'',	'jpg',	31,	35,	NULL),
(36,	NULL,	NULL,	1,	'',	1,	0,	0,	'',	'jpg',	31,	36,	NULL),
(37,	NULL,	NULL,	1,	'',	1,	0,	0,	'',	'jpg',	31,	37,	NULL),
(38,	NULL,	NULL,	2,	'',	2,	0,	0,	'',	'jpg',	32,	26,	NULL),
(39,	NULL,	NULL,	2,	'',	2,	0,	0,	'',	'jpg',	32,	27,	NULL),
(40,	NULL,	NULL,	2,	'',	2,	0,	0,	'',	'jpg',	32,	28,	NULL),
(41,	NULL,	NULL,	2,	'',	2,	0,	0,	'',	'jpg',	32,	29,	NULL),
(42,	NULL,	NULL,	2,	'',	3,	0,	0,	'',	'jpg',	33,	30,	NULL),
(43,	NULL,	NULL,	2,	'',	3,	0,	0,	'',	'jpg',	33,	31,	NULL),
(44,	NULL,	NULL,	2,	'',	3,	0,	0,	'',	'jpg',	33,	32,	NULL),
(45,	NULL,	NULL,	2,	'',	3,	0,	0,	'',	'jpg',	33,	33,	NULL),
(46,	4,	'rucksack_2',	1,	'',	1,	0,	0,	'',	'jpg',	NULL,	NULL,	22),
(47,	4,	'rucksack_1',	2,	'',	2,	0,	0,	'',	'jpg',	NULL,	NULL,	21),
(48,	4,	'rucksack_0',	2,	'',	3,	0,	0,	'',	'jpg',	NULL,	NULL,	20),
(49,	NULL,	NULL,	1,	'',	1,	0,	0,	'',	'jpg',	46,	39,	NULL),
(50,	NULL,	NULL,	2,	'',	2,	0,	0,	'',	'jpg',	47,	38,	NULL),
(51,	NULL,	NULL,	2,	'',	3,	0,	0,	'',	'jpg',	48,	4,	NULL),
(52,	14,	'handtasche_0',	1,	'',	1,	0,	0,	'',	'jpg',	NULL,	NULL,	23),
(53,	14,	'handtasche_1',	2,	'',	2,	0,	0,	'',	'jpg',	NULL,	NULL,	24),
(55,	NULL,	NULL,	1,	'',	1,	0,	0,	'',	'jpg',	52,	14,	NULL),
(56,	NULL,	NULL,	2,	'',	2,	0,	0,	'',	'jpg',	53,	40,	NULL),
(57,	NULL,	NULL,	2,	'',	3,	0,	0,	'',	'jpg',	54,	41,	NULL),
(58,	14,	'handtasche_0',	2,	'',	3,	0,	0,	'',	'jpg',	NULL,	NULL,	23),
(59,	NULL,	NULL,	2,	'',	3,	0,	0,	'',	'jpg',	58,	41,	NULL);

DROP TABLE IF EXISTS `s_articles_img_attributes`;
CREATE TABLE `s_articles_img_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `imageID` int DEFAULT NULL,
  `attribute1` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attribute2` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attribute3` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `imageID` (`imageID`),
  CONSTRAINT `s_articles_img_attributes_ibfk_1` FOREIGN KEY (`imageID`) REFERENCES `s_articles_img` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_articles_information`;
CREATE TABLE `s_articles_information` (
  `id` int NOT NULL AUTO_INCREMENT,
  `articleID` int NOT NULL DEFAULT '0',
  `description` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `link` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `target` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `hauptid` (`articleID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_articles_information` (`id`, `articleID`, `description`, `link`, `target`) VALUES
(1,	8,	'Information über Socken',	'http://www.shopwaredemo.de/',	'_blank');

DROP TABLE IF EXISTS `s_articles_information_attributes`;
CREATE TABLE `s_articles_information_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `informationID` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `informationID` (`informationID`),
  CONSTRAINT `s_articles_information_attributes_ibfk_1` FOREIGN KEY (`informationID`) REFERENCES `s_articles_information` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_articles_notification`;
CREATE TABLE `s_articles_notification` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `ordernumber` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `date` datetime NOT NULL,
  `mail` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `send` int unsigned NOT NULL,
  `language` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `shopLink` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_articles_notification_attributes`;
CREATE TABLE `s_articles_notification_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `notificationID` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `notificationID` (`notificationID`),
  CONSTRAINT `s_articles_notification_attributesibfk_1` FOREIGN KEY (`notificationID`) REFERENCES `s_articles_notification` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_articles_prices`;
CREATE TABLE `s_articles_prices` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `pricegroup` varchar(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `from` int unsigned NOT NULL,
  `to` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `articleID` int NOT NULL DEFAULT '0',
  `articledetailsID` int NOT NULL DEFAULT '0',
  `price` double NOT NULL DEFAULT '0',
  `pseudoprice` double DEFAULT NULL,
  `regulation_price` double DEFAULT NULL,
  `baseprice` double DEFAULT NULL,
  `percent` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `articleID` (`articleID`),
  KEY `articledetailsID` (`articledetailsID`),
  KEY `pricegroup_2` (`pricegroup`,`from`,`articledetailsID`),
  KEY `pricegroup` (`pricegroup`,`to`,`articledetailsID`),
  KEY `product_prices` (`articledetailsID`,`from`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_articles_prices` (`id`, `pricegroup`, `from`, `to`, `articleID`, `articledetailsID`, `price`, `pseudoprice`, `regulation_price`, `baseprice`, `percent`) VALUES
(1,	'EK',	1,	'beliebig',	1,	1,	386.51260504202,	0,	NULL,	NULL,	0.00),
(2,	'EK',	1,	'beliebig',	2,	2,	798.73949579832,	0,	NULL,	NULL,	0.00),
(3,	'EK',	1,	'beliebig',	3,	3,	0.83193277310924,	0,	NULL,	NULL,	0.00),
(6,	'EK',	1,	'beliebig',	6,	6,	12.605042016807,	0,	NULL,	NULL,	0.00),
(8,	'EK',	1,	'beliebig',	8,	8,	5.0336134453782,	0,	NULL,	NULL,	0.00),
(10,	'EK',	1,	'beliebig',	10,	10,	4.2016806722689,	0,	NULL,	NULL,	0.00),
(11,	'EK',	1,	'beliebig',	11,	11,	2.1008403361345,	0,	NULL,	NULL,	0.00),
(12,	'EK',	1,	'beliebig',	12,	12,	4.2016806722689,	0,	NULL,	NULL,	0.00),
(13,	'EK',	1,	'beliebig',	13,	13,	1.672268907563,	0,	NULL,	NULL,	0.00),
(18,	'EK',	1,	'beliebig',	7,	7,	16.798319327731,	0,	NULL,	NULL,	0.00),
(19,	'EK',	1,	'beliebig',	7,	17,	16.798319327731,	0,	NULL,	NULL,	0.00),
(20,	'EK',	1,	'beliebig',	7,	18,	16.798319327731,	0,	NULL,	NULL,	0.00),
(21,	'EK',	1,	'beliebig',	7,	19,	16.798319327731,	0,	NULL,	NULL,	0.00),
(22,	'EK',	1,	'beliebig',	5,	16,	16.798319327731,	0,	NULL,	NULL,	0.00),
(23,	'EK',	1,	'beliebig',	5,	20,	16.798319327731,	0,	NULL,	NULL,	0.00),
(24,	'EK',	1,	'beliebig',	5,	21,	16.798319327731,	0,	NULL,	NULL,	0.00),
(25,	'EK',	1,	'beliebig',	5,	22,	16.798319327731,	0,	NULL,	NULL,	0.00),
(26,	'EK',	1,	'beliebig',	5,	23,	16.798319327731,	0,	NULL,	NULL,	0.00),
(27,	'EK',	1,	'beliebig',	5,	24,	16.798319327731,	0,	NULL,	NULL,	0.00),
(31,	'EK',	1,	'beliebig',	9,	26,	21.008403361345,	0,	NULL,	NULL,	0.00),
(32,	'EK',	1,	'beliebig',	9,	27,	21.008403361345,	0,	NULL,	NULL,	0.00),
(33,	'EK',	1,	'beliebig',	9,	28,	21.008403361345,	0,	NULL,	NULL,	0.00),
(34,	'EK',	1,	'beliebig',	9,	29,	21.008403361345,	0,	NULL,	NULL,	0.00),
(35,	'EK',	1,	'beliebig',	9,	30,	21.008403361345,	0,	NULL,	NULL,	0.00),
(36,	'EK',	1,	'beliebig',	9,	31,	21.008403361345,	0,	NULL,	NULL,	0.00),
(37,	'EK',	1,	'beliebig',	9,	32,	21.008403361345,	0,	NULL,	NULL,	0.00),
(38,	'EK',	1,	'beliebig',	9,	33,	21.008403361345,	0,	NULL,	NULL,	0.00),
(39,	'EK',	1,	'beliebig',	9,	34,	21.008403361345,	0,	NULL,	NULL,	0.00),
(40,	'EK',	1,	'beliebig',	9,	35,	21.008403361345,	0,	NULL,	NULL,	0.00),
(41,	'EK',	1,	'beliebig',	9,	36,	21.008403361345,	0,	NULL,	NULL,	0.00),
(42,	'EK',	1,	'beliebig',	9,	37,	21.008403361345,	0,	NULL,	NULL,	0.00),
(43,	'EK',	1,	'beliebig',	4,	4,	16.806722689076,	0,	NULL,	NULL,	0.00),
(44,	'EK',	1,	'beliebig',	4,	38,	21.008403361345,	0,	NULL,	NULL,	0.00),
(45,	'EK',	1,	'beliebig',	4,	39,	25.210084033613,	42.016806722689,	NULL,	NULL,	0.00),
(46,	'EK',	1,	'beliebig',	14,	14,	218.47899159664,	0,	NULL,	NULL,	0.00),
(47,	'EK',	1,	'beliebig',	14,	40,	302.51260504202,	0,	NULL,	NULL,	0.00),
(48,	'EK',	1,	'beliebig',	14,	41,	386.54621848739,	0,	NULL,	NULL,	0.00);

DROP TABLE IF EXISTS `s_articles_prices_attributes`;
CREATE TABLE `s_articles_prices_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `priceID` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `priceID` (`priceID`),
  CONSTRAINT `s_articles_prices_attributes_ibfk_1` FOREIGN KEY (`priceID`) REFERENCES `s_articles_prices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_articles_relationships`;
CREATE TABLE `s_articles_relationships` (
  `id` int NOT NULL AUTO_INCREMENT,
  `articleID` int NOT NULL,
  `relatedarticle` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `articleID` (`articleID`,`relatedarticle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_articles_relationships` (`id`, `articleID`, `relatedarticle`) VALUES
(19,	10,	'12'),
(18,	12,	'10');

DROP TABLE IF EXISTS `s_articles_similar`;
CREATE TABLE `s_articles_similar` (
  `id` int NOT NULL AUTO_INCREMENT,
  `articleID` int NOT NULL,
  `relatedarticle` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `articleID` (`articleID`,`relatedarticle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_articles_similar` (`id`, `articleID`, `relatedarticle`) VALUES
(10,	5,	'7'),
(19,	7,	'5'),
(21,	11,	'12'),
(16,	12,	'11');

DROP TABLE IF EXISTS `s_articles_similar_shown_ro`;
CREATE TABLE `s_articles_similar_shown_ro` (
  `id` int NOT NULL AUTO_INCREMENT,
  `article_id` int unsigned NOT NULL,
  `related_article_id` int NOT NULL,
  `viewed` int unsigned NOT NULL DEFAULT '0',
  `init_date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `viewed_combination` (`article_id`,`related_article_id`),
  KEY `viewed` (`viewed`,`related_article_id`,`article_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_articles_supplier`;
CREATE TABLE `s_articles_supplier` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `img` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `link` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `meta_title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `meta_description` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `meta_keywords` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `changed` datetime NOT NULL DEFAULT '2019-12-06 10:19:52',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_articles_supplier` (`id`, `name`, `img`, `link`, `description`, `meta_title`, `meta_description`, `meta_keywords`, `changed`) VALUES
(1,	'Shopware Fashion',	'',	'',	'',	'',	'',	'',	'2017-10-05 14:59:03'),
(2,	'Shopware Food',	'',	'',	'',	'',	'',	'',	'2017-10-05 14:59:13'),
(3,	'Shopware Freetime',	'',	'',	'',	'',	'',	'',	'2017-10-05 14:59:40');

DROP TABLE IF EXISTS `s_articles_supplier_attributes`;
CREATE TABLE `s_articles_supplier_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `supplierID` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `supplierID` (`supplierID`),
  CONSTRAINT `s_articles_supplier_attributes_ibfk_1` FOREIGN KEY (`supplierID`) REFERENCES `s_articles_supplier` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_articles_top_seller_ro`;
CREATE TABLE `s_articles_top_seller_ro` (
  `id` int NOT NULL AUTO_INCREMENT,
  `article_id` int unsigned NOT NULL,
  `sales` int unsigned NOT NULL DEFAULT '0',
  `last_cleared` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `article_id` (`article_id`),
  KEY `sales` (`sales`),
  KEY `listing_query` (`sales`,`article_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_articles_translations`;
CREATE TABLE `s_articles_translations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `articleID` int NOT NULL,
  `languageID` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `keywords` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description_long` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description_clear` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `shippingtime` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `attr1` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `attr2` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `attr3` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `attr4` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `attr5` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `articleID` (`articleID`,`languageID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_articles_vote`;
CREATE TABLE `s_articles_vote` (
  `id` int NOT NULL AUTO_INCREMENT,
  `articleID` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `headline` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `comment` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `points` double NOT NULL,
  `datum` datetime NOT NULL,
  `active` int NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `answer` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `answer_date` datetime DEFAULT NULL,
  `shop_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `articleID` (`articleID`),
  KEY `get_articles_votes` (`articleID`,`active`,`datum`),
  KEY `vote_average` (`points`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_articles_vote` (`id`, `articleID`, `name`, `headline`, `comment`, `points`, `datum`, `active`, `email`, `answer`, `answer_date`, `shop_id`) VALUES
(1,	13,	'Shopware',	'Bewertung',	'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.',	5,	'2017-12-14 09:51:59',	1,	'S@shopware.com',	'',	'2017-12-14 09:55:11',	NULL);

DROP TABLE IF EXISTS `s_attribute_configuration`;
CREATE TABLE `s_attribute_configuration` (
  `id` int NOT NULL AUTO_INCREMENT,
  `table_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `column_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `column_type` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `default_value` varchar(500) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `position` int NOT NULL,
  `translatable` int NOT NULL,
  `display_in_backend` int NOT NULL,
  `custom` int NOT NULL,
  `help_text` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `support_text` varchar(500) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `label` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `entity` varchar(500) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `array_store` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `readonly` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `table_column_unique` (`table_name`,`column_name`),
  KEY `table_name` (`table_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_attribute_configuration` (`id`, `table_name`, `column_name`, `column_type`, `default_value`, `position`, `translatable`, `display_in_backend`, `custom`, `help_text`, `support_text`, `label`, `entity`, `array_store`, `readonly`) VALUES
(1,	's_articles_attributes',	'attr3',	'text',	NULL,	3,	1,	1,	0,	'Optionaler Kommentar',	'',	'Kommentar',	'NULL',	NULL,	0),
(2,	's_articles_attributes',	'attr1',	'text',	NULL,	1,	1,	1,	0,	'Freitext zur Anzeige auf der Detailseite',	'',	'Freitext-1',	'NULL',	NULL,	0),
(3,	's_articles_attributes',	'attr2',	'text',	NULL,	2,	1,	1,	0,	'Freitext zur Anzeige auf der Detailseite',	'',	'Freitext-2',	'NULL',	NULL,	0);

DROP TABLE IF EXISTS `s_benchmark_config`;
CREATE TABLE `s_benchmark_config` (
  `id` binary(16) NOT NULL,
  `shop_id` int NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `last_sent` datetime NOT NULL,
  `last_received` datetime NOT NULL,
  `last_order_id` int NOT NULL,
  `last_customer_id` int NOT NULL,
  `last_product_id` int NOT NULL,
  `last_analytics_id` int NOT NULL,
  `last_updated_orders_date` datetime DEFAULT NULL,
  `batch_size` int NOT NULL,
  `industry` int DEFAULT NULL,
  `type` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `response_token` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `cached_template` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `locked` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `shop_id` (`shop_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_billing_template`;
CREATE TABLE `s_billing_template` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `typ` mediumint NOT NULL,
  `group` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `desc` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `position` int NOT NULL,
  `show` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_billing_template` (`ID`, `name`, `value`, `typ`, `group`, `desc`, `position`, `show`) VALUES
(1,	'top',	'1cm',	2,	'margin',	'Seitenabstand oben',	0,	1),
(2,	'right',	'0.81cm',	2,	'margin',	'Seitenrand rechts',	0,	1),
(3,	'bottom',	'0cm',	2,	'margin',	'Seitenabstand unten',	0,	1),
(4,	'left',	'2.41cm',	2,	'margin',	'Seitenabstand links',	0,	1),
(5,	'top2',	'5cm',	2,	'header',	'Logohöhe',	6,	1),
(7,	'margin',	'1cm',	2,	'headline',	'Überschrift Abstand zur Anschrift',	0,	1),
(8,	'left',	'0cm',	2,	'sender',	'Abstand links (negativ Wert möglich)',	0,	1),
(9,	'footer',	'<table style=\"height: 90px;\" border=\"0\" width=\"100%\">\r\n<tbody>\r\n<tr valign=\"top\">\r\n<td style=\"width: 25%;\">\r\n<p><span style=\"font-size: xx-small;\">Demo GmbH</span></p>\r\n<p><span style=\"font-size: xx-small;\">Steuer-Nr <br />UST-ID: <br />Finanzamt </span><span style=\"font-size: xx-small;\">Musterstadt</span></p>\r\n</td>\r\n<td style=\"width: 25%;\">\r\n<p><span style=\"font-size: xx-small;\">Bankverbindung</span></p>\r\n<p><span style=\"font-size: xx-small;\">Sparkasse Musterstadt<br />BLZ: <br />Konto: </span></p>\r\n<span style=\"font-size: xx-small;\">aaaa<br /></span></td>\r\n<td style=\"width: 25%;\">\r\n<p><span style=\"font-size: xx-small;\">AGB<br /></span></p>\r\n<p><span style=\"font-size: xx-small;\">Gerichtsstand ist Musterstadt<br />Erf&uuml;llungsort Musterstadt<br />Gelieferte Ware bleibt bis zur vollst&auml;ndigen Bezahlung unser Eigentum</span></p>\r\n</td>\r\n<td style=\"width: 25%;\">\r\n<p><span style=\"font-size: xx-small;\">Gesch&auml;ftsf&uuml;hrer</span></p>\r\n<p><span style=\"font-size: xx-small;\">Max Mustermann</span></p>\r\n</td>\r\n</tr>\r\n</tbody>\r\n</table>',	1,	'footer',	'Fusszeile',	2,	1),
(13,	'right',	'<p><strong>Demo GmbH </strong><br /> Max Mustermann<br /> Stra&szlig;e 3<br /> 00000 Musterstadt<br /> Fon: 01234 / 56789<br /> Fax: 01234 / 56780<br />info@demo.de<br />www.demo.de</p>',	1,	'header',	'Briefkopf rechts',	9,	1),
(14,	'sender',	'Demo GmbH - Straße 3 - 00000 Musterstadt',	2,	'sender',	'Absender',	0,	1),
(15,	'left',	'100px',	2,	'footer',	'Abstand links',	0,	1),
(16,	'bottom',	'100px',	2,	'footer',	'Abstand unten',	1,	1),
(17,	'number',	'10',	2,	'content_middle',	'Anzahl angezeigter Postionen',	2,	1),
(18,	'text',	'',	1,	'content_middle',	'Freitext',	4,	1),
(19,	'height',	'12cm',	2,	'content_middle',	'Inhaltsabstand zum obigen Seitenrand',	0,	1),
(20,	'top',	'<p><img src=\"http://www.shopwaredemo.de/eMail_logo.jpg\" alt=\"\" width=\"393\" height=\"78\" /></p>',	1,	'header',	'Logo oben',	7,	1),
(21,	'top',	'1cm',	2,	'sender',	'Abstand unten zum Logo (negativ Wert möglich)',	0,	1),
(22,	'margin',	'2.2cm',	2,	'header',	'Abstand rechts (negativ Wert möglich)',	8,	1);

DROP TABLE IF EXISTS `s_blog`;
CREATE TABLE `s_blog` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `author_id` int DEFAULT NULL,
  `active` int NOT NULL,
  `short_description` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `views` int unsigned DEFAULT NULL,
  `display_date` datetime NOT NULL,
  `category_id` int unsigned DEFAULT NULL,
  `template` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `meta_keywords` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `meta_description` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `meta_title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `shop_ids` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `emotion_get_blog_entry` (`display_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_blog_assigned_articles`;
CREATE TABLE `s_blog_assigned_articles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `blog_id` int unsigned NOT NULL,
  `article_id` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `blog_id` (`blog_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_blog_attributes`;
CREATE TABLE `s_blog_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `blog_id` int unsigned DEFAULT NULL,
  `attribute1` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attribute2` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attribute3` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attribute4` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attribute5` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attribute6` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `blog_id` (`blog_id`),
  CONSTRAINT `s_blog_attributes_ibfk_1` FOREIGN KEY (`blog_id`) REFERENCES `s_blog` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_blog_comments`;
CREATE TABLE `s_blog_comments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `blog_id` int unsigned DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `headline` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `comment` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `creation_date` datetime NOT NULL,
  `active` int NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `points` double NOT NULL,
  `shop_id` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_blog_media`;
CREATE TABLE `s_blog_media` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `blog_id` int unsigned NOT NULL,
  `media_id` int unsigned NOT NULL,
  `preview` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `blogID` (`blog_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_blog_tags`;
CREATE TABLE `s_blog_tags` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `blog_id` int unsigned DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `blogID` (`blog_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_campaigns_articles`;
CREATE TABLE `s_campaigns_articles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `parentID` int NOT NULL DEFAULT '0',
  `articleordernumber` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '0',
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `type` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `position` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_campaigns_banner`;
CREATE TABLE `s_campaigns_banner` (
  `id` int NOT NULL AUTO_INCREMENT,
  `parentID` int NOT NULL,
  `image` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `link` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `linkTarget` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_campaigns_containers`;
CREATE TABLE `s_campaigns_containers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `promotionID` int DEFAULT NULL,
  `value` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `position` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_campaigns_groups`;
CREATE TABLE `s_campaigns_groups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_campaigns_groups` (`id`, `name`) VALUES
(1,	'Newsletter-Empfänger');

DROP TABLE IF EXISTS `s_campaigns_html`;
CREATE TABLE `s_campaigns_html` (
  `id` int NOT NULL AUTO_INCREMENT,
  `parentID` int DEFAULT NULL,
  `headline` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `html` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `image` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `link` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `alignment` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_campaigns_links`;
CREATE TABLE `s_campaigns_links` (
  `id` int NOT NULL AUTO_INCREMENT,
  `parentID` int NOT NULL DEFAULT '0',
  `description` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `link` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `target` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `position` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_campaigns_logs`;
CREATE TABLE `s_campaigns_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` datetime DEFAULT NULL,
  `mailingID` int NOT NULL DEFAULT '0',
  `email` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `articleID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_campaigns_mailaddresses`;
CREATE TABLE `s_campaigns_mailaddresses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `customer` int NOT NULL,
  `groupID` int NOT NULL,
  `email` varchar(90) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `lastmailing` int NOT NULL,
  `lastread` int NOT NULL,
  `added` datetime DEFAULT NULL,
  `double_optin_confirmed` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `groupID` (`groupID`),
  KEY `email` (`email`),
  KEY `lastmailing` (`lastmailing`),
  KEY `lastread` (`lastread`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_campaigns_maildata`;
CREATE TABLE `s_campaigns_maildata` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `groupID` int unsigned NOT NULL,
  `salutation` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `firstname` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `lastname` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `street` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `zipcode` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `city` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `added` datetime NOT NULL,
  `double_optin_confirmed` datetime DEFAULT NULL,
  `deleted` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`,`groupID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_campaigns_mailings`;
CREATE TABLE `s_campaigns_mailings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` date DEFAULT NULL,
  `groups` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `subject` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `sendermail` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `sendername` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `plaintext` int NOT NULL,
  `templateID` int NOT NULL DEFAULT '0',
  `languageID` int NOT NULL,
  `status` int NOT NULL DEFAULT '0',
  `locked` datetime DEFAULT NULL,
  `recipients` int NOT NULL,
  `read` int NOT NULL DEFAULT '0',
  `clicked` int NOT NULL DEFAULT '0',
  `customergroup` varchar(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `publish` int unsigned NOT NULL,
  `timed_delivery` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_campaigns_positions`;
CREATE TABLE `s_campaigns_positions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `promotionID` int NOT NULL DEFAULT '0',
  `containerID` int NOT NULL DEFAULT '0',
  `position` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_campaigns_sender`;
CREATE TABLE `s_campaigns_sender` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_campaigns_sender` (`id`, `email`, `name`) VALUES
(1,	'testing@shopware.com',	'Newsletter Absender');

DROP TABLE IF EXISTS `s_campaigns_templates`;
CREATE TABLE `s_campaigns_templates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `path` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_campaigns_templates` (`id`, `path`, `description`) VALUES
(1,	'index.tpl',	'Standardtemplate'),
(2,	'indexh.tpl',	'Händler');

DROP TABLE IF EXISTS `s_categories`;
CREATE TABLE `s_categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `parent` int unsigned DEFAULT NULL,
  `path` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `description` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `position` int unsigned DEFAULT '0',
  `left` int unsigned NOT NULL,
  `right` int unsigned NOT NULL,
  `level` int unsigned NOT NULL,
  `added` datetime NOT NULL,
  `changed` datetime NOT NULL,
  `metakeywords` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `metadescription` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `cmsheadline` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `cmstext` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `template` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `active` int NOT NULL,
  `blog` int NOT NULL,
  `external` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `hidefilter` int NOT NULL,
  `hidetop` int NOT NULL,
  `mediaID` int unsigned DEFAULT NULL,
  `product_box_layout` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `meta_title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `stream_id` int unsigned DEFAULT NULL,
  `hide_sortings` int NOT NULL DEFAULT '0',
  `sorting_ids` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `facet_ids` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `external_target` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT '',
  `shops` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `parent` (`parent`),
  KEY `description` (`description`),
  KEY `position` (`position`),
  KEY `left` (`left`,`right`),
  KEY `level` (`level`),
  KEY `active_query_builder` (`parent`,`position`,`id`),
  KEY `stream_id` (`stream_id`),
  CONSTRAINT `s_categories_fk_stream_id` FOREIGN KEY (`stream_id`) REFERENCES `s_product_streams` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_categories` (`id`, `parent`, `path`, `description`, `position`, `left`, `right`, `level`, `added`, `changed`, `metakeywords`, `metadescription`, `cmsheadline`, `cmstext`, `template`, `active`, `blog`, `external`, `hidefilter`, `hidetop`, `mediaID`, `product_box_layout`, `meta_title`, `stream_id`, `hide_sortings`, `sorting_ids`, `facet_ids`, `external_target`, `shops`) VALUES
(1,	NULL,	NULL,	'Root',	0,	1,	6,	0,	'2012-08-27 22:28:52',	'2012-08-27 22:28:52',	NULL,	NULL,	NULL,	NULL,	NULL,	1,	0,	NULL,	0,	0,	0,	NULL,	NULL,	NULL,	0,	NULL,	NULL,	'',	NULL),
(3,	1,	NULL,	'Deutsch',	0,	2,	3,	1,	'2012-08-27 22:28:52',	'2012-08-27 22:28:52',	NULL,	'',	'',	'',	NULL,	1,	0,	'',	0,	0,	NULL,	NULL,	'',	NULL,	0,	NULL,	NULL,	'',	NULL),
(5,	3,	'|3|',	'Lebensmittel',	NULL,	0,	0,	0,	'2017-10-05 14:56:02',	'2017-10-05 14:56:02',	NULL,	'',	'Lorem Ipsum',	'<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.</p>',	NULL,	1,	0,	'',	0,	0,	1,	NULL,	'',	NULL,	0,	NULL,	NULL,	'',	NULL),
(6,	3,	'|3|',	'Bekleidung',	NULL,	0,	0,	0,	'2017-10-05 14:57:15',	'2017-10-05 14:57:15',	NULL,	'',	'Lorem ipsum',	'<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.</p>',	NULL,	1,	0,	'',	0,	0,	14,	NULL,	'',	NULL,	0,	NULL,	NULL,	'',	NULL),
(7,	3,	'|3|',	'Freizeit & Elektro',	NULL,	0,	0,	0,	'2017-10-05 14:57:24',	'2017-10-05 14:57:24',	NULL,	'',	'Lorem ipsum',	'<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.</p>',	NULL,	1,	0,	'',	0,	0,	8,	NULL,	'',	NULL,	0,	NULL,	NULL,	'',	NULL),
(8,	5,	'|5|3|',	'Backwaren',	NULL,	0,	0,	0,	'2017-10-10 09:10:16',	'2017-10-10 09:10:16',	NULL,	'',	'Lorem ipsum',	'<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.</p>',	NULL,	1,	0,	'',	0,	0,	1,	NULL,	'',	NULL,	0,	NULL,	NULL,	'',	NULL),
(9,	5,	'|5|3|',	'Fisch',	NULL,	0,	0,	0,	'2017-10-10 09:10:25',	'2017-10-10 09:10:25',	NULL,	'',	'Lorem ipsum',	'<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.</p>',	NULL,	1,	0,	'',	0,	0,	3,	NULL,	'',	NULL,	0,	NULL,	NULL,	'',	NULL),
(10,	5,	'|5|3|',	'Süßes',	NULL,	0,	0,	0,	'2017-10-10 09:10:41',	'2017-10-10 09:10:41',	NULL,	'',	'Lorem ipsum',	'<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.</p>',	NULL,	1,	0,	'',	0,	0,	10,	NULL,	'',	NULL,	0,	NULL,	NULL,	'',	NULL),
(11,	6,	'|6|3|',	'Damen',	NULL,	0,	0,	0,	'2017-10-10 09:10:57',	'2017-10-10 09:10:57',	NULL,	'',	'Lorem ipsum',	'<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.</p>',	NULL,	1,	0,	'',	0,	0,	23,	NULL,	'',	NULL,	0,	NULL,	NULL,	'',	NULL),
(12,	6,	'|6|3|',	'Herren',	NULL,	0,	0,	0,	'2017-10-10 09:11:00',	'2017-10-10 09:11:00',	NULL,	'',	'Lorem ipsum',	'<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.</p>',	NULL,	1,	0,	'',	0,	0,	6,	NULL,	'',	NULL,	0,	NULL,	NULL,	'',	NULL);

DROP TABLE IF EXISTS `s_categories_attributes`;
CREATE TABLE `s_categories_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `categoryID` int unsigned DEFAULT NULL,
  `attribute1` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attribute2` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attribute3` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attribute4` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attribute5` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attribute6` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categoryID` (`categoryID`),
  CONSTRAINT `s_categories_attributes_ibfk_1` FOREIGN KEY (`categoryID`) REFERENCES `s_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_categories_avoid_customergroups`;
CREATE TABLE `s_categories_avoid_customergroups` (
  `categoryID` int NOT NULL,
  `customergroupID` int NOT NULL,
  PRIMARY KEY (`categoryID`,`customergroupID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_categories_manual_sorting`;
CREATE TABLE `s_categories_manual_sorting` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_id` int NOT NULL,
  `product_id` int NOT NULL,
  `position` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_id_product_id` (`category_id`,`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_cms_static`;
CREATE TABLE `s_cms_static` (
  `id` int NOT NULL AUTO_INCREMENT,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `tpl1variable` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `tpl1path` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `tpl2variable` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `tpl2path` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `tpl3variable` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `tpl3path` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `html` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `grouping` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `position` int NOT NULL,
  `link` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `target` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `parentID` int NOT NULL DEFAULT '0',
  `page_title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `meta_keywords` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `meta_description` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `changed` datetime NOT NULL DEFAULT '2019-12-06 10:19:52',
  `shop_ids` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `get_menu` (`position`,`description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_cms_static` (`id`, `active`, `tpl1variable`, `tpl1path`, `tpl2variable`, `tpl2path`, `tpl3variable`, `tpl3path`, `description`, `html`, `grouping`, `position`, `link`, `target`, `parentID`, `page_title`, `meta_keywords`, `meta_description`, `changed`, `shop_ids`) VALUES
(1,	1,	'',	'',	'',	'',	'',	'',	'Kontakt',	'<p>F&uuml;gen Sie hier Ihre Kontaktdaten ein</p>',	'left|bottom',	1,	'shopware.php?sViewport=ticket&sFid=5',	'_self',	0,	'',	'',	'',	'2019-12-06 10:19:52',	NULL),
(2,	1,	'',	'',	'',	'',	'',	'',	'Hilfe / Support',	'<h2>Isericordaliter Occatio ter aut Aliusmodi vel Fugo redigo, iam ops tam Plaga consulo sui ymo Zephyr humilitas.</h2>\n<p>Isericordaliter Occatio ter aut Aliusmodi vel Fugo redigo, iam ops tam Plaga consulo sui ymo Zephyr humilitas. Ivi praebalteata Occumbo congruens seco, lea qui se surculus sed abhinc praejudico in forix curo. Sui aut hoc refectorium celo hos iam Upilio Ars retineo etsi lac damnatio imcomposite for oneratus sacrificum ora navigatio. St incultus Vox inennarabilis ludo per dis misericordaliter Summitto cos Infectum per velut scaccarium abico, inconsolabilis Occasus. Ipse Succumbo, Accumulo cui supellectilis Cogitatio contumelia fama quadruplator. Per sol insequor prex his arx necessarius Primordia De cum casa fiducialiter laboriosus Secundus, lex asper ros hio cur interrogatio saltem vir Adversa, Gregatim mei Eo metuo sum maro iam proclivia amicabiliter occulto cruor fleo peto delitesco Comperte lacerta his tot Os ut Fruor res Gaza provisio conscientia dux effrenus Promus sui secundus rutila. Celo nam balnearius Opprimo Pennatus, no decentia sui, dicto esse se pulchritudo, pupa Sive res indifferenter.</p>\n<p>Captivo pala pro de tandem Singulus labor, determino cui Ingurgito quo Ico pax ethologus praetorgredior internuntius. Ops foveo Huius dux respublica his animadverto dolus imperterritus. Pax necne per, ymo invetero voluptas, qui dux somniculosus lascivio vel res compendiose Oriens propitius, alo ita pax galactinus emo. Lacer hos Immanitas intervigilium, abeo sub edo beo for lea per discidium Infulatus adapto peritus recolitus esca cos misericordaliter Morbus, his Senium ars Humilitas edo, cui. Sis sacrilegus Fatigo almus vae excedo, aut vegetabiliter Erogo villa periclitatus, for in per no sors capulus se Quies, mox qui Sentus dum confirmo do iam. Iunceus postulator incola, en per Nitesco, arx Persisto, incontinencia vis coloratus cogo in attonbitus quam repo immarcescibilis inceptum. Ego Vena series sudo ac Nitidus. Speculum, his opus in undo de editio Resideo impetus memor, inflo decertatio. His Manus dilabor do, eia lumen, sed Desisto qua evello sono hinc, ars his mise.</p>\n<p>Isericordaliter Occatio ter aut Aliusmodi vel Fugo redigo, iam ops tam Plaga consulo sui ymo Zephyr humilitas. Ivi praebalteata Occumbo congruens seco, lea qui se surculus sed abhinc praejudico in forix curo. Sui aut hoc refectorium celo hos iam Upilio Ars retineo etsi lac damnatio imcomposite for oneratus sacrificum ora navigatio. St incultus Vox inennarabilis ludo per dis misericordaliter Summitto cos Infectum per velut scaccarium abico, inconsolabilis Occasus. Ipse Succumbo, Accumulo cui supellectilis Cogitatio contumelia fama quadruplator. Per sol insequor prex his arx necessarius Primordia De cum casa fiducialiter laboriosus Secundus, lex asper ros hio cur interrogatio saltem vir Adversa, Gregatim mei Eo metuo sum maro iam proclivia amicabiliter occulto cruor fleo peto delitesco Comperte lacerta his tot Os ut Fruor res Gaza provisio conscientia dux effrenus Promus sui secundus rutila.</p>\n<p>Celo nam balnearius Opprimo Pennatus, no decentia sui, dicto esse se pulchritudo, pupa Sive res indifferenter. Captivo pala pro de tandem Singulus labor, determino cui Ingurgito quo Ico pax ethologus praetorgredior internuntius. Ops foveo Huius dux respublica his animadverto dolus imperterritus. Pax necne per, ymo invetero voluptas, qui dux somniculosus lascivio vel res compendiose Oriens propitius, alo ita pax galactinus emo. Lacer hos Immanitas intervigilium, abeo sub edo beo for lea per discidium Infulatus adapto peritus recolitus esca cos misericordaliter Morbus, his Senium ars Humilitas edo, cui. Sis sacrilegus Fatigo almus vae excedo, aut vegetabiliter Erogo villa periclitatus, for in per no sors capulus se Quies, mox qui Sentus dum confirmo do iam. Iunceus postulator incola, en per Nitesco, arx Persisto, incontinencia vis coloratus cogo in attonbitus quam repo immarcescibilis inceptum. Ego Vena series sudo ac Nitidus. Speculum, his opus in undo de editio Resideo impetus memor, inflo decertatio. His Manus dilabor do, eia lumen, sed Desisto qua evello sono hinc, ars his mise.</p>',	'left',	1,	'',	'',	0,	'',	'',	'',	'2019-12-06 10:19:52',	NULL),
(3,	1,	'',	'',	'',	'',	'',	'',	'Impressum',	'<h2>Isericordaliter Occatio ter aut Aliusmodi vel Fugo redigo, iam ops tam Plaga consulo sui ymo Zephyr humilitas.</h2>\n<p>Isericordaliter Occatio ter aut Aliusmodi vel Fugo redigo, iam ops tam Plaga consulo sui ymo Zephyr humilitas. Ivi praebalteata Occumbo congruens seco, lea qui se surculus sed abhinc praejudico in forix curo. Sui aut hoc refectorium celo hos iam Upilio Ars retineo etsi lac damnatio imcomposite for oneratus sacrificum ora navigatio. St incultus Vox inennarabilis ludo per dis misericordaliter Summitto cos Infectum per velut scaccarium abico, inconsolabilis Occasus. Ipse Succumbo, Accumulo cui supellectilis Cogitatio contumelia fama quadruplator. Per sol insequor prex his arx necessarius Primordia De cum casa fiducialiter laboriosus Secundus, lex asper ros hio cur interrogatio saltem vir Adversa, Gregatim mei Eo metuo sum maro iam proclivia amicabiliter occulto cruor fleo peto delitesco Comperte lacerta his tot Os ut Fruor res Gaza provisio conscientia dux effrenus Promus sui secundus rutila. Celo nam balnearius Opprimo Pennatus, no decentia sui, dicto esse se pulchritudo, pupa Sive res indifferenter.</p>\n<p>Captivo pala pro de tandem Singulus labor, determino cui Ingurgito quo Ico pax ethologus praetorgredior internuntius. Ops foveo Huius dux respublica his animadverto dolus imperterritus. Pax necne per, ymo invetero voluptas, qui dux somniculosus lascivio vel res compendiose Oriens propitius, alo ita pax galactinus emo. Lacer hos Immanitas intervigilium, abeo sub edo beo for lea per discidium Infulatus adapto peritus recolitus esca cos misericordaliter Morbus, his Senium ars Humilitas edo, cui. Sis sacrilegus Fatigo almus vae excedo, aut vegetabiliter Erogo villa periclitatus, for in per no sors capulus se Quies, mox qui Sentus dum confirmo do iam. Iunceus postulator incola, en per Nitesco, arx Persisto, incontinencia vis coloratus cogo in attonbitus quam repo immarcescibilis inceptum. Ego Vena series sudo ac Nitidus. Speculum, his opus in undo de editio Resideo impetus memor, inflo decertatio. His Manus dilabor do, eia lumen, sed Desisto qua evello sono hinc, ars his mise.</p>\n<p>Isericordaliter Occatio ter aut Aliusmodi vel Fugo redigo, iam ops tam Plaga consulo sui ymo Zephyr humilitas. Ivi praebalteata Occumbo congruens seco, lea qui se surculus sed abhinc praejudico in forix curo. Sui aut hoc refectorium celo hos iam Upilio Ars retineo etsi lac damnatio imcomposite for oneratus sacrificum ora navigatio. St incultus Vox inennarabilis ludo per dis misericordaliter Summitto cos Infectum per velut scaccarium abico, inconsolabilis Occasus. Ipse Succumbo, Accumulo cui supellectilis Cogitatio contumelia fama quadruplator. Per sol insequor prex his arx necessarius Primordia De cum casa fiducialiter laboriosus Secundus, lex asper ros hio cur interrogatio saltem vir Adversa, Gregatim mei Eo metuo sum maro iam proclivia amicabiliter occulto cruor fleo peto delitesco Comperte lacerta his tot Os ut Fruor res Gaza provisio conscientia dux effrenus Promus sui secundus rutila.</p>\n<p>Celo nam balnearius Opprimo Pennatus, no decentia sui, dicto esse se pulchritudo, pupa Sive res indifferenter. Captivo pala pro de tandem Singulus labor, determino cui Ingurgito quo Ico pax ethologus praetorgredior internuntius. Ops foveo Huius dux respublica his animadverto dolus imperterritus. Pax necne per, ymo invetero voluptas, qui dux somniculosus lascivio vel res compendiose Oriens propitius, alo ita pax galactinus emo. Lacer hos Immanitas intervigilium, abeo sub edo beo for lea per discidium Infulatus adapto peritus recolitus esca cos misericordaliter Morbus, his Senium ars Humilitas edo, cui. Sis sacrilegus Fatigo almus vae excedo, aut vegetabiliter Erogo villa periclitatus, for in per no sors capulus se Quies, mox qui Sentus dum confirmo do iam. Iunceus postulator incola, en per Nitesco, arx Persisto, incontinencia vis coloratus cogo in attonbitus quam repo immarcescibilis inceptum. Ego Vena series sudo ac Nitidus. Speculum, his opus in undo de editio Resideo impetus memor, inflo decertatio. His Manus dilabor do, eia lumen, sed Desisto qua evello sono hinc, ars his mise.</p>',	'left|bottom2',	20,	'',	'',	0,	'',	'',	'',	'2019-12-06 10:19:52',	NULL),
(4,	1,	'',	'',	'',	'',	'',	'',	'AGB',	'<h2>Isericordaliter Occatio ter aut Aliusmodi vel Fugo redigo, iam ops tam Plaga consulo sui ymo Zephyr humilitas.</h2>\n<p>Isericordaliter Occatio ter aut Aliusmodi vel Fugo redigo, iam ops tam Plaga consulo sui ymo Zephyr humilitas. Ivi praebalteata Occumbo congruens seco, lea qui se surculus sed abhinc praejudico in forix curo. Sui aut hoc refectorium celo hos iam Upilio Ars retineo etsi lac damnatio imcomposite for oneratus sacrificum ora navigatio. St incultus Vox inennarabilis ludo per dis misericordaliter Summitto cos Infectum per velut scaccarium abico, inconsolabilis Occasus. Ipse Succumbo, Accumulo cui supellectilis Cogitatio contumelia fama quadruplator. Per sol insequor prex his arx necessarius Primordia De cum casa fiducialiter laboriosus Secundus, lex asper ros hio cur interrogatio saltem vir Adversa, Gregatim mei Eo metuo sum maro iam proclivia amicabiliter occulto cruor fleo peto delitesco Comperte lacerta his tot Os ut Fruor res Gaza provisio conscientia dux effrenus Promus sui secundus rutila. Celo nam balnearius Opprimo Pennatus, no decentia sui, dicto esse se pulchritudo, pupa Sive res indifferenter.</p>\n<p>Captivo pala pro de tandem Singulus labor, determino cui Ingurgito quo Ico pax ethologus praetorgredior internuntius. Ops foveo Huius dux respublica his animadverto dolus imperterritus. Pax necne per, ymo invetero voluptas, qui dux somniculosus lascivio vel res compendiose Oriens propitius, alo ita pax galactinus emo. Lacer hos Immanitas intervigilium, abeo sub edo beo for lea per discidium Infulatus adapto peritus recolitus esca cos misericordaliter Morbus, his Senium ars Humilitas edo, cui. Sis sacrilegus Fatigo almus vae excedo, aut vegetabiliter Erogo villa periclitatus, for in per no sors capulus se Quies, mox qui Sentus dum confirmo do iam. Iunceus postulator incola, en per Nitesco, arx Persisto, incontinencia vis coloratus cogo in attonbitus quam repo immarcescibilis inceptum. Ego Vena series sudo ac Nitidus. Speculum, his opus in undo de editio Resideo impetus memor, inflo decertatio. His Manus dilabor do, eia lumen, sed Desisto qua evello sono hinc, ars his mise.</p>\n<p>Isericordaliter Occatio ter aut Aliusmodi vel Fugo redigo, iam ops tam Plaga consulo sui ymo Zephyr humilitas. Ivi praebalteata Occumbo congruens seco, lea qui se surculus sed abhinc praejudico in forix curo. Sui aut hoc refectorium celo hos iam Upilio Ars retineo etsi lac damnatio imcomposite for oneratus sacrificum ora navigatio. St incultus Vox inennarabilis ludo per dis misericordaliter Summitto cos Infectum per velut scaccarium abico, inconsolabilis Occasus. Ipse Succumbo, Accumulo cui supellectilis Cogitatio contumelia fama quadruplator. Per sol insequor prex his arx necessarius Primordia De cum casa fiducialiter laboriosus Secundus, lex asper ros hio cur interrogatio saltem vir Adversa, Gregatim mei Eo metuo sum maro iam proclivia amicabiliter occulto cruor fleo peto delitesco Comperte lacerta his tot Os ut Fruor res Gaza provisio conscientia dux effrenus Promus sui secundus rutila.</p>\n<p>Celo nam balnearius Opprimo Pennatus, no decentia sui, dicto esse se pulchritudo, pupa Sive res indifferenter. Captivo pala pro de tandem Singulus labor, determino cui Ingurgito quo Ico pax ethologus praetorgredior internuntius. Ops foveo Huius dux respublica his animadverto dolus imperterritus. Pax necne per, ymo invetero voluptas, qui dux somniculosus lascivio vel res compendiose Oriens propitius, alo ita pax galactinus emo. Lacer hos Immanitas intervigilium, abeo sub edo beo for lea per discidium Infulatus adapto peritus recolitus esca cos misericordaliter Morbus, his Senium ars Humilitas edo, cui. Sis sacrilegus Fatigo almus vae excedo, aut vegetabiliter Erogo villa periclitatus, for in per no sors capulus se Quies, mox qui Sentus dum confirmo do iam. Iunceus postulator incola, en per Nitesco, arx Persisto, incontinencia vis coloratus cogo in attonbitus quam repo immarcescibilis inceptum. Ego Vena series sudo ac Nitidus. Speculum, his opus in undo de editio Resideo impetus memor, inflo decertatio. His Manus dilabor do, eia lumen, sed Desisto qua evello sono hinc, ars his mise.</p>',	'left|bottom',	18,	'',	'',	0,	'',	'',	'',	'2019-12-06 10:19:52',	NULL),
(6,	1,	'',	'',	'',	'',	'',	'',	'Versand und Zahlungsbedingungen',	'<h2>Isericordaliter Occatio ter aut Aliusmodi vel Fugo redigo, iam ops tam Plaga consulo sui ymo Zephyr humilitas.</h2>\n<p>Isericordaliter Occatio ter aut Aliusmodi vel Fugo redigo, iam ops tam Plaga consulo sui ymo Zephyr humilitas. Ivi praebalteata Occumbo congruens seco, lea qui se surculus sed abhinc praejudico in forix curo. Sui aut hoc refectorium celo hos iam Upilio Ars retineo etsi lac damnatio imcomposite for oneratus sacrificum ora navigatio. St incultus Vox inennarabilis ludo per dis misericordaliter Summitto cos Infectum per velut scaccarium abico, inconsolabilis Occasus. Ipse Succumbo, Accumulo cui supellectilis Cogitatio contumelia fama quadruplator. Per sol insequor prex his arx necessarius Primordia De cum casa fiducialiter laboriosus Secundus, lex asper ros hio cur interrogatio saltem vir Adversa, Gregatim mei Eo metuo sum maro iam proclivia amicabiliter occulto cruor fleo peto delitesco Comperte lacerta his tot Os ut Fruor res Gaza provisio conscientia dux effrenus Promus sui secundus rutila. Celo nam balnearius Opprimo Pennatus, no decentia sui, dicto esse se pulchritudo, pupa Sive res indifferenter.</p>\n<p>Captivo pala pro de tandem Singulus labor, determino cui Ingurgito quo Ico pax ethologus praetorgredior internuntius. Ops foveo Huius dux respublica his animadverto dolus imperterritus. Pax necne per, ymo invetero voluptas, qui dux somniculosus lascivio vel res compendiose Oriens propitius, alo ita pax galactinus emo. Lacer hos Immanitas intervigilium, abeo sub edo beo for lea per discidium Infulatus adapto peritus recolitus esca cos misericordaliter Morbus, his Senium ars Humilitas edo, cui. Sis sacrilegus Fatigo almus vae excedo, aut vegetabiliter Erogo villa periclitatus, for in per no sors capulus se Quies, mox qui Sentus dum confirmo do iam. Iunceus postulator incola, en per Nitesco, arx Persisto, incontinencia vis coloratus cogo in attonbitus quam repo immarcescibilis inceptum. Ego Vena series sudo ac Nitidus. Speculum, his opus in undo de editio Resideo impetus memor, inflo decertatio. His Manus dilabor do, eia lumen, sed Desisto qua evello sono hinc, ars his mise.</p>\n<p>Isericordaliter Occatio ter aut Aliusmodi vel Fugo redigo, iam ops tam Plaga consulo sui ymo Zephyr humilitas. Ivi praebalteata Occumbo congruens seco, lea qui se surculus sed abhinc praejudico in forix curo. Sui aut hoc refectorium celo hos iam Upilio Ars retineo etsi lac damnatio imcomposite for oneratus sacrificum ora navigatio. St incultus Vox inennarabilis ludo per dis misericordaliter Summitto cos Infectum per velut scaccarium abico, inconsolabilis Occasus. Ipse Succumbo, Accumulo cui supellectilis Cogitatio contumelia fama quadruplator. Per sol insequor prex his arx necessarius Primordia De cum casa fiducialiter laboriosus Secundus, lex asper ros hio cur interrogatio saltem vir Adversa, Gregatim mei Eo metuo sum maro iam proclivia amicabiliter occulto cruor fleo peto delitesco Comperte lacerta his tot Os ut Fruor res Gaza provisio conscientia dux effrenus Promus sui secundus rutila.</p>\n<p>Celo nam balnearius Opprimo Pennatus, no decentia sui, dicto esse se pulchritudo, pupa Sive res indifferenter. Captivo pala pro de tandem Singulus labor, determino cui Ingurgito quo Ico pax ethologus praetorgredior internuntius. Ops foveo Huius dux respublica his animadverto dolus imperterritus. Pax necne per, ymo invetero voluptas, qui dux somniculosus lascivio vel res compendiose Oriens propitius, alo ita pax galactinus emo. Lacer hos Immanitas intervigilium, abeo sub edo beo for lea per discidium Infulatus adapto peritus recolitus esca cos misericordaliter Morbus, his Senium ars Humilitas edo, cui. Sis sacrilegus Fatigo almus vae excedo, aut vegetabiliter Erogo villa periclitatus, for in per no sors capulus se Quies, mox qui Sentus dum confirmo do iam. Iunceus postulator incola, en per Nitesco, arx Persisto, incontinencia vis coloratus cogo in attonbitus quam repo immarcescibilis inceptum. Ego Vena series sudo ac Nitidus. Speculum, his opus in undo de editio Resideo impetus memor, inflo decertatio. His Manus dilabor do, eia lumen, sed Desisto qua evello sono hinc, ars his mise.</p>',	'left|bottom',	3,	'',	'',	0,	'',	'',	'',	'2019-12-06 10:19:52',	NULL),
(7,	1,	'',	'',	'',	'',	'',	'',	'Datenschutz',	'<h2>Isericordaliter Occatio ter aut Aliusmodi vel Fugo redigo, iam ops tam Plaga consulo sui ymo Zephyr humilitas.</h2>\n<p>Isericordaliter Occatio ter aut Aliusmodi vel Fugo redigo, iam ops tam Plaga consulo sui ymo Zephyr humilitas. Ivi praebalteata Occumbo congruens seco, lea qui se surculus sed abhinc praejudico in forix curo. Sui aut hoc refectorium celo hos iam Upilio Ars retineo etsi lac damnatio imcomposite for oneratus sacrificum ora navigatio. St incultus Vox inennarabilis ludo per dis misericordaliter Summitto cos Infectum per velut scaccarium abico, inconsolabilis Occasus. Ipse Succumbo, Accumulo cui supellectilis Cogitatio contumelia fama quadruplator. Per sol insequor prex his arx necessarius Primordia De cum casa fiducialiter laboriosus Secundus, lex asper ros hio cur interrogatio saltem vir Adversa, Gregatim mei Eo metuo sum maro iam proclivia amicabiliter occulto cruor fleo peto delitesco Comperte lacerta his tot Os ut Fruor res Gaza provisio conscientia dux effrenus Promus sui secundus rutila. Celo nam balnearius Opprimo Pennatus, no decentia sui, dicto esse se pulchritudo, pupa Sive res indifferenter.</p>\n<p>Captivo pala pro de tandem Singulus labor, determino cui Ingurgito quo Ico pax ethologus praetorgredior internuntius. Ops foveo Huius dux respublica his animadverto dolus imperterritus. Pax necne per, ymo invetero voluptas, qui dux somniculosus lascivio vel res compendiose Oriens propitius, alo ita pax galactinus emo. Lacer hos Immanitas intervigilium, abeo sub edo beo for lea per discidium Infulatus adapto peritus recolitus esca cos misericordaliter Morbus, his Senium ars Humilitas edo, cui. Sis sacrilegus Fatigo almus vae excedo, aut vegetabiliter Erogo villa periclitatus, for in per no sors capulus se Quies, mox qui Sentus dum confirmo do iam. Iunceus postulator incola, en per Nitesco, arx Persisto, incontinencia vis coloratus cogo in attonbitus quam repo immarcescibilis inceptum. Ego Vena series sudo ac Nitidus. Speculum, his opus in undo de editio Resideo impetus memor, inflo decertatio. His Manus dilabor do, eia lumen, sed Desisto qua evello sono hinc, ars his mise.</p>\n<p>Isericordaliter Occatio ter aut Aliusmodi vel Fugo redigo, iam ops tam Plaga consulo sui ymo Zephyr humilitas. Ivi praebalteata Occumbo congruens seco, lea qui se surculus sed abhinc praejudico in forix curo. Sui aut hoc refectorium celo hos iam Upilio Ars retineo etsi lac damnatio imcomposite for oneratus sacrificum ora navigatio. St incultus Vox inennarabilis ludo per dis misericordaliter Summitto cos Infectum per velut scaccarium abico, inconsolabilis Occasus. Ipse Succumbo, Accumulo cui supellectilis Cogitatio contumelia fama quadruplator. Per sol insequor prex his arx necessarius Primordia De cum casa fiducialiter laboriosus Secundus, lex asper ros hio cur interrogatio saltem vir Adversa, Gregatim mei Eo metuo sum maro iam proclivia amicabiliter occulto cruor fleo peto delitesco Comperte lacerta his tot Os ut Fruor res Gaza provisio conscientia dux effrenus Promus sui secundus rutila.</p>\n<p>Celo nam balnearius Opprimo Pennatus, no decentia sui, dicto esse se pulchritudo, pupa Sive res indifferenter. Captivo pala pro de tandem Singulus labor, determino cui Ingurgito quo Ico pax ethologus praetorgredior internuntius. Ops foveo Huius dux respublica his animadverto dolus imperterritus. Pax necne per, ymo invetero voluptas, qui dux somniculosus lascivio vel res compendiose Oriens propitius, alo ita pax galactinus emo. Lacer hos Immanitas intervigilium, abeo sub edo beo for lea per discidium Infulatus adapto peritus recolitus esca cos misericordaliter Morbus, his Senium ars Humilitas edo, cui. Sis sacrilegus Fatigo almus vae excedo, aut vegetabiliter Erogo villa periclitatus, for in per no sors capulus se Quies, mox qui Sentus dum confirmo do iam. Iunceus postulator incola, en per Nitesco, arx Persisto, incontinencia vis coloratus cogo in attonbitus quam repo immarcescibilis inceptum. Ego Vena series sudo ac Nitidus. Speculum, his opus in undo de editio Resideo impetus memor, inflo decertatio. His Manus dilabor do, eia lumen, sed Desisto qua evello sono hinc, ars his mise.</p>',	'left|bottom2',	6,	'',	'',	0,	'',	'',	'',	'2019-12-06 10:19:52',	NULL),
(8,	1,	'',	'',	'',	'',	'',	'',	'Widerrufsrecht',	'<h2>Isericordaliter Occatio ter aut Aliusmodi vel Fugo redigo, iam ops tam Plaga consulo sui ymo Zephyr humilitas.</h2>\n<p>Isericordaliter Occatio ter aut Aliusmodi vel Fugo redigo, iam ops tam Plaga consulo sui ymo Zephyr humilitas. Ivi praebalteata Occumbo congruens seco, lea qui se surculus sed abhinc praejudico in forix curo. Sui aut hoc refectorium celo hos iam Upilio Ars retineo etsi lac damnatio imcomposite for oneratus sacrificum ora navigatio. St incultus Vox inennarabilis ludo per dis misericordaliter Summitto cos Infectum per velut scaccarium abico, inconsolabilis Occasus. Ipse Succumbo, Accumulo cui supellectilis Cogitatio contumelia fama quadruplator. Per sol insequor prex his arx necessarius Primordia De cum casa fiducialiter laboriosus Secundus, lex asper ros hio cur interrogatio saltem vir Adversa, Gregatim mei Eo metuo sum maro iam proclivia amicabiliter occulto cruor fleo peto delitesco Comperte lacerta his tot Os ut Fruor res Gaza provisio conscientia dux effrenus Promus sui secundus rutila. Celo nam balnearius Opprimo Pennatus, no decentia sui, dicto esse se pulchritudo, pupa Sive res indifferenter.</p>\n<p>Captivo pala pro de tandem Singulus labor, determino cui Ingurgito quo Ico pax ethologus praetorgredior internuntius. Ops foveo Huius dux respublica his animadverto dolus imperterritus. Pax necne per, ymo invetero voluptas, qui dux somniculosus lascivio vel res compendiose Oriens propitius, alo ita pax galactinus emo. Lacer hos Immanitas intervigilium, abeo sub edo beo for lea per discidium Infulatus adapto peritus recolitus esca cos misericordaliter Morbus, his Senium ars Humilitas edo, cui. Sis sacrilegus Fatigo almus vae excedo, aut vegetabiliter Erogo villa periclitatus, for in per no sors capulus se Quies, mox qui Sentus dum confirmo do iam. Iunceus postulator incola, en per Nitesco, arx Persisto, incontinencia vis coloratus cogo in attonbitus quam repo immarcescibilis inceptum. Ego Vena series sudo ac Nitidus. Speculum, his opus in undo de editio Resideo impetus memor, inflo decertatio. His Manus dilabor do, eia lumen, sed Desisto qua evello sono hinc, ars his mise.</p>\n<p>Isericordaliter Occatio ter aut Aliusmodi vel Fugo redigo, iam ops tam Plaga consulo sui ymo Zephyr humilitas. Ivi praebalteata Occumbo congruens seco, lea qui se surculus sed abhinc praejudico in forix curo. Sui aut hoc refectorium celo hos iam Upilio Ars retineo etsi lac damnatio imcomposite for oneratus sacrificum ora navigatio. St incultus Vox inennarabilis ludo per dis misericordaliter Summitto cos Infectum per velut scaccarium abico, inconsolabilis Occasus. Ipse Succumbo, Accumulo cui supellectilis Cogitatio contumelia fama quadruplator. Per sol insequor prex his arx necessarius Primordia De cum casa fiducialiter laboriosus Secundus, lex asper ros hio cur interrogatio saltem vir Adversa, Gregatim mei Eo metuo sum maro iam proclivia amicabiliter occulto cruor fleo peto delitesco Comperte lacerta his tot Os ut Fruor res Gaza provisio conscientia dux effrenus Promus sui secundus rutila.</p>\n<p>Celo nam balnearius Opprimo Pennatus, no decentia sui, dicto esse se pulchritudo, pupa Sive res indifferenter. Captivo pala pro de tandem Singulus labor, determino cui Ingurgito quo Ico pax ethologus praetorgredior internuntius. Ops foveo Huius dux respublica his animadverto dolus imperterritus. Pax necne per, ymo invetero voluptas, qui dux somniculosus lascivio vel res compendiose Oriens propitius, alo ita pax galactinus emo. Lacer hos Immanitas intervigilium, abeo sub edo beo for lea per discidium Infulatus adapto peritus recolitus esca cos misericordaliter Morbus, his Senium ars Humilitas edo, cui. Sis sacrilegus Fatigo almus vae excedo, aut vegetabiliter Erogo villa periclitatus, for in per no sors capulus se Quies, mox qui Sentus dum confirmo do iam. Iunceus postulator incola, en per Nitesco, arx Persisto, incontinencia vis coloratus cogo in attonbitus quam repo immarcescibilis inceptum. Ego Vena series sudo ac Nitidus. Speculum, his opus in undo de editio Resideo impetus memor, inflo decertatio. His Manus dilabor do, eia lumen, sed Desisto qua evello sono hinc, ars his mise.</p>',	'left|bottom',	5,	'',	'',	0,	'',	'',	'',	'2019-12-06 10:19:52',	NULL),
(9,	1,	'',	'',	'',	'',	'',	'',	'Über uns',	'<h2>Isericordaliter Occatio ter aut Aliusmodi vel Fugo redigo, iam ops tam Plaga consulo sui ymo Zephyr humilitas.</h2>\n<p>Isericordaliter Occatio ter aut Aliusmodi vel Fugo redigo, iam ops tam Plaga consulo sui ymo Zephyr humilitas. Ivi praebalteata Occumbo congruens seco, lea qui se surculus sed abhinc praejudico in forix curo. Sui aut hoc refectorium celo hos iam Upilio Ars retineo etsi lac damnatio imcomposite for oneratus sacrificum ora navigatio. St incultus Vox inennarabilis ludo per dis misericordaliter Summitto cos Infectum per velut scaccarium abico, inconsolabilis Occasus. Ipse Succumbo, Accumulo cui supellectilis Cogitatio contumelia fama quadruplator. Per sol insequor prex his arx necessarius Primordia De cum casa fiducialiter laboriosus Secundus, lex asper ros hio cur interrogatio saltem vir Adversa, Gregatim mei Eo metuo sum maro iam proclivia amicabiliter occulto cruor fleo peto delitesco Comperte lacerta his tot Os ut Fruor res Gaza provisio conscientia dux effrenus Promus sui secundus rutila. Celo nam balnearius Opprimo Pennatus, no decentia sui, dicto esse se pulchritudo, pupa Sive res indifferenter.</p>\n<p>Captivo pala pro de tandem Singulus labor, determino cui Ingurgito quo Ico pax ethologus praetorgredior internuntius. Ops foveo Huius dux respublica his animadverto dolus imperterritus. Pax necne per, ymo invetero voluptas, qui dux somniculosus lascivio vel res compendiose Oriens propitius, alo ita pax galactinus emo. Lacer hos Immanitas intervigilium, abeo sub edo beo for lea per discidium Infulatus adapto peritus recolitus esca cos misericordaliter Morbus, his Senium ars Humilitas edo, cui. Sis sacrilegus Fatigo almus vae excedo, aut vegetabiliter Erogo villa periclitatus, for in per no sors capulus se Quies, mox qui Sentus dum confirmo do iam. Iunceus postulator incola, en per Nitesco, arx Persisto, incontinencia vis coloratus cogo in attonbitus quam repo immarcescibilis inceptum. Ego Vena series sudo ac Nitidus. Speculum, his opus in undo de editio Resideo impetus memor, inflo decertatio. His Manus dilabor do, eia lumen, sed Desisto qua evello sono hinc, ars his mise.</p>\n<p>Isericordaliter Occatio ter aut Aliusmodi vel Fugo redigo, iam ops tam Plaga consulo sui ymo Zephyr humilitas. Ivi praebalteata Occumbo congruens seco, lea qui se surculus sed abhinc praejudico in forix curo. Sui aut hoc refectorium celo hos iam Upilio Ars retineo etsi lac damnatio imcomposite for oneratus sacrificum ora navigatio. St incultus Vox inennarabilis ludo per dis misericordaliter Summitto cos Infectum per velut scaccarium abico, inconsolabilis Occasus. Ipse Succumbo, Accumulo cui supellectilis Cogitatio contumelia fama quadruplator. Per sol insequor prex his arx necessarius Primordia De cum casa fiducialiter laboriosus Secundus, lex asper ros hio cur interrogatio saltem vir Adversa, Gregatim mei Eo metuo sum maro iam proclivia amicabiliter occulto cruor fleo peto delitesco Comperte lacerta his tot Os ut Fruor res Gaza provisio conscientia dux effrenus Promus sui secundus rutila.</p>\n<p>Celo nam balnearius Opprimo Pennatus, no decentia sui, dicto esse se pulchritudo, pupa Sive res indifferenter. Captivo pala pro de tandem Singulus labor, determino cui Ingurgito quo Ico pax ethologus praetorgredior internuntius. Ops foveo Huius dux respublica his animadverto dolus imperterritus. Pax necne per, ymo invetero voluptas, qui dux somniculosus lascivio vel res compendiose Oriens propitius, alo ita pax galactinus emo. Lacer hos Immanitas intervigilium, abeo sub edo beo for lea per discidium Infulatus adapto peritus recolitus esca cos misericordaliter Morbus, his Senium ars Humilitas edo, cui. Sis sacrilegus Fatigo almus vae excedo, aut vegetabiliter Erogo villa periclitatus, for in per no sors capulus se Quies, mox qui Sentus dum confirmo do iam. Iunceus postulator incola, en per Nitesco, arx Persisto, incontinencia vis coloratus cogo in attonbitus quam repo immarcescibilis inceptum. Ego Vena series sudo ac Nitidus. Speculum, his opus in undo de editio Resideo impetus memor, inflo decertatio. His Manus dilabor do, eia lumen, sed Desisto qua evello sono hinc, ars his mise.</p>',	'left|bottom2',	0,	'',	'',	0,	'',	'',	'',	'2019-12-06 10:19:52',	NULL),
(21,	1,	'',	'',	'',	'',	'',	'',	'Händler-Login',	'',	'left',	0,	'shopware.php?sViewport=registerFC&sUseSSL=1&sValidation=H',	'',	0,	'',	'',	'',	'2019-12-06 10:19:52',	NULL),
(26,	1,	'',	'',	'',	'',	'',	'',	'Newsletter',	'',	'bottom2',	0,	'shopware.php?sViewport=newsletter',	'',	0,	'',	'',	'',	'2019-12-06 10:19:52',	NULL),
(37,	1,	'',	'',	'',	'',	'',	'',	'Partnerprogramm',	'<h1>Jetzt Partner werden</h1>\n<h2>Isericordaliter Occatio ter aut Aliusmodi vel Fugo redigo, iam ops tam Plaga consulo sui ymo Zephyr humilitas.</h2>\n<p>Isericordaliter Occatio ter aut Aliusmodi vel Fugo redigo, iam ops tam Plaga consulo sui ymo Zephyr humilitas. Ivi praebalteata Occumbo congruens seco, lea qui se surculus sed abhinc praejudico in forix curo. Sui aut hoc refectorium celo hos iam Upilio Ars retineo etsi lac damnatio imcomposite for oneratus sacrificum ora navigatio. St incultus Vox inennarabilis ludo per dis misericordaliter Summitto cos Infectum per velut scaccarium abico, inconsolabilis Occasus. Ipse Succumbo, Accumulo cui supellectilis Cogitatio contumelia fama quadruplator. Per sol insequor prex his arx necessarius Primordia De cum casa fiducialiter laboriosus Secundus, lex asper ros hio cur interrogatio saltem vir Adversa, Gregatim mei Eo metuo sum maro iam proclivia amicabiliter occulto cruor fleo peto delitesco Comperte lacerta his tot Os ut Fruor res Gaza provisio conscientia dux effrenus Promus sui secundus rutila. Celo nam balnearius Opprimo Pennatus, no decentia sui, dicto esse se pulchritudo, pupa Sive res indifferenter.</p>\n<p>Captivo pala pro de tandem Singulus labor, determino cui Ingurgito quo Ico pax ethologus praetorgredior internuntius. Ops foveo Huius dux respublica his animadverto dolus imperterritus. Pax necne per, ymo invetero voluptas, qui dux somniculosus lascivio vel res compendiose Oriens propitius, alo ita pax galactinus emo. Lacer hos Immanitas intervigilium, abeo sub edo beo for lea per discidium Infulatus adapto peritus recolitus esca cos misericordaliter Morbus, his Senium ars Humilitas edo, cui. Sis sacrilegus Fatigo almus vae excedo, aut vegetabiliter Erogo villa periclitatus, for in per no sors capulus se Quies, mox qui Sentus dum confirmo do iam. Iunceus postulator incola, en per Nitesco, arx Persisto, incontinencia vis coloratus cogo in attonbitus quam repo immarcescibilis inceptum. Ego Vena series sudo ac Nitidus. Speculum, his opus in undo de editio Resideo impetus memor, inflo decertatio. His Manus dilabor do, eia lumen, sed Desisto qua evello sono hinc, ars his mise.</p>\n<p>Isericordaliter Occatio ter aut Aliusmodi vel Fugo redigo, iam ops tam Plaga consulo sui ymo Zephyr humilitas. Ivi praebalteata Occumbo congruens seco, lea qui se surculus sed abhinc praejudico in forix curo. Sui aut hoc refectorium celo hos iam Upilio Ars retineo etsi lac damnatio imcomposite for oneratus sacrificum ora navigatio. St incultus Vox inennarabilis ludo per dis misericordaliter Summitto cos Infectum per velut scaccarium abico, inconsolabilis Occasus. Ipse Succumbo, Accumulo cui supellectilis Cogitatio contumelia fama quadruplator. Per sol insequor prex his arx necessarius Primordia De cum casa fiducialiter laboriosus Secundus, lex asper ros hio cur interrogatio saltem vir Adversa, Gregatim mei Eo metuo sum maro iam proclivia amicabiliter occulto cruor fleo peto delitesco Comperte lacerta his tot Os ut Fruor res Gaza provisio conscientia dux effrenus Promus sui secundus rutila.</p>\n<p>Celo nam balnearius Opprimo Pennatus, no decentia sui, dicto esse se pulchritudo, pupa Sive res indifferenter. Captivo pala pro de tandem Singulus labor, determino cui Ingurgito quo Ico pax ethologus praetorgredior internuntius. Ops foveo Huius dux respublica his animadverto dolus imperterritus. Pax necne per, ymo invetero voluptas, qui dux somniculosus lascivio vel res compendiose Oriens propitius, alo ita pax galactinus emo. Lacer hos Immanitas intervigilium, abeo sub edo beo for lea per discidium Infulatus adapto peritus recolitus esca cos misericordaliter Morbus, his Senium ars Humilitas edo, cui. Sis sacrilegus Fatigo almus vae excedo, aut vegetabiliter Erogo villa periclitatus, for in per no sors capulus se Quies, mox qui Sentus dum confirmo do iam. Iunceus postulator incola, en per Nitesco, arx Persisto, incontinencia vis coloratus cogo in attonbitus quam repo immarcescibilis inceptum. Ego Vena series sudo ac Nitidus. Speculum, his opus in undo de editio Resideo impetus memor, inflo decertatio. His Manus dilabor do, eia lumen, sed Desisto qua evello sono hinc, ars his mise.</p>',	'bottom',	0,	'shopware.php?sViewport=ticket&sFid=8',	'_self',	0,	'',	'',	'',	'2019-12-06 10:19:52',	NULL),
(39,	1,	'',	'',	'',	'',	'',	'',	'Defektes Produkt',	'<p>Defektes Produkt.</p>',	'bottom',	0,	'shopware.php?sViewport=ticket&sFid=9',	'_self',	0,	'',	'',	'',	'2019-12-06 10:19:52',	NULL),
(41,	1,	'',	'',	'',	'',	'',	'',	'Rückgabe',	'<p>R&uuml;ckgabe.</p>',	'bottom',	4,	'shopware.php?sViewport=ticket&sFid=10',	'_self',	0,	'',	'',	'',	'2019-12-06 10:19:52',	NULL),
(43,	1,	'',	'',	'',	'',	'',	'',	'rechtliche Vorabinformationen',	'<h2>Isericordaliter Occatio ter aut Aliusmodi vel Fugo redigo, iam ops tam Plaga consulo sui ymo Zephyr humilitas.</h2>\n<p>Isericordaliter Occatio ter aut Aliusmodi vel Fugo redigo, iam ops tam Plaga consulo sui ymo Zephyr humilitas. Ivi praebalteata Occumbo congruens seco, lea qui se surculus sed abhinc praejudico in forix curo. Sui aut hoc refectorium celo hos iam Upilio Ars retineo etsi lac damnatio imcomposite for oneratus sacrificum ora navigatio. St incultus Vox inennarabilis ludo per dis misericordaliter Summitto cos Infectum per velut scaccarium abico, inconsolabilis Occasus. Ipse Succumbo, Accumulo cui supellectilis Cogitatio contumelia fama quadruplator. Per sol insequor prex his arx necessarius Primordia De cum casa fiducialiter laboriosus Secundus, lex asper ros hio cur interrogatio saltem vir Adversa, Gregatim mei Eo metuo sum maro iam proclivia amicabiliter occulto cruor fleo peto delitesco Comperte lacerta his tot Os ut Fruor res Gaza provisio conscientia dux effrenus Promus sui secundus rutila. Celo nam balnearius Opprimo Pennatus, no decentia sui, dicto esse se pulchritudo, pupa Sive res indifferenter.</p>\n<p>Captivo pala pro de tandem Singulus labor, determino cui Ingurgito quo Ico pax ethologus praetorgredior internuntius. Ops foveo Huius dux respublica his animadverto dolus imperterritus. Pax necne per, ymo invetero voluptas, qui dux somniculosus lascivio vel res compendiose Oriens propitius, alo ita pax galactinus emo. Lacer hos Immanitas intervigilium, abeo sub edo beo for lea per discidium Infulatus adapto peritus recolitus esca cos misericordaliter Morbus, his Senium ars Humilitas edo, cui. Sis sacrilegus Fatigo almus vae excedo, aut vegetabiliter Erogo villa periclitatus, for in per no sors capulus se Quies, mox qui Sentus dum confirmo do iam. Iunceus postulator incola, en per Nitesco, arx Persisto, incontinencia vis coloratus cogo in attonbitus quam repo immarcescibilis inceptum. Ego Vena series sudo ac Nitidus. Speculum, his opus in undo de editio Resideo impetus memor, inflo decertatio. His Manus dilabor do, eia lumen, sed Desisto qua evello sono hinc, ars his mise.</p>\n<p>Isericordaliter Occatio ter aut Aliusmodi vel Fugo redigo, iam ops tam Plaga consulo sui ymo Zephyr humilitas. Ivi praebalteata Occumbo congruens seco, lea qui se surculus sed abhinc praejudico in forix curo. Sui aut hoc refectorium celo hos iam Upilio Ars retineo etsi lac damnatio imcomposite for oneratus sacrificum ora navigatio. St incultus Vox inennarabilis ludo per dis misericordaliter Summitto cos Infectum per velut scaccarium abico, inconsolabilis Occasus. Ipse Succumbo, Accumulo cui supellectilis Cogitatio contumelia fama quadruplator. Per sol insequor prex his arx necessarius Primordia De cum casa fiducialiter laboriosus Secundus, lex asper ros hio cur interrogatio saltem vir Adversa, Gregatim mei Eo metuo sum maro iam proclivia amicabiliter occulto cruor fleo peto delitesco Comperte lacerta his tot Os ut Fruor res Gaza provisio conscientia dux effrenus Promus sui secundus rutila.</p>\n<p>Celo nam balnearius Opprimo Pennatus, no decentia sui, dicto esse se pulchritudo, pupa Sive res indifferenter. Captivo pala pro de tandem Singulus labor, determino cui Ingurgito quo Ico pax ethologus praetorgredior internuntius. Ops foveo Huius dux respublica his animadverto dolus imperterritus. Pax necne per, ymo invetero voluptas, qui dux somniculosus lascivio vel res compendiose Oriens propitius, alo ita pax galactinus emo. Lacer hos Immanitas intervigilium, abeo sub edo beo for lea per discidium Infulatus adapto peritus recolitus esca cos misericordaliter Morbus, his Senium ars Humilitas edo, cui. Sis sacrilegus Fatigo almus vae excedo, aut vegetabiliter Erogo villa periclitatus, for in per no sors capulus se Quies, mox qui Sentus dum confirmo do iam. Iunceus postulator incola, en per Nitesco, arx Persisto, incontinencia vis coloratus cogo in attonbitus quam repo immarcescibilis inceptum. Ego Vena series sudo ac Nitidus. Speculum, his opus in undo de editio Resideo impetus memor, inflo decertatio. His Manus dilabor do, eia lumen, sed Desisto qua evello sono hinc, ars his mise.</p>',	'left|bottom',	0,	'',	'',	0,	'',	'',	'',	'2019-12-06 10:19:52',	NULL),
(45,	1,	'',	'',	'',	'',	'',	'',	'Widerrufsformular',	'<h2>Isericordaliter Occatio ter aut Aliusmodi vel Fugo redigo, iam ops tam Plaga consulo sui ymo Zephyr humilitas.</h2>\n<p>Isericordaliter Occatio ter aut Aliusmodi vel Fugo redigo, iam ops tam Plaga consulo sui ymo Zephyr humilitas. Ivi praebalteata Occumbo congruens seco, lea qui se surculus sed abhinc praejudico in forix curo. Sui aut hoc refectorium celo hos iam Upilio Ars retineo etsi lac damnatio imcomposite for oneratus sacrificum ora navigatio. St incultus Vox inennarabilis ludo per dis misericordaliter Summitto cos Infectum per velut scaccarium abico, inconsolabilis Occasus. Ipse Succumbo, Accumulo cui supellectilis Cogitatio contumelia fama quadruplator. Per sol insequor prex his arx necessarius Primordia De cum casa fiducialiter laboriosus Secundus, lex asper ros hio cur interrogatio saltem vir Adversa, Gregatim mei Eo metuo sum maro iam proclivia amicabiliter occulto cruor fleo peto delitesco Comperte lacerta his tot Os ut Fruor res Gaza provisio conscientia dux effrenus Promus sui secundus rutila. Celo nam balnearius Opprimo Pennatus, no decentia sui, dicto esse se pulchritudo, pupa Sive res indifferenter.</p>\n<p>Captivo pala pro de tandem Singulus labor, determino cui Ingurgito quo Ico pax ethologus praetorgredior internuntius. Ops foveo Huius dux respublica his animadverto dolus imperterritus. Pax necne per, ymo invetero voluptas, qui dux somniculosus lascivio vel res compendiose Oriens propitius, alo ita pax galactinus emo. Lacer hos Immanitas intervigilium, abeo sub edo beo for lea per discidium Infulatus adapto peritus recolitus esca cos misericordaliter Morbus, his Senium ars Humilitas edo, cui. Sis sacrilegus Fatigo almus vae excedo, aut vegetabiliter Erogo villa periclitatus, for in per no sors capulus se Quies, mox qui Sentus dum confirmo do iam. Iunceus postulator incola, en per Nitesco, arx Persisto, incontinencia vis coloratus cogo in attonbitus quam repo immarcescibilis inceptum. Ego Vena series sudo ac Nitidus. Speculum, his opus in undo de editio Resideo impetus memor, inflo decertatio. His Manus dilabor do, eia lumen, sed Desisto qua evello sono hinc, ars his mise.</p>\n<p>Isericordaliter Occatio ter aut Aliusmodi vel Fugo redigo, iam ops tam Plaga consulo sui ymo Zephyr humilitas. Ivi praebalteata Occumbo congruens seco, lea qui se surculus sed abhinc praejudico in forix curo. Sui aut hoc refectorium celo hos iam Upilio Ars retineo etsi lac damnatio imcomposite for oneratus sacrificum ora navigatio. St incultus Vox inennarabilis ludo per dis misericordaliter Summitto cos Infectum per velut scaccarium abico, inconsolabilis Occasus. Ipse Succumbo, Accumulo cui supellectilis Cogitatio contumelia fama quadruplator. Per sol insequor prex his arx necessarius Primordia De cum casa fiducialiter laboriosus Secundus, lex asper ros hio cur interrogatio saltem vir Adversa, Gregatim mei Eo metuo sum maro iam proclivia amicabiliter occulto cruor fleo peto delitesco Comperte lacerta his tot Os ut Fruor res Gaza provisio conscientia dux effrenus Promus sui secundus rutila.</p>\n<p>Celo nam balnearius Opprimo Pennatus, no decentia sui, dicto esse se pulchritudo, pupa Sive res indifferenter. Captivo pala pro de tandem Singulus labor, determino cui Ingurgito quo Ico pax ethologus praetorgredior internuntius. Ops foveo Huius dux respublica his animadverto dolus imperterritus. Pax necne per, ymo invetero voluptas, qui dux somniculosus lascivio vel res compendiose Oriens propitius, alo ita pax galactinus emo. Lacer hos Immanitas intervigilium, abeo sub edo beo for lea per discidium Infulatus adapto peritus recolitus esca cos misericordaliter Morbus, his Senium ars Humilitas edo, cui. Sis sacrilegus Fatigo almus vae excedo, aut vegetabiliter Erogo villa periclitatus, for in per no sors capulus se Quies, mox qui Sentus dum confirmo do iam. Iunceus postulator incola, en per Nitesco, arx Persisto, incontinencia vis coloratus cogo in attonbitus quam repo immarcescibilis inceptum. Ego Vena series sudo ac Nitidus. Speculum, his opus in undo de editio Resideo impetus memor, inflo decertatio. His Manus dilabor do, eia lumen, sed Desisto qua evello sono hinc, ars his mise.</p>',	'bottom',	8,	'',	'',	0,	'',	'',	'',	'2019-12-06 10:19:52',	NULL),
(46,	1,	'',	'',	'',	'',	'',	'',	'Cookie-Einstellungen',	'',	'bottom2|left',	0,	'javascript:openCookieConsentManager()',	'',	0,	'',	'',	'',	'2019-11-01 00:00:00',	NULL);

DROP TABLE IF EXISTS `s_cms_static_attributes`;
CREATE TABLE `s_cms_static_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cmsStaticID` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cmsStaticID` (`cmsStaticID`),
  CONSTRAINT `s_cms_static_attributes_ibfk_1` FOREIGN KEY (`cmsStaticID`) REFERENCES `s_cms_static` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_cms_static_groups`;
CREATE TABLE `s_cms_static_groups` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `key` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `active` int NOT NULL,
  `mapping_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mapping_id` (`mapping_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_cms_static_groups` (`id`, `name`, `key`, `active`, `mapping_id`) VALUES
(1,	'Links',	'left',	1,	NULL),
(2,	'Unten (Spalte 1)',	'bottom',	1,	NULL),
(3,	'Unten (Spalte 2)',	'bottom2',	1,	NULL),
(4,	'In Bearbeitung',	'disabled',	0,	NULL);

DROP TABLE IF EXISTS `s_cms_support`;
CREATE TABLE `s_cms_support` (
  `id` int NOT NULL AUTO_INCREMENT,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `text` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `email_template` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `email_subject` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `text2` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `meta_title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `meta_keywords` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `meta_description` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `ticket_typeID` int NOT NULL,
  `isocode` varchar(3) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'de',
  `shop_ids` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_cms_support` (`id`, `active`, `name`, `text`, `email`, `email_template`, `email_subject`, `text2`, `meta_title`, `meta_keywords`, `meta_description`, `ticket_typeID`, `isocode`, `shop_ids`) VALUES
(5,	1,	'Kontaktformular',	'<p>Schreiben Sie uns eine E-Mail.</p>\r\n<p>Wir freuen uns auf Ihre Kontaktaufnahme.</p>',	'testing@shopware.com',	'Kontaktformular Shopware Demoshop\r\n\r\nAnrede: {sVars.anrede}\r\nVorname: {sVars.vorname}\r\nNachname: {sVars.nachname}\r\nE-Mail: {sVars.email}\r\nTelefon: {sVars.telefon}\r\nBetreff: {sVars.betreff}\r\nKommentar: \r\n{sVars.kommentar}\r\n\r\n\r\n',	'Kontaktformular Shopware',	'<p>Ihr Formular wurde versendet!</p>',	NULL,	NULL,	NULL,	1,	'de',	NULL),
(8,	1,	'Partnerformular',	'<h2>Partner werden und mitverdienen!</h2>\r\n<p>Einfach unseren Link auf ihre Seite legen und Sie erhalten f&uuml;r jeden Umsatz ihrer vermittelten Kunden automatisch eine attraktive Provision auf den Netto-Auftragswert.</p>\r\n<p>Bitte f&uuml;llen Sie <span style=\"text-decoration: underline;\">unverbindlich</span> das Partnerformular aus. Wir werden uns umgehend mit Ihnen in Verbindung setzen!</p>',	'testing@shopware.com',	'Partneranfrage - {$sShopname}\n{sVars.firma} moechte Partner Ihres Shops werden!\n\nFirma: {sVars.firma}\nAnsprechpartner: {sVars.ansprechpartner}\nStraße/Hausnr.: {sVars.strasse}\nPLZ / Ort: {sVars.plz} {sVars.ort}\neMail: {sVars.email}\nTelefon: {sVars.tel}\nFax: {sVars.fax}\nWebseite: {sVars.website}\n\nKommentar:\n{sVars.kommentar}\n\nProfil:\n{sVars.profil}',	'Partner Anfrage',	'<p>Die Anfrage wurde versandt!</p>',	NULL,	NULL,	NULL,	0,	'de',	NULL),
(9,	1,	'Defektes Produkt',	'<p>Sie erhalten von uns nach dem Absenden dieses Formulars innerhalb kurzer Zeit eine R&uuml;ckantwort mit einer RMA-Nummer und weiterer Vorgehensweise.</p>\r\n<p>Bitte f&uuml;llen Sie die Fehlerbeschreibung ausf&uuml;hrlich aus, Sie m&uuml;ssen diese dann nicht mehr dem Paket beilegen.</p>',	'testing@shopware.com',	'Defektes Produkt - Shopware Demoshop\r\n\r\nFirma: {sVars.firma}\r\nKundennummer: {sVars.kdnr}\r\neMail: {sVars.email}\r\n\r\nRechnungsnummer: {sVars.rechnung}\r\nArtikelnummer: {sVars.artikel}\r\n\r\nDetaillierte Fehlerbeschreibung:\r\n--------------------------------\r\n{sVars.fehler}\r\n\r\nRechner: {sVars.rechner}\r\nSystem {sVars.system}\r\nWie tritt das Problem auf: {sVars.wie}\r\n',	'Online-Serviceformular',	'<p>Formular erfolgreich versandt!</p>',	NULL,	NULL,	NULL,	2,	'de',	NULL),
(10,	1,	'Rückgabe',	'<h2>Hier k&ouml;nnen Sie Informationen zur R&uuml;ckgabe einstellen...</h2>',	'testing@shopware.com',	'Rückgabe - Shopware Demoshop\n \nKundennummer: {sVars.kdnr}\neMail: {sVars.email}\n \nRechnungsnummer: {sVars.rechnung}\nArtikelnummer: {sVars.artikel}\n \nKommentar:\n \n{sVars.info}',	'Rückgabe',	'<p>Formular erfolgreich versandt.</p>',	NULL,	NULL,	NULL,	0,	'de',	NULL),
(16,	1,	'Anfrage-Formular',	'<p>Schreiben Sie uns eine eMail.</p>\r\n<p>Wir freuen uns auf Ihre Kontaktaufnahme.</p>',	'testing@shopware.com',	'{sShopname} Anfrage-Formular\n\nAnrede: {sVars.anrede}\nVorname: {sVars.vorname}\nNachname: {sVars.nachname}\neMail: {sVars.email}\nTelefon: {sVars.telefon}\nArtikel: {sVars.sordernumber}\n\nFrage:\n{sVars.inquiry}',	'{sShopname} Anfrage-Formular',	'<p>Ihre Anfrage wurde versendet!</p>',	NULL,	NULL,	NULL,	0,	'de',	NULL),
(17,	1,	'Partner form',	'<h2><strong>Become partner and earn money!</strong></h2>\r\n<p>Link our Site and receive&nbsp;an attractive commission on the net contract price&nbsp;for every tornover of your&nbsp;provided customers.</p>\r\n<p>Please fill out the partner form <span style=\"text-decoration: underline;\">without obligation</span>.&nbsp;We will immediately get in contact with you!</p>',	'testing@shopware.com',	'Partner inquiry - {$sShopname}\n{sVars.firma} want to become your partner!\n\nCompany: {sVars.firma}\nContact person: {sVars.ansprechpartner}\nStreet / No.: {sVars.strasse}\nPostal Code / City: {sVars.plz} {sVars.ort}\neMail: {sVars.email}\nPhone: {sVars.tel}\nFax: {sVars.fax}\nWebsite: {sVars.website}\n\nComment:\n{sVars.kommentar}\n\nProfile:\n{sVars.profil}',	'Partner inquiry',	'<p>&nbsp;</p>\r\n&nbsp;\r\n<div id=\"result_box\" dir=\"ltr\">The request has been sent!</div>',	NULL,	NULL,	NULL,	0,	'de',	NULL),
(18,	1,	'Contact',	'',	'testing@shopware.com',	'Contact form Shopware Demoshop\r\n\r\nTitle: {sVars.anrede}\r\nFirst name: {sVars.vorname}\r\nLast name: {sVars.nachname}\r\neMail: {sVars.email}\r\nPhone: {sVars.telefon}\r\nSubject: {sVars.betreff}\r\nComment: \r\n{sVars.kommentar}\r\n\r\n\r\n',	'Contact form Shopware',	'<p>Your form was sent!</p>',	NULL,	NULL,	NULL,	0,	'de',	NULL),
(19,	1,	'Defective product',	'<p>&nbsp;</p>\r\n&nbsp;\r\n<h1>Defective product - for customers and traders</h1>\r\n<p>You will receive an answer&nbsp;from us&nbsp;with an RMA number an other approach&nbsp;after sending this form.&nbsp;</p>\r\n<p>Please fill out the error description, so you must not add this any more to the package.</p>',	'testing@shopware.com',	'Defective product - Shopware Demoshop\n\nCompany: {sVars.firma}\nCustomer no.: {sVars.kdnr}\neMail: {sVars.email}\n\nInvoice no.: {sVars.rechnung}\nArticle no.: {sVars.artikel}\n\nDescription of failure:\n--------------------------------\n{sVars.fehler}\n\nType: {sVars.rechner}\nSystem {sVars.system}\nHow does the problem occur:\n{sVars.wie}',	'Online-Serviceform',	'<p>Form successfully sent!</p>',	NULL,	NULL,	NULL,	0,	'de',	NULL),
(20,	1,	'Return',	'<h2>Here you can write information about the return...</h2>',	'testing@shopware.com',	'Return - Shopware Demoshop\n\nCustomer no.: {sVars.kdnr}\neMail: {sVars.email}\n\nInvoice no.: {sVars.rechnung}\nArticle no.: {sVars.artikel}\n\nComment:\n{sVars.info}',	'Return',	'<p>Form successfully sent.</p>',	NULL,	NULL,	NULL,	0,	'de',	NULL),
(21,	1,	'Inquiry form',	'<p>Send us an email.&nbsp;<br /><br />We look forward to hearing from you.</p>',	'testing@shopware.com',	'{sShopname} Anfrage-Formular\n\nAnrede: {sVars.anrede}\nVorname: {sVars.vorname}\nNachname: {sVars.nachname}\neMail: {sVars.email}\nTelefon: {sVars.telefon}\nArtikel: {sVars.sordernumber}\n\nFrage:\n{sVars.inquiry}',	'{sShopname} Anfrage-Formular',	'<p>Your request has been sent!</p>',	NULL,	NULL,	NULL,	0,	'de',	NULL),
(22,	1,	'Support beantragen',	'<p>Wir freuen uns &uuml;ber Ihre Kontaktaufnahme.</p>',	'testing@shopware.com',	'',	'Support beantragen',	'<p>Vielen Dank f&uuml;r Ihre Anfrage!</p>',	NULL,	NULL,	NULL,	1,	'de',	NULL);

DROP TABLE IF EXISTS `s_cms_support_attributes`;
CREATE TABLE `s_cms_support_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cmsSupportID` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cmsSupportID` (`cmsSupportID`),
  CONSTRAINT `s_cms_support_attributes_ibfk_1` FOREIGN KEY (`cmsSupportID`) REFERENCES `s_cms_support` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_cms_support_fields`;
CREATE TABLE `s_cms_support_fields` (
  `id` int NOT NULL AUTO_INCREMENT,
  `error_msg` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `note` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `typ` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `required` int NOT NULL,
  `supportID` int NOT NULL,
  `label` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `class` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `value` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `added` datetime NOT NULL,
  `position` int NOT NULL,
  `ticket_task` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`,`supportID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_cms_support_fields` (`id`, `error_msg`, `name`, `note`, `typ`, `required`, `supportID`, `label`, `class`, `value`, `added`, `position`, `ticket_task`) VALUES
(24,	'',	'anrede',	'',	'select',	1,	5,	'Anrede',	'normal',	'Frau;Herr',	'2007-11-02 03:28:48',	1,	''),
(35,	'',	'vorname',	'',	'text',	1,	5,	'Vorname',	'normal',	'',	'2007-11-06 03:17:48',	2,	''),
(36,	'',	'nachname',	'',	'text',	1,	5,	'Nachname',	'normal',	'',	'2007-11-06 03:17:57',	3,	'name'),
(37,	'',	'email',	'',	'email',	1,	5,	'E-Mail-Adresse',	'normal',	'',	'2007-11-06 03:18:36',	4,	'email'),
(38,	'',	'telefon',	'',	'text',	0,	5,	'Telefon',	'normal',	'',	'2007-11-06 03:18:49',	5,	''),
(39,	'',	'betreff',	'',	'text',	1,	5,	'Betreff',	'normal',	'',	'2007-11-06 03:18:57',	6,	'subject'),
(40,	'',	'kommentar',	'',	'textarea',	1,	5,	'Kommentar',	'normal',	'',	'2007-11-06 03:19:08',	7,	'message'),
(41,	'',	'firma',	'',	'text',	1,	8,	'Firma',	'normal',	'',	'2007-11-22 08:11:39',	1,	''),
(42,	'',	'ansprechpartner',	'',	'text',	1,	8,	'Ansprechpartner',	'normal',	'',	'2007-11-22 08:12:18',	2,	''),
(43,	'',	'strasse',	'',	'text',	1,	8,	'Straße & Hausnummer',	'normal',	'',	'2007-11-22 08:12:49',	3,	''),
(44,	'',	'plz;ort',	'',	'text2',	1,	8,	'PLZ ; Ort',	'plz;ort',	'',	'2007-11-22 08:12:59',	4,	''),
(45,	'',	'tel',	'',	'text',	1,	8,	'Telefon',	'normal',	'',	'2007-11-22 08:13:45',	5,	''),
(46,	'',	'fax',	'',	'text',	0,	8,	'Fax',	'normal',	'',	'2007-11-22 08:13:52',	6,	''),
(47,	'',	'email',	'',	'text',	1,	8,	'E-Mail',	'normal',	'',	'2007-11-22 08:13:58',	7,	''),
(48,	'',	'website',	'',	'text',	1,	8,	'Webseite',	'normal',	'',	'2007-11-22 08:14:07',	8,	''),
(49,	'',	'kommentar',	'',	'textarea',	0,	8,	'Kommentar',	'normal',	'',	'2007-11-22 08:14:21',	9,	''),
(50,	'',	'profil',	'',	'textarea',	1,	8,	'Firmenprofil',	'normal',	'',	'2007-11-22 08:14:34',	10,	''),
(51,	'',	'rechnung',	'',	'text',	1,	9,	'Rechnungsnummer',	'normal',	'',	'2007-11-06 17:21:49',	1,	''),
(52,	'',	'email',	'',	'text',	1,	9,	'E-Mail-Adresse',	'normal',	'',	'2007-11-06 17:19:20',	2,	'email'),
(53,	'',	'kdnr',	'',	'text',	1,	9,	'KdNr.(siehe Rechnung)',	'normal',	'',	'2007-11-06 17:19:10',	3,	'name'),
(54,	'',	'firma',	'',	'checkbox',	0,	9,	'Firma (Wenn ja, bitte ankreuzen)',	'',	'1',	'2007-11-06 17:18:36',	4,	''),
(55,	'',	'artikel',	'',	'textarea',	1,	9,	'Artikelnummer(n)',	'normal',	'',	'2007-11-06 17:22:13',	5,	'subject'),
(56,	'',	'fehler',	'',	'textarea',	1,	9,	'Detaillierte Fehlerbeschreibung',	'normal',	'',	'2007-11-06 17:22:33',	6,	'message'),
(57,	'',	'rechner',	'',	'textarea',	0,	9,	'Auf welchem Rechnertypen läuft das defekte Produkt?',	'normal',	'',	'2007-11-06 17:23:17',	7,	''),
(58,	'',	'system',	'',	'textarea',	0,	9,	'Mit welchem Betriebssystem arbeiten Sie?',	'normal',	'',	'2007-11-06 17:23:57',	8,	''),
(59,	'',	'wie',	'',	'select',	1,	9,	'Wie tritt das Problem auf?',	'normal',	'sporadisch; ständig',	'2007-11-06 17:24:26',	9,	''),
(60,	'',	'kdnr',	'',	'text',	1,	10,	'KdNr.(siehe Rechnung)',	'normal',	'',	'2007-11-06 17:31:38',	1,	''),
(61,	'',	'email',	'',	'text',	1,	10,	'E-Mail-Adresse',	'normal',	'',	'2007-11-06 17:31:51',	2,	''),
(62,	'',	'rechnung',	'',	'text',	1,	10,	'Rechnungsnummer',	'normal',	'',	'2007-11-06 17:32:02',	3,	''),
(63,	'',	'artikel',	'',	'textarea',	1,	10,	'Artikelnummer(n)',	'normal',	'',	'2007-11-06 17:32:17',	4,	''),
(64,	'',	'info',	'',	'textarea',	0,	10,	'Kommentar',	'normal',	'',	'2007-11-06 17:32:42',	5,	''),
(69,	'',	'inquiry',	'',	'textarea',	1,	16,	'Anfrage',	'normal',	'',	'2007-11-06 03:19:08',	1,	''),
(71,	'',	'nachname',	'',	'text',	1,	16,	'Nachname',	'normal',	'',	'2007-11-06 03:17:57',	2,	''),
(72,	'',	'anrede',	'',	'select',	1,	16,	'Anrede',	'normal',	'Frau;Herr',	'2007-11-02 03:28:48',	3,	''),
(73,	'',	'telefon',	'',	'text',	0,	16,	'Telefon',	'normal',	'',	'2007-11-06 03:18:49',	4,	''),
(74,	'',	'email',	'',	'text',	1,	16,	'E-Mail-Adresse',	'normal',	'',	'2007-11-06 03:18:36',	5,	''),
(75,	'',	'vorname',	'',	'text',	1,	16,	'Vorname',	'normal',	'',	'2007-11-06 03:17:48',	6,	''),
(76,	'',	'firma',	'',	'text',	1,	17,	'Company',	'normal',	'',	'2008-10-17 13:02:42',	1,	''),
(77,	'',	'ansprechpartner',	'',	'text',	1,	17,	'Contact person',	'normal',	'',	'2008-10-17 13:03:35',	2,	''),
(78,	'',	'strasse',	'',	'text',	1,	17,	'Street & house number',	'normal',	'',	'2008-10-17 13:05:55',	3,	''),
(79,	'',	'plz;ort',	'',	'text2',	1,	17,	'Postal Code ; City',	'plz;ort',	'',	'2008-10-17 13:06:23',	4,	''),
(80,	'',	'tel',	'',	'text',	1,	17,	'Phone',	'normal',	'',	'2008-10-17 13:06:35',	5,	''),
(81,	'',	'fax',	'',	'text',	0,	17,	'Fax',	'normal',	'',	'2008-10-17 13:06:48',	6,	''),
(82,	'',	'email',	'',	'text',	1,	17,	'eMail',	'normal',	'',	'2008-10-17 13:07:06',	7,	''),
(83,	'',	'website',	'',	'text',	1,	17,	'Website',	'normal',	'',	'2008-10-17 13:07:14',	8,	''),
(84,	'',	'kommentar',	'',	'textarea',	0,	17,	'Comment',	'normal',	'',	'2008-10-17 13:07:25',	9,	''),
(85,	'',	'profil',	'',	'textarea',	1,	17,	'Company profile',	'normal',	'',	'2008-10-17 13:07:43',	10,	''),
(86,	'',	'anrede',	'',	'select',	1,	18,	'Title',	'normal',	'Ms;Mr',	'2008-10-17 13:21:07',	1,	''),
(87,	'',	'vorname',	'',	'text',	1,	18,	'First name',	'normal',	'',	'2008-10-17 13:21:41',	2,	''),
(88,	'',	'nachname',	'',	'text',	1,	18,	'Last name',	'normal',	'',	'2008-10-17 13:22:01',	3,	''),
(89,	'',	'email',	'',	'text',	1,	18,	'eMail-Adress',	'normal',	'',	'2008-10-17 13:22:18',	4,	''),
(90,	'',	'telefon',	'',	'text',	0,	18,	'Phone',	'normal',	'',	'2008-10-17 13:22:28',	5,	''),
(91,	'',	'betreff',	'',	'text',	1,	18,	'Subject',	'normal',	'',	'2008-10-17 13:22:38',	6,	''),
(92,	'',	'kommentar',	'',	'textarea',	1,	18,	'Comment',	'normal',	'',	'2008-10-17 13:22:45',	7,	''),
(93,	'',	'firma',	'',	'checkbox',	0,	19,	'Company (If so, please mark)',	'',	'1',	'2008-10-17 13:45:44',	1,	''),
(94,	'',	'kdnr',	'',	'text',	1,	19,	'Customer no. (See invoice)',	'normal',	'',	'2008-10-17 13:46:04',	2,	''),
(95,	'',	'email',	'',	'text',	1,	19,	'Email address',	'normal',	'',	'2008-10-17 13:46:27',	3,	''),
(96,	'',	'rechnung',	'',	'text',	1,	19,	'Invoice number',	'normal',	'',	'2008-10-17 13:47:03',	4,	''),
(97,	'',	'artikel',	'',	'textarea',	1,	19,	'Article number(s)',	'normal',	'',	'2008-10-17 13:47:43',	5,	''),
(98,	'',	'fehler',	'',	'textarea',	1,	19,	'Detailed error description',	'normal',	'',	'2008-10-17 13:48:54',	6,	''),
(99,	'',	'rechner',	'',	'textarea',	0,	19,	'On which computer type does the defective product run?',	'normal',	'',	'2008-10-17 14:02:03',	7,	''),
(100,	'',	'system',	'',	'textarea',	0,	19,	'With which operating system do you work?',	'normal',	'',	'2008-10-17 14:02:36',	8,	''),
(101,	'',	'wie',	'',	'select',	1,	19,	'How does the problem occur?',	'normal',	'sporadically;permanently',	'2008-10-17 14:02:55',	9,	''),
(102,	'',	'kdnr',	'',	'text',	1,	20,	'Customer no. (See invoice)',	'normal',	'',	'2008-10-17 14:21:28',	1,	''),
(103,	'',	'email',	'',	'text',	1,	20,	'eMail-Adress',	'normal',	'',	'2008-10-17 14:22:12',	2,	''),
(104,	'',	'rechnung',	'',	'text',	1,	20,	'Invoice number',	'normal',	'',	'2008-10-17 14:22:43',	3,	''),
(105,	'',	'artikel',	'',	'textarea',	1,	20,	'Articlenumber(s)',	'normal',	'',	'2008-10-17 14:23:15',	4,	''),
(106,	'',	'info',	'',	'textarea',	0,	20,	'Comment',	'normal',	'',	'2008-10-17 14:23:37',	5,	''),
(107,	'',	'anrede',	'',	'select',	1,	21,	'Title',	'normal',	'Ms;Mr',	'2008-10-17 14:45:21',	1,	''),
(108,	'',	'vorname',	'',	'text',	1,	21,	'First name',	'normal',	'',	'2008-10-17 14:46:11',	2,	''),
(109,	'',	'nachname',	'',	'text',	1,	21,	'Last name',	'normal',	'',	'2008-10-17 14:46:31',	3,	''),
(110,	'',	'email',	'',	'text',	1,	21,	'eMail-Adress',	'normal',	'',	'2008-10-17 14:46:49',	4,	''),
(111,	'',	'telefon',	'',	'text',	0,	21,	'Phone',	'normal',	'',	'2008-10-17 14:47:00',	5,	''),
(112,	'',	'inquiry',	'',	'textarea',	1,	21,	'Inquiry',	'normal',	'',	'2008-10-17 14:47:25',	6,	''),
(113,	'',	'name',	'',	'text',	1,	22,	'Name',	'normal',	'',	'2009-04-15 22:20:30',	1,	'name'),
(114,	'',	'email',	'',	'email',	1,	22,	'eMail',	'normal',	'',	'2009-04-15 22:20:37',	2,	'email'),
(115,	'',	'betreff',	'',	'text',	1,	22,	'Betreff',	'normal',	'',	'2009-04-15 22:20:45',	3,	'subject'),
(116,	'',	'kommentar',	'',	'textarea',	1,	22,	'Kommentar',	'normal',	'',	'2009-04-15 22:21:07',	4,	'message'),
(117,	'',	'sordernumber',	'',	'hidden',	0,	16,	'Artikelnummer',	'normal',	'',	'2019-12-06 09:19:55',	7,	''),
(118,	'',	'sordernumber',	'',	'hidden',	0,	21,	'Order number',	'normal',	'',	'2019-12-06 09:19:55',	7,	'');

DROP TABLE IF EXISTS `s_content_types`;
CREATE TABLE `s_content_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `internalName` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `source` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `config` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_core_acl_privilege_requirements`;
CREATE TABLE `s_core_acl_privilege_requirements` (
  `privilege_id` int unsigned NOT NULL,
  `required_privilege_id` int unsigned NOT NULL,
  PRIMARY KEY (`privilege_id`,`required_privilege_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_acl_privilege_requirements` (`privilege_id`, `required_privilege_id`) VALUES
(5,	92),
(5,	99),
(5,	101),
(5,	102),
(6,	6),
(6,	92),
(6,	99),
(6,	101),
(6,	102),
(7,	6),
(8,	6),
(17,	16),
(18,	16),
(20,	22),
(21,	22),
(22,	99),
(22,	119),
(22,	169),
(22,	174),
(23,	24),
(26,	24),
(27,	22),
(31,	30),
(32,	30),
(33,	16),
(33,	22),
(33,	110),
(33,	115),
(33,	169),
(34,	16),
(34,	22),
(34,	110),
(34,	115),
(34,	169),
(35,	34),
(36,	34),
(37,	34),
(38,	34),
(39,	22),
(39,	99),
(39,	110),
(40,	39),
(41,	39),
(42,	39),
(45,	44),
(46,	44),
(47,	110),
(48,	110),
(49,	48),
(50,	48),
(58,	22),
(59,	58),
(60,	58),
(61,	62),
(63,	62),
(64,	62),
(65,	62),
(65,	92),
(66,	62),
(66,	92),
(67,	66),
(68,	66),
(71,	70),
(72,	70),
(73,	99),
(74,	99),
(75,	74),
(76,	74),
(77,	74),
(78,	74),
(79,	48),
(82,	81),
(83,	84),
(84,	30),
(84,	115),
(85,	84),
(86,	22),
(87,	22),
(88,	87),
(89,	87),
(91,	99),
(91,	101),
(91,	102),
(91,	103),
(91,	110),
(91,	113),
(92,	99),
(92,	110),
(93,	92),
(93,	103),
(94,	92),
(95,	22),
(98,	56),
(100,	99),
(102,	99),
(103,	99),
(104,	92),
(104,	99),
(105,	104),
(106,	104),
(110,	92),
(110,	99),
(110,	102),
(110,	119),
(110,	148),
(112,	110),
(113,	102),
(113,	110),
(116,	115),
(117,	115),
(118,	101),
(118,	102),
(118,	103),
(118,	119),
(119,	16),
(119,	92),
(119,	99),
(119,	104),
(119,	110),
(120,	119),
(121,	119),
(122,	123),
(123,	22),
(123,	92),
(123,	99),
(124,	123),
(126,	125),
(127,	125),
(133,	132),
(134,	132),
(137,	136),
(138,	136),
(139,	136),
(140,	136),
(146,	102),
(146,	141),
(148,	110),
(149,	148),
(150,	148),
(151,	148),
(152,	112),
(152,	113),
(152,	148),
(153,	113),
(153,	148),
(153,	154),
(154,	148),
(154,	152),
(154,	153),
(156,	155),
(157,	155),
(158,	155),
(159,	155),
(160,	155),
(161,	155),
(163,	162),
(163,	182),
(164,	162),
(164,	182),
(166,	167),
(168,	81),
(169,	22),
(170,	169),
(171,	169),
(174,	169),
(176,	48),
(181,	136),
(182,	162),
(187,	186),
(188,	186);

DROP TABLE IF EXISTS `s_core_acl_privileges`;
CREATE TABLE `s_core_acl_privileges` (
  `id` int NOT NULL AUTO_INCREMENT,
  `resourceID` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `resourceID` (`resourceID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_acl_privileges` (`id`, `resourceID`, `name`) VALUES
(1,	1,	'create'),
(2,	1,	'read'),
(3,	1,	'update'),
(4,	1,	'delete'),
(5,	2,	'create'),
(6,	2,	'read'),
(7,	2,	'update'),
(8,	2,	'delete'),
(10,	3,	'create'),
(11,	3,	'update'),
(12,	3,	'delete'),
(15,	4,	'create'),
(16,	4,	'read'),
(17,	4,	'update'),
(18,	4,	'delete'),
(19,	5,	'create'),
(20,	5,	'update'),
(21,	5,	'delete'),
(22,	5,	'read'),
(23,	6,	'createupdate'),
(24,	6,	'read'),
(26,	6,	'delete'),
(27,	5,	'detail'),
(28,	5,	'perform_order'),
(29,	7,	'create'),
(30,	7,	'read'),
(31,	7,	'update'),
(32,	7,	'delete'),
(33,	8,	'create'),
(34,	8,	'read'),
(35,	8,	'update'),
(36,	8,	'delete'),
(37,	8,	'export'),
(38,	8,	'generate'),
(39,	9,	'read'),
(40,	9,	'accept'),
(41,	9,	'comment'),
(42,	9,	'delete'),
(43,	10,	'create'),
(44,	10,	'read'),
(45,	10,	'update'),
(46,	10,	'delete'),
(47,	11,	'create'),
(48,	11,	'read'),
(49,	11,	'update'),
(50,	11,	'delete'),
(56,	13,	'read'),
(57,	14,	'create'),
(58,	14,	'read'),
(59,	14,	'update'),
(60,	14,	'delete'),
(61,	15,	'create'),
(62,	15,	'read'),
(63,	15,	'update'),
(64,	15,	'delete'),
(65,	16,	'create'),
(66,	16,	'read'),
(67,	16,	'update'),
(68,	16,	'delete'),
(69,	17,	'create'),
(70,	17,	'read'),
(71,	17,	'update'),
(72,	17,	'delete'),
(73,	18,	'createGroup'),
(74,	18,	'read'),
(75,	18,	'createSite'),
(76,	18,	'updateSite'),
(77,	18,	'deleteSite'),
(78,	18,	'deleteGroup'),
(79,	11,	'generate'),
(80,	19,	'read'),
(81,	20,	'read'),
(82,	20,	'delete'),
(83,	21,	'save'),
(84,	21,	'read'),
(85,	21,	'delete'),
(86,	22,	'create'),
(87,	22,	'read'),
(88,	22,	'update'),
(89,	22,	'delete'),
(90,	22,	'statistic'),
(91,	23,	'create'),
(92,	23,	'read'),
(93,	23,	'update'),
(94,	23,	'delete'),
(95,	24,	'read'),
(96,	25,	'delete'),
(97,	25,	'read'),
(98,	26,	'read'),
(99,	27,	'read'),
(100,	27,	'delete'),
(101,	27,	'create'),
(102,	27,	'upload'),
(103,	27,	'update'),
(104,	28,	'read'),
(105,	28,	'delete'),
(106,	28,	'update'),
(107,	28,	'create'),
(108,	28,	'comments'),
(110,	29,	'read'),
(112,	29,	'delete'),
(113,	29,	'save'),
(114,	30,	'create'),
(115,	30,	'read'),
(116,	30,	'update'),
(117,	30,	'delete'),
(118,	31,	'create'),
(119,	31,	'read'),
(120,	31,	'update'),
(121,	31,	'delete'),
(122,	32,	'delete'),
(123,	32,	'read'),
(124,	32,	'write'),
(125,	33,	'read'),
(126,	33,	'update'),
(127,	33,	'clear'),
(131,	35,	'create'),
(132,	35,	'read'),
(133,	35,	'update'),
(134,	35,	'delete'),
(136,	36,	'read'),
(137,	36,	'upload'),
(138,	36,	'download'),
(139,	36,	'install'),
(140,	36,	'update'),
(141,	37,	'read'),
(142,	37,	'swag-visitors-customers-widget'),
(143,	37,	'swag-last-orders-widget'),
(144,	37,	'swag-sales-widget'),
(145,	37,	'swag-merchant-widget'),
(146,	37,	'swag-upload-widget'),
(147,	37,	'swag-notice-widget'),
(148,	38,	'read'),
(149,	38,	'createFilters'),
(150,	38,	'editFilters'),
(151,	38,	'deleteFilters'),
(152,	38,	'editSingleArticle'),
(153,	38,	'doMultiEdit'),
(154,	38,	'doBackup'),
(155,	39,	'read'),
(156,	39,	'preview'),
(157,	39,	'changeTheme'),
(158,	39,	'createTheme'),
(159,	39,	'uploadTheme'),
(160,	39,	'configureTheme'),
(161,	39,	'configureSystem'),
(162,	40,	'read'),
(163,	40,	'update'),
(164,	40,	'skipUpdate'),
(166,	41,	'update'),
(167,	41,	'read'),
(168,	20,	'system'),
(169,	42,	'read'),
(170,	42,	'save'),
(171,	42,	'delete'),
(172,	42,	'search_index'),
(174,	42,	'charts'),
(175,	16,	'sql_rule'),
(176,	11,	'sqli'),
(177,	43,	'read'),
(178,	43,	'submit'),
(179,	43,	'manage'),
(180,	14,	'deleteDocument'),
(181,	36,	'notification'),
(182,	40,	'notification'),
(183,	44,	'read'),
(184,	44,	'resend'),
(185,	44,	'manage'),
(186,	45,	'read'),
(187,	45,	'edit'),
(188,	45,	'delete');

DROP TABLE IF EXISTS `s_core_acl_resources`;
CREATE TABLE `s_core_acl_resources` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `pluginID` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_acl_resources` (`id`, `name`, `pluginID`) VALUES
(1,	'debug_test',	NULL),
(2,	'banner',	NULL),
(4,	'supplier',	NULL),
(5,	'customer',	NULL),
(6,	'form',	NULL),
(7,	'premium',	NULL),
(8,	'voucher',	NULL),
(9,	'vote',	NULL),
(10,	'mail',	NULL),
(11,	'productfeed',	NULL),
(13,	'overview',	NULL),
(14,	'order',	NULL),
(15,	'payment',	NULL),
(16,	'shipping',	NULL),
(17,	'snippet',	NULL),
(18,	'site',	NULL),
(19,	'systeminfo',	NULL),
(20,	'log',	NULL),
(21,	'riskmanagement',	NULL),
(22,	'partner',	NULL),
(23,	'category',	NULL),
(24,	'notification',	NULL),
(25,	'canceledorder',	NULL),
(26,	'analytics',	NULL),
(27,	'mediamanager',	NULL),
(28,	'blog',	NULL),
(29,	'article',	NULL),
(30,	'config',	NULL),
(31,	'emotion',	NULL),
(32,	'newslettermanager',	NULL),
(33,	'performance',	NULL),
(35,	'usermanager',	NULL),
(36,	'pluginmanager',	NULL),
(37,	'widgets',	NULL),
(38,	'articlelist',	NULL),
(39,	'theme',	NULL),
(40,	'swagupdate',	NULL),
(41,	'attributes',	NULL),
(42,	'customerstream',	NULL),
(43,	'benchmark',	NULL),
(44,	'maillog',	NULL),
(45,	'contenttypemanager',	NULL);

DROP TABLE IF EXISTS `s_core_acl_roles`;
CREATE TABLE `s_core_acl_roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `roleID` int NOT NULL,
  `resourceID` int DEFAULT NULL,
  `privilegeID` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roleID` (`roleID`,`resourceID`,`privilegeID`),
  KEY `resourceID` (`resourceID`),
  KEY `privilegeID` (`privilegeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_acl_roles` (`id`, `roleID`, `resourceID`, `privilegeID`) VALUES
(1,	1,	NULL,	NULL);

DROP TABLE IF EXISTS `s_core_auth`;
CREATE TABLE `s_core_auth` (
  `id` int NOT NULL AUTO_INCREMENT,
  `roleID` int NOT NULL,
  `username` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `encoder` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'LegacyBackendMd5',
  `apiKey` varchar(40) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `localeID` int NOT NULL,
  `sessionID` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `lastlogin` datetime DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `email` varchar(120) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `active` int NOT NULL DEFAULT '0',
  `failedlogins` int NOT NULL,
  `lockeduntil` datetime DEFAULT NULL,
  `extended_editor` tinyint unsigned NOT NULL DEFAULT '0',
  `disabled_cache` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

DROP TABLE IF EXISTS `s_core_auth_attributes`;
CREATE TABLE `s_core_auth_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `authID` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `authID` (`authID`),
  CONSTRAINT `s_core_auth_attributes_ibfk_1` FOREIGN KEY (`authID`) REFERENCES `s_core_auth` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_core_auth_config`;
CREATE TABLE `s_core_auth_config` (
  `user_id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `config` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`user_id`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_core_auth_roles`;
CREATE TABLE `s_core_auth_roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `parentID` int DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `source` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `enabled` int NOT NULL,
  `admin` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_auth_roles` (`id`, `parentID`, `name`, `description`, `source`, `enabled`, `admin`) VALUES
(1,	NULL,	'local_admins',	'Default group that gains access to all shop functions',	'build-in',	1,	1);

DROP TABLE IF EXISTS `s_core_config_element_translations`;
CREATE TABLE `s_core_config_element_translations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `element_id` int unsigned NOT NULL,
  `locale_id` int unsigned NOT NULL,
  `label` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `element_id` (`element_id`,`locale_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_config_element_translations` (`id`, `element_id`, `locale_id`, `label`, `description`) VALUES
(1,	225,	2,	'Controller selection',	NULL),
(4,	224,	2,	'Display recently viewed items',	NULL),
(5,	269,	2,	'Activate expandable menu in storefront',	NULL),
(6,	273,	2,	'Display item comparison',	NULL),
(11,	186,	2,	'VAT vouchers',	NULL),
(12,	188,	2,	'VAT discounts',	NULL),
(13,	189,	2,	'Customer reviews must be approved',	NULL),
(14,	190,	2,	'Automatic item number suggestions',	NULL),
(15,	191,	2,	'Prefix for automatically generated item numbers',	NULL),
(16,	192,	2,	'Deactivate product evaluations ',	NULL),
(17,	193,	2,	'Automatically remind customer to submit reviews',	'Remind the customer via email of pending article reviews'),
(18,	194,	2,	'Days to wait before sending reminder',	'Days until the customer is reminded via Email of a pending article review'),
(19,	195,	2,	'Set tax for discounts dynamically',	NULL),
(22,	231,	2,	'Maximum number of items to display',	NULL),
(29,	252,	2,	'Cache search',	NULL),
(31,	254,	2,	'Close shop due to maintenance',	NULL),
(32,	255,	2,	'IP excluded from closure',	NULL),
(35,	270,	2,	'Number of tiers',	NULL),
(36,	271,	2,	'Activate caching',	NULL),
(37,	272,	2,	'Caching time',	NULL),
(38,	274,	2,	'Maximum number of items to be compared',	NULL),
(43,	286,	2,	'Items per page',	NULL),
(44,	287,	2,	'Standard sorting of listings',	NULL),
(46,	289,	2,	'Number of days items are considered new',	NULL),
(47,	290,	2,	'Number of days considered for top sellers',	NULL),
(48,	291,	2,	'Number of top sellers for charts',	NULL),
(49,	292,	2,	'Selection of items per page',	NULL),
(50,	293,	2,	'Available listing layouts',	NULL),
(51,	294,	2,	'Maximum number of items selectable via pull-down menu',	NULL),
(52,	295,	2,	'Text for unavailable items',	NULL),
(53,	296,	2,	'Number of similar items for cross selling',	NULL),
(54,	297,	2,	'Number of items \"customers also bought\"',	NULL),
(55,	298,	2,	'Standard template for new categories',	NULL),
(56,	299,	2,	'Number of days to be considered for top seller creation',	NULL),
(57,	300,	2,	'Number of automatically determined similar products (detail page)',	'If no similar articles are found, Shopware can automatically generates alternative suggestions. You can activate these suggestions if you enter a number greater than 0. May decrease performance when loading these articles.'),
(58,	301,	2,	'Show delivery time in shopping cart',	NULL),
(60,	303,	2,	'Request form ID',	NULL),
(61,	304,	2,	'Minimum shopping cart value for offering individual requests',	NULL),
(62,	305,	2,	'Zoom viewer instead of light box on detail page ',	NULL),
(67,	310,	2,	'Hide \"add to shopping cart\" option if item is out-of-stock',	'Customers can choose to be informed per email when an item is \"now in stock\".'),
(68,	311,	2,	'Display inventory shortages in shopping cart',	NULL),
(69,	312,	2,	'Jump to detail if only one item is available',	NULL),
(71,	314,	2,	'Available templates for detail page',	NULL),
(73,	317,	2,	'Minimum password length (registration)',	NULL),
(74,	318,	2,	'Standard payment method ID (registration)',	NULL),
(75,	319,	2,	'Standard recipient group ID for registered customers (system / newsletter)',	NULL),
(76,	320,	2,	'Generate customer numbers automatically',	NULL),
(77,	321,	2,	'Deactivate AGB terms checkbox on checkout page',	NULL),
(78,	322,	2,	'Display country and state fields in shipping address forms',	NULL),
(79,	323,	2,	'Data protection conditions must be accepted via checkbox',	'Please activate the checkbox \"Data protection information will be shown\" first.'),
(80,	324,	2,	'Default payment method ID',	NULL),
(81,	325,	2,	'Confirm customer email addresses',	'Customers must enter email addresses twice, in order to avoid typing mistakes.'),
(82,	326,	2,	'Check extended fields in newsletter registration',	NULL),
(83,	327,	2,	'Deactivate \"no customer account\"',	NULL),
(84,	585,	2,	'Exclude IP from statistics',	NULL),
(85,	586,	2,	'Google Analytics ID',	NULL),
(86,	587,	2,	'Google Conversion ID',	NULL),
(87,	588,	2,	'Anonymous IP address',	NULL),
(88,	589,	2,	'Cache controller/Times',	NULL),
(89,	590,	2,	'NoCache Controller/Tags',	NULL),
(90,	591,	2,	'Alternative Proxy URL',	NULL),
(91,	592,	2,	'Admin view',	NULL),
(95,	608,	2,	'Activate SQL injection protection',	NULL),
(96,	609,	2,	'SQL injection filter',	NULL),
(97,	610,	2,	'Activate XXS protection',	NULL),
(98,	611,	2,	'XXS filter',	NULL),
(99,	612,	2,	'Activate Remote File Inclusion protection',	NULL),
(100,	613,	2,	'RemoteFileInclusion-filter',	NULL),
(101,	614,	2,	'Vouchers designated as',	NULL),
(102,	615,	2,	'Minimum search term length',	NULL),
(103,	620,	2,	'Discounts designated as',	NULL),
(104,	623,	2,	'Shortages designated as',	NULL),
(105,	624,	2,	'Disable order confirmation to shop owner',	NULL),
(106,	625,	2,	'Blacklist for keywords',	NULL),
(107,	626,	2,	'Surcharges on payment methods designated as',	NULL),
(108,	627,	2,	'Designation percentual deduction on payment method',	NULL),
(109,	628,	2,	'Order number for discounts',	NULL),
(110,	629,	2,	'Order  number for shortages',	NULL),
(111,	630,	2,	'Surcharge on payment method',	NULL),
(112,	631,	2,	'Number of live search results',	NULL),
(114,	633,	2,	'Send registration confirmation to shop owner in CC',	NULL),
(115,	634,	2,	'Double opt in for newsletter subscriptions',	NULL),
(116,	635,	2,	'Double opt in for blog comments & customer reviews',	NULL),
(117,	636,	2,	'Order number for deduction dispatch rule',	NULL),
(118,	637,	2,	'Deduction dispatch rule designated as',	NULL),
(119,	641,	2,	'Order status - Changes to CC addresses',	NULL),
(120,	642,	2,	'Extended SQL query',	NULL),
(121,	643,	2,	'Block orders with no available shipping type',	NULL),
(122,	646,	2,	'Only use lower case letters in URLs',	NULL),
(124,	649,	2,	'Prepare meta description of categories / items',	NULL),
(125,	650,	2,	'Remove Category ID from URL',	NULL),
(126,	651,	2,	'SEO noindex queries',	NULL),
(127,	652,	2,	'SEO noindex viewsports',	NULL),
(129,	654,	2,	'Remove HTML comments',	NULL),
(130,	655,	2,	'Query aliases',	NULL),
(131,	656,	2,	'SEO follow backlinks',	NULL),
(133,	658,	2,	'Last update',	NULL),
(134,	659,	2,	'SEO URLs caching timetable',	NULL),
(140,	665,	2,	'Mark VAT ID number as required for company customers',	NULL),
(142,	667,	2,	'SEO URLs item template',	NULL),
(143,	668,	2,	'SEO URLs category template',	NULL),
(145,	670,	2,	'Other SEO URLs',	NULL),
(148,	673,	2,	'Shop name',	NULL),
(149,	674,	2,	'Shop owner email',	NULL),
(150,	675,	2,	'Address',	NULL),
(152,	677,	2,	'Bank account',	NULL),
(153,	843,	2,	'Captcha font color (R,G,B)',	NULL),
(154,	844,	2,	'Bot list',	NULL),
(155,	845,	2,	'Version',	NULL),
(156,	846,	2,	'Revision',	NULL),
(157,	847,	2,	'Base file',	NULL),
(158,	848,	2,	'ESD key',	NULL),
(159,	849,	2,	'Available templates for blog detail page',	NULL),
(161,	851,	2,	'Factor for accurate hits ',	NULL),
(162,	852,	2,	'Last update',	NULL),
(163,	853,	2,	'Factor for inaccurate hits ',	NULL),
(164,	854,	2,	'Minimum relevance for top items (%)',	NULL),
(165,	855,	2,	'Maximum distance allowed for partial names (%)',	NULL),
(166,	856,	2,	'Factor for partial hits',	NULL),
(169,	859,	2,	'Selection results per page',	NULL),
(170,	860,	2,	'ESD-Min-Serials',	NULL),
(171,	867,	2,	'Display \"customers also bought\" recommendations',	NULL),
(172,	868,	2,	'Number of items per page in the list',	NULL),
(173,	869,	2,	'Maximum number of pages in the list',	NULL),
(174,	870,	2,	'Display \"customers also viewed\" recommendations',	NULL),
(175,	871,	2,	'Number of items per page in the list',	NULL),
(176,	872,	2,	'Maximum number of pages in the list',	NULL),
(177,	873,	2,	'Display shop cancellation policy',	NULL),
(178,	874,	2,	'Display newsletter registration',	NULL),
(179,	875,	2,	'Display bank detail notice',	NULL),
(180,	876,	2,	'Display further notices',	'Snippet: ConfirmTextOrderDefault'),
(181,	877,	2,	'Display further options',	'Add product, comment function'),
(182,	878,	2,	'Show Bonus System (if installed)',	NULL),
(183,	879,	2,	'Display \"free with purchase\" items',	NULL),
(184,	880,	2,	'Display country descriptions',	NULL),
(185,	881,	2,	'Display information for net orders',	NULL),
(189,	885,	2,	'Template for essential characteristics',	NULL),
(190,	886,	2,	'PHP timeout',	NULL),
(191,	887,	2,	'Selectable languages ',	NULL),
(192,	888,	2,	'SEO URLs blog template',	NULL),
(193,	889,	2,	'Display item details in modal box',	NULL),
(197,	893,	2,	'Company',	NULL),
(198,	894,	2,	'SEO URLs landing page template',	NULL),
(199,	897,	2,	'All-inclusive surcharges on payment methods designated as',	NULL),
(200,	898,	2,	'Order number for all-inclusive surcharges on payment methods designated as',	NULL),
(203,	909,	2,	'Always display item descriptions in listing views',	'Affected views: Top seller, category listings, emotions'),
(205,	236,	2,	'Message ID hostname',	'Will be received in headers on a default HELO string. If not defined, the value returned from SERVER_NAME, \"localhost.localdomain\" will be used.'),
(206,	235,	2,	'Sending method',	'mail, SMTP or file'),
(207,	238,	2,	'Default port',	'Sets the default SMTP server port.'),
(208,	239,	2,	'Connection prefix',	'\"\", ssl, or tls'),
(209,	237,	2,	'Mail host',	'You can also specify a different port by using this format: [hostname:port] - e.g., smtp1.example.com:25'),
(210,	242,	2,	'Connection auth',	'plain, login or crammd5'),
(211,	240,	2,	'SMTP username',	NULL),
(212,	241,	2,	'SMTP password',	NULL),
(213,	901,	2,	'Own filter',	NULL),
(214,	905,	2,	'Check current password at password-change requests',	NULL),
(215,	900,	2,	'Number of mails sent per call',	NULL),
(216,	938,	2,	' Release download with payment status',	'Define the payment status in which a download of ESD items is possible.'),
(217,	939,	2,	'Always display the article preview image',	'e.g. in listings or when using selection or picture configurator with no selected variant. Important: If you filter on expanded variant groups, this configuration will be ignored.'),
(218,	940,	2,	'Send order mail',	NULL),
(219,	0,	2,	'Force http canonical url',	NULL),
(220,	942,	2,	'Treat phone field as required',	'Note that you must configure the asterisk indication in the snippet RegisterLabelPhone'),
(222,	910,	2,	'Password algorithm',	'Note that some hashing functions are only displayed if the required PHP version is installed <br>If \"Auto\" is selected, bcrypt is used. If bcrypt is not available, sha256 is used.'),
(223,	911,	2,	'Live migration',	'Should available user passwords be rehashed with other algorithms on next login? This is done automatically in the background, so that passwords can be gradually converted to a new algorithm.'),
(224,	912,	2,	'Bcrypt iterations',	'The higher the number of iterations, the more difficult it is for a potential attacker to calculate the clear-text password for the encrypted password.'),
(225,	913,	2,	'Sha256 iterations',	'The higher the number of iterations, the more difficult it is for a potential attacker to calculate the clear-text password for the encrypted password.'),
(226,	933,	2,	'Admin view',	'Deactivate cache for item preview in express checkout'),
(227,	934,	2,	'Controller cache timeouts',	NULL),
(228,	935,	2,	'Skip caching for controllers / tags',	NULL),
(229,	936,	2,	'Alternative proxy URL',	'Prepend \"http://\" to HTTP proxy links'),
(230,	937,	2,	'Activate cache clearing',	'Enable automatic cache clearing.'),
(232,	943,	2,	'Creditor name',	'Name of the creditor to be included in the mandate.'),
(233,	944,	2,	'Header text',	'Header text of the mandate.'),
(234,	945,	2,	'Creditor number',	'Number of the creditor to be included in the mandate.'),
(235,	946,	2,	'Send email',	'Send email to the customer with the attached SEPA mandate file.'),
(236,	947,	2,	'Show SEPA\'s BIC field',	'Allow customer to specify its BIC when filling in SEPA payment data.'),
(237,	948,	2,	'Require SEPA\'s BIC field',	'Require customer to specify its BIC when filling in SEPA payment data. This option is ignored if the field is hidden.'),
(238,	949,	2,	'Show SEPA\'s bank name field',	'Allow customer to specify its bank name when filling in SEPA payment data.'),
(239,	950,	2,	'Require SEPA\'s bank name field',	'Require customer to specify its bank name when filling in SEPA payment data. This option is ignored if the field is hidden.'),
(241,	952,	2,	'Supplier SEO',	NULL),
(242,	953,	2,	'Supplier SEO URLs template',	NULL),
(243,	955,	2,	'Maximum age for referrer statistics',	'Old referrer data will be deleted by the cron job call if active'),
(244,	956,	2,	'Maximum age for impression statistics',	'Old impression data will be deleted by the cron job call if active'),
(245,	957,	2,	'Show recommend product',	NULL),
(246,	941,	2,	'Force http canonical url',	'This option does not take effect if the option \"Use always SSL\" is activated.'),
(247,	958,	2,	'Storage period in days',	NULL),
(248,	959,	2,	'Download strategy for ESD files',	'<b>Warning</b>: Changing this setting might break ESD downloads. If not sure, use default (PHP)<br><br>Strategy to generate the download links for ESD files. If you use an external storage, the method PHP will always be used for security reasons.<br><b>Link</b>: Better performance, but possibly insecure <br><b>PHP</b>: More secure, but memory consuming, especially for bigger files <br><b>X-Sendfile</b>: Secure and lightweight, but requires X-Sendfile module and Apache2 web server <br><b>X-Accel</b>: Equivalent to X-Sendfile, but requires Nginx web server instead'),
(249,	965,	1,	'Feedback senden',	NULL),
(252,	968,	2,	'Show phone number field',	NULL),
(253,	969,	2,	'Password must be entered twice.',	'Password must be entered twice in order to avoid typing errors'),
(254,	970,	2,	'Show Birthday field',	NULL),
(255,	971,	2,	'Birthday is required',	NULL),
(256,	972,	2,	'Show additional address line 1',	''),
(257,	973,	2,	'Show additional address line 2',	''),
(258,	974,	2,	'Treat additional address line 1 as required',	''),
(259,	975,	2,	'Treat additional address line 2 as required',	''),
(260,	954,	2,	'Report errors to shop owner',	NULL),
(261,	907,	2,	'Remove \"Shopware\" from URLs',	'Remove \"shopware.php\" from URLs. Prevents search engines from incorrectly identifying duplicate content in the shop. If mod_rewrite is not available in Apache, this option must be disabled.'),
(262,	927,	2,	'Move categories in batch mode',	NULL),
(263,	980,	2,	'Show shipping costs calculation in shopping cart',	'If enabled, a shipping cost calculator will be displayed in the cart page. This is only available for customers who haven\'t logged in'),
(264,	981,	2,	'\"Page not found\" destination',	'When the user requests a non-existent page, he will be shown the following page.'),
(265,	982,	2,	'\"Page not found\" error code',	'HTTP code used in \"Page not found\" responses'),
(266,	983,	2,	'Show \"I am\" select field',	'If this option is false, all registrations will be done as a private customer. This option only affects the registration, it is still available when editing user data.'),
(267,	984,	2,	'Shop is family friendly',	'Will set the meta tag \"isFamilyFriendly\" for search engines'),
(268,	985,	2,	'Custom site SEO URLs template',	NULL),
(269,	986,	2,	'Form SEO URLs template',	NULL),
(270,	976,	1,	'Anzahl der Produkte pro Queue-Request',	'Anzahl der Produkte, die je Request in den Queue geladen werden. Je größer die Zahl, desto länger dauern die Requests. Zu kleine Werte erhöhen den Overhead.'),
(271,	977,	1,	'Anzahl der Produkte pro Batch-Request',	'Anzahl der Produkte, die je Request verarbeitet werden. Je größer die Zahl, desto länger dauern die Requests. Zu kleine Werte erhöhen den Overhead.'),
(272,	978,	1,	'Rückgängig-Funktion aktivieren',	'Ermöglicht es, einzelne Mehrfach-Änderungen rückgängig zu machen. Diese Funktion ersetzt kein Backup.'),
(273,	979,	1,	'Automatische Cache-Invalidierung aktivieren',	'Invalidiert den Cache für jedes Produkt, das geändert wird. Bei vielen Produkten kann sich das negativ auf die Dauer des Vorgangs auswirken. Es wird daher empfohlen, den Cache nach Ende des Vorgangs manuell zu leeren.'),
(274,	992,	2,	'Product layout',	'Product layout allows you to control how your products are presented on the search result page. Choose between three different layouts to fine-tune your product display.'),
(275,	993,	2,	'Do not show on sale products that are out of stock ',	'If inactive, the listing may take longer to load if the split variant filtering is used. This effect does not occur when using ElasticSearch.'),
(276,	994,	2,	'Email header plaintext',	NULL),
(277,	995,	2,	'Email footer plaintext',	NULL),
(278,	996,	2,	'Email header HTML',	NULL),
(279,	997,	2,	'Email footer HTML',	NULL),
(280,	998,	2,	'Show instant downloads in account',	'Instant downloads can already be downloaded from the order details page.'),
(281,	999,	2,	'Run \'First run wizard\' on next backend execution',	''),
(282,	1000,	2,	'Show checkbox for the right of revocations for ESD products',	NULL),
(283,	1001,	2,	'Product free text field for service products',	NULL),
(284,	1002,	2,	'Use prev/next-tag on paginated sites',	'If active, use prev/next-tag instead of the Canoncial-tag on paginated sites'),
(285,	1003,	2,	'Thumbnail noise filter',	'Produces clearer thumbnails. May increase thumbnail generation time.'),
(286,	1005,	2,	'Display related articles on \"Article not found\" page',	'If enabled, \"Article not found\" page will display related articles suggestions. Disable to use the standard \"Page not found\" page'),
(287,	1006,	2,	'Show zip code field before city field',	'Determines if the zip code field should be shown before or after the the city field. Only applicable for Shopware 5 themes'),
(289,	1009,	2,	'Consider product minimum order quantity for cheapest price calculation',	NULL),
(290,	1010,	2,	'Consider product graduatation for cheapest price calculation',	NULL),
(291,	1013,	2,	'Use \"and\" search logic',	'The search will only return results that match all the search terms.'),
(292,	1014,	2,	'Always select payment method in checkout',	NULL),
(293,	1015,	2,	'Ajax timeout',	'Defines the max execution time for ExtJS ajax requests (in seconds)'),
(294,	1016,	2,	'Available salutations',	'Allows to configure the available shop salutations in frontend registration and account. Inserted keys are generated automatically as snippet inside the frontend/salutation namespace.'),
(295,	1017,	2,	'Show title field',	NULL),
(296,	1021,	2,	'Send confirmation email after registration',	NULL),
(297,	1022,	2,	'Maximum number of items per page',	NULL),
(298,	1023,	2,	'Use strip_tags globally',	'When activated, each form input in the frontend is filtered using strip_tags.'),
(299,	1025,	2,	'Captcha Method',	'Choose the method to protect the forms against spam bots.'),
(300,	1026,	2,	'Disable after login',	'If set to yes, captchas are disabled for logged in customers'),
(301,	1027,	2,	'Display buy button in listing',	''),
(302,	1028,	2,	'Show cookie hint',	'If this option is active, a notification message will be displayed informing the user of the cookie guidelines. The content can be edited via the text editor module.'),
(303,	1029,	2,	'Link to the data privacy statement for cookies',	NULL),
(305,	991,	2,	'Default category sorting',	NULL),
(306,	1032,	2,	'Available sortings',	NULL),
(307,	1033,	2,	'Available filter',	NULL),
(310,	1036,	2,	'Automatically expand backend menu entries',	'The behavior of the buttons in the upper menu in the backend changes with this option. If this option is set to No, the menu entries must be opened manually by a mouse click. (backend cache needs to be cleared and the backend must be reloaded)'),
(311,	1037,	2,	'Notification position',	'With this option the backend notifications can be displayed at different positions (backend cache needs to be cleared and the backend must be reloaded)'),
(312,	1038,	2,	'Alternative email addresses for errors',	'If this field is empty, the shop owners email address will be used. One recipient address may be given per line.'),
(313,	1035,	2,	'Create Shopware Login Cookie',	'A cookie is stored, where the user can be identified again. This cookie is only used for setting the current customer group and the active Customer Streams'),
(314,	1039,	2,	'Manufacturer page product layout',	''),
(315,	1040,	2,	'Log level',	'Here you can choose the minimum log level for sending an e-mail. The default is \"Warning\". To focus on actual errors, you can increase the log level for example to \"Error\" or higher.'),
(316,	1034,	2,	'Use captcha for newsletter',	'The selected captcha method is used in the newsletter registration in the frontend.'),
(317,	1041,	2,	'Data protection information will be shown',	'Affects the registration, blog & product comments, newsletters and the product notification plugin form, but also your own forms'),
(318,	1042,	2,	'Delete accountless customers without orders after x months',	'The cronjob \"Guest customer cleanup\" must be active'),
(319,	1043,	2,	'Delete canceled orders after x months',	'The cronjob \"Cancelled baskets cleanup\" must be active'),
(320,	1044,	2,	'Anonymize customer IPs',	'Removes the last two blocks of IPv4 and three blocks of IPv6 addresses in statistics and orders to comply with privacy laws.'),
(321,	1045,	2,	'Double opt in for registrations',	NULL),
(322,	1046,	2,	'Days without confirmation until deletion',	'For Double-Opt-In: Time after which unconfirmed actions are deleted.'),
(323,	1047,	2,	'Double opt in for quick orderer',	NULL),
(324,	1048,	2,	'Cookie notice mode',	NULL),
(325,	1031,	2,	'Use captcha in registration',	'If active, a captcha will be shown in the registration. The recommended method for registrations is honeypot.'),
(326,	1050,	2,	'Proportional calculation of tax positions',	NULL),
(327,	1051,	2,	'Output href-lang in the meta tags',	'If active, all languages of a page are displayed in the the meta tags'),
(328,	1052,	2,	'Use language and country in href-lang',	'If this option is activated, the country is output in addition to the language, e.g. \"en-GB\" instead of \"en\"'),
(329,	1024,	2,	'Display only shop specific ratings',	'Defines whether only the ratings from the corresponding shop should be displayed for a product.'),
(330,	1060,	2,	'Display shop specific blog comments only',	'If active, only blog comments of the corresponding shop are displayed. <br>If inactive, all blog comments are always displayed regardless of the language or subshop.'),
(331,	1061,	2,	'Minimal keyword length for indexation',	'This setting defines the minimal keyword length for indexation. <b>Default: 3 characters</b>'),
(332,	1062,	2,	'Display essential characteristics throughout the checkout process',	'If activated, the essential characteristics are displayed throughout the checkout. Otherwise, they will only appear on the order confirmation page.'),
(333,	1063,	2,	'Show shipping costs calculation in mini/offcanvas shopping cart',	'If enabled, a shipping cost calculation will be displayed in the mini/offcanvas cart page. This is only available for customers who aren\'t logged in.'),
(334,	1066,	2,	'Display voucher field on checkout page',	NULL),
(335,	1067,	2,	'Display voucher field in shopping cart',	NULL),
(336,	1068,	2,	'Net orders consistently round to 2 digits',	NULL),
(337,	1069,	2,	'Clear basket after logout',	'If active, the shopping cart will be cleared after a logout. <br>If inactive, the shopping cart will not be cleared after a logout and will be retained for a later login.'),
(338,	1070,	2,	'Restore saved shopping carts on login',	'It will only be restored if the shopping cart is empty at login.'),
(339,	1071,	2,	'Share browser session between language shops',	'When active, the browser session is shared between language shops. Thus the customer does not lose the shopping basket when switching'),
(340,	1075,	1,	'Geburtstag als Datumsfeld anzeigen',	'Wenn aktiv, wird das Geburtsdatum als einzelnes Datumsfeld dargestellt, statt drei einzelnen Feldern'),
(341,	1075,	2,	'Display birthday as a date field',	'If active, the birthdate will be displayed as a date field, rather than three single fields.'),
(342,	1076,	2,	'Order number for surcharge dispatch rule',	NULL),
(343,	1077,	2,	'Show \"Accept all\" button in cookie hint',	'Only counts for the mode \"Technically necessary cookies\". Before you change this setting, you should first have your legal advisor check the use of this setting.'),
(344,	1078,	2,	'Argon2 memory',	'Higher memory usage increases the security against attackers.'),
(345,	1079,	2,	'Argon2 time',	'Increasing the required time for hash calculation.'),
(346,	1080,	2,	'Argon2 threads',	'Use more threads for parallelism and therefore increased security against attackers, based on your setup.'),
(347,	1081,	2,	'Just output href-lang with SEO URLs',	'If active, just SEO URLs are displayed in the meta tags \"href-lang\"'),
(348,	1082,	2,	'Show all countries in the delivery country dropdown',	''),
(349,	1083,	2,	'Redirect urls without trailing slash',	'If active, URLs that normally end in a slash (\"/\") and are called without it are forwarded to the correct page with slash via http-code 301. The Canonical always points to the correct page with slash.'),
(350,	1084,	2,	'Hide categories in off-canvas menu aswell',	'If a category is hidden from the top navigation, it won\'t be displayed in the off-canvas menu aswell, if this option is active.'),
(351,	1085,	2,	'Invalidate Cookies after X days',	'Invalidates Cookies after set time'),
(352,	1086,	2,	'Use captcha for the password reset form',	'If this option is active, a captcha is used to protect the Password reset form.'),
(353,	1087,	2,	'Use captcha for the e-mail notification for products',	'If this option is active, a captcha is used to protect e-mail notification form to notify about new products in stock.'),
(354,	1088,	2,	'Salutation required',	'Whether or not a salutation is required upon registration.'),
(355,	1089,	1,	'Sichere Verbindung erzwingen',	'Erfordert bei Aktivierung eine SSL verschlüsselte Verbindung für die API Anfragen.'),
(356,	1089,	2,	'Enforce secure connection',	'Enforces an SSL encrypted connection for the API request.');

DROP TABLE IF EXISTS `s_core_config_elements`;
CREATE TABLE `s_core_config_elements` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `form_id` int unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `value` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `label` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `type` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `required` int unsigned NOT NULL,
  `position` int NOT NULL,
  `scope` int unsigned NOT NULL,
  `options` blob,
  PRIMARY KEY (`id`),
  UNIQUE KEY `form_id_2` (`form_id`,`name`),
  KEY `form_id` (`form_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_config_elements` (`id`, `form_id`, `name`, `value`, `label`, `description`, `type`, `required`, `position`, `scope`, `options`) VALUES
(186,	86,	'vouchertax',	's:2:\"19\";',	'MwSt. Gutscheine',	NULL,	'text',	0,	0,	0,	NULL),
(188,	86,	'discounttax',	's:2:\"19\";',	'MwSt. Rabatte',	NULL,	'text',	0,	0,	0,	NULL),
(189,	90,	'voteunlock',	'b:1;',	'Artikel-Bewertungen müssen freigeschaltet werden',	NULL,	'boolean',	0,	0,	0,	NULL),
(190,	84,	'backendautoordernumber',	'b:1;',	'Automatischer Vorschlag der Artikelnummer',	NULL,	'boolean',	0,	0,	0,	NULL),
(191,	84,	'backendautoordernumberprefix',	's:2:\"SW\";',	'Präfix für automatisch generierte Artikelnummer',	NULL,	'text',	0,	0,	0,	NULL),
(192,	90,	'votedisable',	'b:0;',	'Artikel-Bewertungen deaktivieren',	NULL,	'boolean',	0,	0,	1,	NULL),
(193,	90,	'votesendcalling',	'b:1;',	'Automatische Erinnerung zur Artikelbewertung senden',	'Nach Kauf dem Benutzer an die Artikelbewertung via E-Mail erinnern',	'boolean',	0,	0,	0,	NULL),
(194,	90,	'votecallingtime',	's:1:\"1\";',	'Tage bis die Erinnerungs-E-Mail verschickt wird',	'Tage bis der Kunde via E-Mail an die Artikel-Bewertung erinnert wird',	'text',	0,	0,	0,	NULL),
(195,	86,	'taxautomode',	'b:1;',	'Steuer für Rabatte dynamisch feststellen',	NULL,	'boolean',	0,	0,	1,	NULL),
(224,	102,	'lastarticles_show',	'b:1;',	'Artikelverlauf anzeigen',	NULL,	'checkbox',	0,	0,	1,	'a:0:{}'),
(225,	102,	'lastarticles_controller',	's:61:\"index, listing, detail, custom, newsletter, sitemap, campaign\";',	'Controller-Auswahl',	NULL,	'text',	0,	0,	1,	'a:0:{}'),
(231,	102,	'lastarticlestoshow',	's:1:\"5\";',	'Anzahl Artikel in Verlauf (zuletzt angeschaut)',	NULL,	'text',	0,	0,	0,	NULL),
(235,	124,	'mailer_mailer',	's:4:\"mail\";',	'Methode zum Senden der Mail',	NULL,	'combo',	0,	0,	1,	'a:4:{s:12:\"displayValue\";s:4:\"name\";s:10:\"valueField\";s:4:\"name\";s:5:\"store\";s:135:\"new Ext.create(\"Ext.data.Store\",{fields: [{name: \"name\", type: \"string\"}], data:[{\"name\": \"mail\"},{\"name\": \"smtp\"},{\"name\": \"file\"}]});\";s:9:\"queryMode\";s:5:\"local\";}'),
(236,	124,	'mailer_hostname',	's:0:\"\";',	'Hostname für die Message-ID',	'Wird im Header mittels HELO verwendet. Andernfalls wird der Wert aus SERVER_NAME oder \"localhost.localdomain\" genutzt.',	'text',	0,	0,	1,	NULL),
(237,	124,	'mailer_host',	's:9:\"localhost\";',	'Mail Host',	'Es kann auch ein anderer Port über dieses Muster genutzt werden: [hostname:port] - Bsp.: smtp1.example.com:25',	'text',	0,	0,	1,	NULL),
(238,	124,	'mailer_port',	's:2:\"25\";',	'Standard Port',	'Setzt den Standard SMTP Server-Port',	'text',	0,	0,	1,	NULL),
(239,	124,	'mailer_smtpsecure',	's:0:\"\";',	'Verbindungs Präfix',	NULL,	'combo',	0,	0,	1,	'a:4:{s:12:\"displayValue\";s:4:\"name\";s:10:\"valueField\";s:4:\"name\";s:5:\"store\";s:116:\"new Ext.create(\"Ext.data.Store\",{fields: [{name: \"name\", type: \"string\"}], data:[{\"name\": \"ssl\"},{\"name\": \"tls\"}]});\";s:9:\"queryMode\";s:5:\"local\";}'),
(240,	124,	'mailer_username',	's:0:\"\";',	'SMTP Benutzername',	NULL,	'text',	0,	0,	1,	NULL),
(241,	124,	'mailer_password',	's:0:\"\";',	'SMTP Passwort',	NULL,	'text',	0,	0,	1,	'a:2:{s:9:\"inputType\";s:8:\"password\";s:10:\"autoCreate\";a:1:{s:12:\"autocomplete\";s:3:\"off\";}}'),
(242,	124,	'mailer_auth',	's:0:\"\";',	'Verbindungs-Authentifizierung',	NULL,	'combo',	0,	0,	1,	'a:4:{s:12:\"displayValue\";s:4:\"name\";s:10:\"valueField\";s:4:\"name\";s:5:\"store\";s:140:\"new Ext.create(\"Ext.data.Store\",{fields: [{name: \"name\", type: \"string\"}], data:[{\"name\": \"login\"},{\"name\": \"plain\"},{\"name\": \"crammd5\"}]});\";s:9:\"queryMode\";s:5:\"local\";}'),
(252,	0,	'cachesearch',	'i:86400;',	'Cache Suche',	NULL,	'interval',	0,	0,	0,	NULL),
(254,	128,	'setoffline',	'b:0;',	'Shop wegen Wartung sperren',	NULL,	'boolean',	0,	0,	1,	NULL),
(255,	128,	'offlineip',	's:1:\"0\";',	'Von der Sperrung ausgeschlossene IP',	NULL,	'text',	0,	0,	1,	NULL),
(269,	133,	'show',	'i:1;',	'Menü zeigen',	NULL,	'checkbox',	0,	0,	1,	'a:0:{}'),
(270,	133,	'levels',	'i:2;',	'Anzahl Ebenen',	NULL,	'text',	0,	0,	1,	'a:0:{}'),
(271,	133,	'caching',	'i:1;',	'Caching aktivieren',	NULL,	'checkbox',	0,	0,	0,	'a:0:{}'),
(272,	133,	'cachetime',	'i:86400;',	'Cachezeit',	NULL,	'interval',	0,	0,	0,	'a:0:{}'),
(273,	134,	'compareShow',	'i:1;',	'Vergleich zeigen',	NULL,	'checkbox',	0,	0,	1,	'a:0:{}'),
(274,	134,	'maxComparisons',	'i:5;',	'Maximale Anzahl von zu vergleichenden Artikeln',	NULL,	'number',	0,	0,	1,	'a:0:{}'),
(286,	144,	'articlesperpage',	's:2:\"12\";',	'Artikel pro Seite',	NULL,	'text',	0,	0,	0,	NULL),
(289,	145,	'markasnew',	's:2:\"30\";',	'Artikel als neu markieren (Tage)',	NULL,	'text',	0,	0,	0,	NULL),
(290,	145,	'markastopseller',	's:2:\"30\";',	'Artikel als Topseller markieren (Verkäufe)',	NULL,	'text',	0,	0,	0,	NULL),
(291,	145,	'chartrange',	'i:8;',	'Anzahl Topseller für Charts',	NULL,	'number',	0,	0,	0,	NULL),
(292,	144,	'numberarticlestoshow',	's:11:\"12|24|36|48\";',	'Auswahl Artikel pro Seite',	NULL,	'text',	0,	0,	0,	NULL),
(293,	144,	'categorytemplates',	's:0:\"\";',	'Verfügbare Listen Layouts',	NULL,	'textarea',	0,	0,	0,	NULL),
(294,	147,	'maxpurchase',	's:3:\"100\";',	'Max. wählbare Artikelmenge / Artikel über Pulldown-Menü',	NULL,	'text',	0,	0,	0,	NULL),
(295,	147,	'notavailable',	's:21:\"Lieferzeit ca. 5 Tage\";',	'Text für nicht verfügbare Artikel',	NULL,	'text',	0,	0,	1,	NULL),
(296,	146,	'maxcrosssimilar',	's:1:\"8\";',	'Anzahl ähnlicher Artikel Cross-Selling',	NULL,	'text',	0,	0,	0,	NULL),
(297,	146,	'maxcrossalsobought',	's:1:\"8\";',	'Anzahl \"Kunden kauften auch\" Artikel Cross-Selling',	NULL,	'text',	0,	0,	0,	NULL),
(299,	145,	'chartinterval',	's:2:\"10\";',	'Anzahl der Tage, die für die Topseller-Generierung berücksichtigt werden',	NULL,	'text',	0,	0,	0,	NULL),
(300,	146,	'similarlimit',	's:1:\"0\";',	'Anzahl automatisch ermittelter, ähnlicher Artikel (Detailseite)',	'Wenn keine ähnlichen Produkte gefunden wurden, kann Shopware automatisch alternative Vorschläge generieren. Du kannst die automatischen Vorschläge aktivieren, indem du einen Wert größer als 0 einträgst. Das Aktivieren kann sich negativ auf die Performance des Shops auswirken.',	'text',	0,	0,	0,	NULL),
(301,	147,	'basketshippinginfo',	'b:1;',	'Lieferzeit im Warenkorb anzeigen',	NULL,	'boolean',	0,	0,	0,	NULL),
(303,	147,	'inquiryid',	's:2:\"16\";',	'Anfrage-Formular ID',	NULL,	'text',	0,	0,	1,	NULL),
(304,	147,	'inquiryvalue',	's:3:\"150\";',	'Mind. Warenkorbwert ab dem die Möglichkeit der individuellen Anfrage angeboten wird',	NULL,	'text',	0,	0,	0,	NULL),
(305,	147,	'usezoomplus',	'b:1;',	'Zoomviewer statt Lightbox auf Detailseite',	NULL,	'boolean',	0,	0,	0,	NULL),
(310,	147,	'deactivatebasketonnotification',	'b:1;',	'Warenkorb bei E-Mail-Benachrichtigung ausblenden',	'Warenkorb bei aktivierter E-Mail-Benachrichtigung und nicht vorhandenem Lagerbestand ausblenden',	'boolean',	0,	0,	0,	NULL),
(311,	147,	'instockinfo',	'b:0;',	'Lagerbestands-Unterschreitung im Warenkorb anzeigen',	NULL,	'boolean',	0,	0,	0,	NULL),
(312,	144,	'categorydetaillink',	'b:0;',	'Direkt auf Detailspringen, falls nur ein Artikel vorhanden ist',	NULL,	'boolean',	0,	0,	0,	NULL),
(314,	147,	'detailtemplates',	's:9:\":Standard\";',	'Verfügbare Templates Detailseite',	NULL,	'textarea',	0,	0,	0,	NULL),
(317,	157,	'minpassword',	's:1:\"8\";',	'Mindestlänge Passwort (Registrierung)',	NULL,	'text',	0,	317,	0,	NULL),
(318,	157,	'defaultpayment',	's:1:\"5\";',	'Standardzahlungsart (Id) (Registrierung)',	NULL,	'select',	1,	318,	1,	'a:3:{s:5:\"store\";s:12:\"base.Payment\";s:12:\"displayField\";s:11:\"description\";s:10:\"valueField\";s:2:\"id\";}'),
(319,	157,	'newsletterdefaultgroup',	's:1:\"1\";',	'Standard-Empfangsgruppe (ID) für registrierte Kunden (System / Newsletter)',	NULL,	'text',	0,	319,	1,	NULL),
(320,	157,	'shopwaremanagedcustomernumbers',	'b:1;',	'Shopware generiert Kundennummern',	NULL,	'boolean',	0,	320,	0,	NULL),
(321,	277,	'ignoreagb',	'b:0;',	'AGB - Checkbox auf Kassenseite deaktivieren',	NULL,	'boolean',	0,	10,	0,	NULL),
(323,	277,	'actdprcheck',	'b:0;',	'Datenschutzhinweise müssen über Checkbox akzeptiert werden',	'Bitte aktiviere vorher die Checkbox \"Datenschutzhinweis anzeigen\"',	'boolean',	0,	1,	0,	NULL),
(324,	157,	'paymentdefault',	's:1:\"5\";',	'Fallback-Zahlungsart (ID)',	NULL,	'text',	0,	324,	0,	NULL),
(325,	157,	'doubleemailvalidation',	'b:0;',	'E-Mail Addresse muss zweimal eingegeben werden.',	'E-Mail Addresse muss zweimal eingegeben werden, um Tippfehler zu vermeiden.',	'boolean',	0,	325,	0,	NULL),
(326,	277,	'newsletterextendedfields',	'b:1;',	'Erweiterte Felder in Newsletter-Registrierung abfragen',	NULL,	'boolean',	0,	10,	1,	NULL),
(327,	157,	'noaccountdisable',	'i:2;',	'\"Kein Kundenkonto\" deaktivieren',	NULL,	'select',	0,	327,	0,	'a:5:{s:8:\"editable\";b:0;s:14:\"forceSelection\";b:1;s:22:\"translateUsingSnippets\";b:1;s:9:\"namespace\";s:24:\"backend/application/main\";s:5:\"store\";a:3:{i:0;a:2:{i:0;i:0;i:1;a:3:{s:7:\"snippet\";s:35:\"deactivate_no_customer_account_true\";s:5:\"en_GB\";s:3:\"Yes\";s:5:\"de_DE\";s:2:\"Ja\";}}i:1;a:2:{i:0;i:1;i:1;a:3:{s:7:\"snippet\";s:42:\"deactivate_no_customer_account_preselected\";s:5:\"en_GB\";s:25:\"No: Option is preselected\";s:5:\"de_DE\";s:31:\"Nein: Option ist vorausgewählt\";}}i:2;a:2:{i:0;i:2;i:1;a:3:{s:7:\"snippet\";s:41:\"deactivate_no_customer_account_unselected\";s:5:\"en_GB\";s:29:\"No: Option is not preselected\";s:5:\"de_DE\";s:38:\"Nein: Option ist nicht vorausgewählt.\";}}}}'),
(585,	173,	'blockIp',	'N;',	'IP von Statistiken ausschließen',	NULL,	'text',	0,	0,	1,	'a:0:{}'),
(608,	189,	'sql_protection',	'b:1;',	'SQL-Injection-Schutz aktivieren',	NULL,	'checkbox',	0,	0,	0,	'a:0:{}'),
(610,	189,	'xss_protection',	'b:1;',	'XSS-Schutz aktivieren',	NULL,	'checkbox',	0,	0,	0,	'a:0:{}'),
(612,	189,	'rfi_protection',	'b:1;',	'RemoteFileInclusion-Schutz aktivieren',	NULL,	'checkbox',	0,	0,	0,	'a:0:{}'),
(614,	0,	'vouchername',	's:9:\"Gutschein\";',	'Gutscheine Bezeichnung',	NULL,	'text',	0,	0,	1,	NULL),
(615,	190,	'minsearchlenght',	's:1:\"3\";',	'Minimale Suchwortlänge',	NULL,	'text',	0,	0,	0,	NULL),
(620,	0,	'discountname',	's:15:\"Warenkorbrabatt\";',	'Rabatte Bezeichnung ',	NULL,	'text',	0,	0,	1,	NULL),
(623,	0,	'surchargename',	's:20:\"Mindermengenzuschlag\";',	'Mindermengen Bezeichnung',	NULL,	'text',	0,	0,	1,	NULL),
(624,	192,	'no_order_mail',	'b:0;',	'Bestellbestätigung an Shopbetreiber deaktivieren',	NULL,	'boolean',	0,	0,	0,	NULL),
(625,	190,	'badwords',	's:375:\"ab,die,der,und,in,zu,den,das,nicht,von,sie,ist,des,sich,mit,dem,dass,er,es,ein,ich,auf,so,eine,auch,als,an,nach,wie,im,für,einen,um,werden,mehr,zum,aus,ihrem,style,oder,neue,spieler,können,wird,sind,ihre,einem,of,du,sind,einer,über,alle,neuen,bei,durch,kann,hat,nur,noch,zur,gegen,bis,aber,haben,vor,seine,ihren,jetzt,ihr,dir,etc,bzw,nach,deine,the,warum,machen,0,sowie,am\";',	'Blacklist für Keywords',	NULL,	'text',	1,	0,	0,	NULL),
(626,	0,	'paymentsurchargeadd',	's:25:\"Zuschlag für Zahlungsart\";',	'Bezeichnung proz. Zuschlag für Zahlungsart',	NULL,	'text',	0,	0,	1,	NULL),
(627,	0,	'paymentsurchargedev',	's:25:\"Abschlag für Zahlungsart\";',	'Bezeichnung proz. Abschlag für Zahlungsart',	NULL,	'text',	0,	0,	1,	NULL),
(628,	191,	'discountnumber',	's:11:\"sw-discount\";',	'Rabatte Bestellnummer',	NULL,	'text',	0,	0,	1,	NULL),
(629,	191,	'surchargenumber',	's:12:\"sw-surcharge\";',	'Mindermengen Bestellnummer',	NULL,	'text',	0,	0,	1,	NULL),
(630,	191,	'paymentsurchargenumber',	's:10:\"sw-payment\";',	'Zuschlag für Zahlungsart (Bestellnummer)',	NULL,	'text',	0,	0,	1,	NULL),
(631,	190,	'maxlivesearchresults',	's:1:\"6\";',	'Anzahl der Ergebnisse in der Livesuche',	NULL,	'text',	0,	0,	0,	NULL),
(633,	192,	'send_confirm_mail',	'b:1;',	'Registrierungsbestätigung in CC an Shopbetreiber schicken',	NULL,	'boolean',	0,	0,	0,	NULL),
(634,	277,	'optinnewsletter',	'b:0;',	'Double-Opt-In für Newsletter-Anmeldungen',	NULL,	'boolean',	0,	10,	0,	NULL),
(635,	277,	'optinvote',	'b:0;',	'Double-Opt-In für Blog- & Artikel-Bewertungen',	NULL,	'boolean',	0,	10,	0,	NULL),
(636,	191,	'shippingdiscountnumber',	's:16:\"SHIPPINGDISCOUNT\";',	'Abschlag-Versandregel (Bestellnummer)',	NULL,	'text',	0,	0,	1,	NULL),
(637,	0,	'shippingdiscountname',	's:15:\"Warenkorbrabatt\";',	'Abschlag-Versandregel (Bezeichnung)',	NULL,	'text',	0,	0,	1,	NULL),
(641,	192,	'orderstatemailack',	's:0:\"\";',	'Bestellstatus - Änderungen CC-Adresse',	NULL,	'text',	0,	0,	0,	NULL),
(642,	247,	'premiumshippiungasketselect',	's:93:\"MAX(a.topseller) as has_topseller, MAX(at.attr3) as has_comment, MAX(b.esdarticle) as has_esd\";',	'Erweitere SQL-Abfrage',	NULL,	'text',	1,	0,	0,	NULL),
(643,	247,	'premiumshippingnoorder',	'b:0;',	'Bestellung bei keiner verfügbaren Versandart blocken',	NULL,	'boolean',	1,	0,	0,	NULL),
(646,	249,	'routertolower',	'b:1;',	'Nur Kleinbuchstaben in den Urls nutzen',	NULL,	'boolean',	0,	0,	0,	NULL),
(649,	249,	'seometadescription',	'b:1;',	'Meta-Description von Artikel/Kategorien aufbereiten',	NULL,	'boolean',	0,	0,	1,	NULL),
(650,	249,	'routerremovecategory',	'b:0;',	'KategorieID aus Url entfernen',	NULL,	'boolean',	0,	0,	1,	NULL),
(651,	249,	'seoqueryblacklist',	's:50:\"sPage,sPerPage,sSupplier,sFilterProperties,p,n,s,f\";',	'SEO-Noindex Querys',	NULL,	'text',	0,	0,	0,	NULL),
(652,	249,	'seoviewportblacklist',	's:112:\"login,ticket,tellafriend,note,support,basket,admin,registerFC,newsletter,search,search,account,checkout,register\";',	'SEO-Noindex Viewports',	NULL,	'text',	0,	0,	0,	NULL),
(654,	249,	'seoremovecomments',	'b:1;',	'Html-Kommentare entfernen',	NULL,	'boolean',	0,	0,	0,	NULL),
(655,	249,	'seoqueryalias',	's:244:\"sSearch=q,\nsPage=p,\nsPerPage=n,\nsSupplier=s,\nsFilterProperties=f,\nsCategory=c,\nsCoreId=u,\nsTarget=t,\nsValidation=v,\nsTemplate=l,\npriceMin=min,\npriceMax=max,\nshippingFree=free,\nimmediateDelivery=delivery,\nsSort=o,\ncategoryFilter=cf,\nvariants=var\";',	'Query-Aliase',	NULL,	'textarea',	0,	0,	0,	NULL),
(656,	249,	'seobacklinkwhitelist',	's:54:\"www.shopware.de,\r\nwww.shopware.ag,\r\nwww.shopware-ag.de\";',	'SEO-Follow Backlinks',	NULL,	'textarea',	0,	0,	1,	NULL),
(658,	249,	'routerlastupdate',	NULL,	'Datum des letzten Updates',	NULL,	'datetime',	0,	0,	1,	NULL),
(659,	249,	'routercache',	's:5:\"86400\";',	'SEO-Urls Cachezeit Tabelle',	NULL,	'text',	0,	0,	0,	NULL),
(665,	157,	'vatcheckrequired',	'b:0;',	'USt-IdNr. für Firmenkunden als Pflichtfeld markieren',	NULL,	'boolean',	0,	665,	1,	NULL),
(667,	249,	'routerarticletemplate',	's:70:\"{sCategoryPath articleID=$sArticle.id}/{$sArticle.id}/{$sArticle.name}\";',	'SEO-Urls Artikel-Template',	NULL,	'text',	0,	0,	1,	NULL),
(668,	249,	'routercategorytemplate',	's:41:\"{sCategoryPath categoryID=$sCategory.id}/\";',	'SEO-Urls Kategorie-Template',	NULL,	'text',	0,	0,	1,	NULL),
(670,	249,	'seostaticurls',	NULL,	'sonstige SEO-Urls',	NULL,	'textarea',	0,	0,	1,	NULL),
(673,	119,	'shopName',	's:13:\"Shopware Demo\";',	'Name des Shops',	NULL,	'text',	1,	0,	1,	NULL),
(674,	119,	'mail',	's:16:\"info@example.com\";',	'Shopbetreiber E-Mail',	NULL,	'text',	1,	0,	1,	NULL),
(675,	119,	'address',	's:0:\"\";',	'Adresse',	NULL,	'textarea',	0,	0,	1,	NULL),
(677,	119,	'bankAccount',	's:0:\"\";',	'Bankverbindung',	NULL,	'textarea',	0,	0,	1,	NULL),
(843,	274,	'captchaColor',	's:8:\"51,51,51\";',	'Schriftfarbe Captcha (R,G,B)',	NULL,	'text',	0,	10,	1,	NULL),
(844,	173,	'botBlackList',	's:2757:\"antibot;appie;architext;bjaaland;digout4u;echo;fast-webcrawler;ferret;googlebot;gulliver;harvest;htdig;ia_archiver;jeeves;jennybot;linkwalker;lycos;mercator;moget;muscatferret;myweb;netcraft;nomad;petersnews;scooter;slurp;unlost_web_crawler;voila;voyager;webbase;weblayers;wget;wisenutbot;acme.spider;ahoythehomepagefinder;alkaline;arachnophilia;aretha;ariadne;arks;aspider;atn.txt;atomz;auresys;backrub;bigbrother;blackwidow;blindekuh;bloodhound;brightnet;bspider;cactvschemistryspider;cassandra;cgireader;checkbot;churl;cmc;collective;combine;conceptbot;coolbot;cosmos;cruiser;cusco;cyberspyder;deweb;dienstspider;digger;diibot;directhit;dnabot;download_express;dragonbot;dwcp;e-collector;ebiness;eit;elfinbot;emacs;emcspider;esther;evliyacelebi;nzexplorer;fdse;felix;fetchrover;fido;finnish;fireball;fouineur;francoroute;freecrawl;funnelweb;gama;gazz;gcreep;getbot;geturl;golem;grapnel;griffon;gromit;hambot;havindex;hometown;htmlgobble;hyperdecontextualizer;iajabot;ibm;iconoclast;ilse;imagelock;incywincy;informant;infoseek;infoseeksidewinder;infospider;inspectorwww;intelliagent;irobot;israelisearch;javabee;jbot;jcrawler;jobo;jobot;joebot;jubii;jumpstation;katipo;kdd;kilroy;ko_yappo_robot;labelgrabber.txt;larbin;linkidator;linkscan;lockon;logo_gif;macworm;magpie;marvin;mattie;mediafox;merzscope;meshexplorer;mindcrawler;momspider;monster;mwdsearch;netcarta;netmechanic;netscoop;newscan-online;nhse;northstar;occam;octopus;openfind;orb_search;packrat;pageboy;parasite;patric;pegasus;perignator;perlcrawler;phantom;piltdownman;pimptrain;pioneer;pitkow;pjspider;pka;plumtreewebaccessor;poppi;portalb;puu;python;raven;rbse;resumerobot;rhcs;roadrunner;robbie;robi;robofox;robozilla;roverbot;rules;safetynetrobot;search_au;searchprocess;senrigan;sgscout;shaggy;shaihulud;sift;simbot;site-valet;sitegrabber;sitetech;slcrawler;smartspider;snooper;solbot;spanner;speedy;spider_monkey;spiderbot;spiderline;spiderman;spiderview;spry;ssearcher;suke;suntek;sven;tach_bw;tarantula;tarspider;techbot;templeton;teoma_agent1;titin;titan;tkwww;tlspider;ucsd;udmsearch;urlck;valkyrie;victoria;visionsearch;vwbot;w3index;w3m2;wallpaper;wanderer;wapspider;webbandit;webcatcher;webcopy;webfetcher;webfoot;weblinker;webmirror;webmoose;webquest;webreader;webreaper;websnarf;webspider;webvac;webwalk;webwalker;webwatch;whatuseek;whowhere;wired-digital;wmir;wolp;wombat;worm;wwwc;wz101;xget;awbot;bobby;boris;bumblebee;cscrawler;daviesbot;ezresult;gigabot;gnodspider;internetseer;justview;linkbot;linkchecker;nederland.zoek;perman;pompos;pooodle;redalert;shoutcast;slysearch;ultraseek;webcompass;yandex;robot;yahoo;bot;psbot;crawl;RSS;larbin;ichiro;Slurp;msnbot;bot;Googlebot;ShopWiki;Bot;WebAlta;;abachobot;architext;ask jeeves;frooglebot;googlebot;lycos;spider;HTTPClient\";',	'Bot-Liste',	NULL,	'textarea',	1,	20,	0,	NULL),
(847,	78,	'baseFile',	's:12:\"shopware.php\";',	'Base-File',	NULL,	'text',	1,	0,	0,	NULL),
(848,	253,	'esdKey',	's:33:\"552211cce724117c3178e3d22bec532ec\";',	'ESD-Key',	NULL,	'text',	1,	0,	0,	NULL),
(849,	147,	'blogdetailtemplates',	's:10:\":Standard;\";',	'Verfügbare Templates Blog-Detailseite',	NULL,	'textarea',	0,	0,	0,	NULL),
(851,	190,	'fuzzysearchexactmatchfactor',	'i:100;',	'Faktor für genaue Treffer',	NULL,	'number',	1,	0,	1,	NULL),
(852,	190,	'fuzzysearchlastupdate',	's:19:\"2010-01-01 00:00:00\";',	'Datum des letzten Updates',	NULL,	'datetime',	0,	0,	0,	NULL),
(853,	190,	'fuzzysearchmatchfactor',	'i:5;',	'Faktor für unscharfe Treffer',	NULL,	'number',	1,	0,	1,	NULL),
(854,	190,	'fuzzysearchmindistancentop',	'i:20;',	'Minimale Relevanz zum Topartikel in Prozent',	NULL,	'number',	1,	0,	1,	NULL),
(855,	190,	'fuzzysearchpartnamedistancen',	'i:25;',	'Maximal-Distanz für Teilnamen in Prozent',	NULL,	'number',	1,	0,	1,	NULL),
(856,	190,	'fuzzysearchpatternmatchfactor',	'i:50;',	'Faktor für Teiltreffer',	NULL,	'number',	1,	0,	1,	NULL),
(859,	190,	'fuzzysearchselectperpage',	's:11:\"12|24|36|48\";',	'Auswahl Ergebnisse pro Seite',	NULL,	'text',	1,	0,	1,	NULL),
(860,	253,	'esdMinSerials',	'i:5;',	'ESD-Min-Serials',	NULL,	'text',	1,	0,	0,	NULL),
(867,	255,	'alsoBoughtShow',	'b:1;',	'Anzeigen der Kunden-kauften-auch-Empfehlung',	NULL,	'checkbox',	1,	1,	1,	NULL),
(868,	255,	'alsoBoughtPerPage',	'i:4;',	'Anzahl an Artikel pro Seite in der Liste',	NULL,	'number',	1,	2,	1,	NULL),
(869,	255,	'alsoBoughtMaxPages',	'i:10;',	'Maximale Anzahl von Seiten in der Liste',	NULL,	'number',	1,	3,	1,	NULL),
(870,	255,	'similarViewedShow',	'b:1;',	'Anzeigen der Kunden-schauten-sich-auch-an-Empfehlung',	NULL,	'checkbox',	1,	5,	1,	NULL),
(871,	255,	'similarViewedPerPage',	'i:4;',	'Anzahl an Artikel pro Seite in der Liste',	NULL,	'number',	1,	6,	1,	NULL),
(872,	255,	'similarViewedMaxPages',	'i:10;',	'Maximale Anzahl von Seiten in der Liste',	NULL,	'number',	1,	7,	1,	NULL),
(873,	256,	'revocationNotice',	'b:1;',	'Zeige Widerrufsbelehrung an',	NULL,	'boolean',	0,	0,	1,	'a:0:{}'),
(874,	256,	'newsletter',	'b:0;',	'Zeige Newsletter-Registrierung an',	NULL,	'boolean',	0,	0,	1,	'a:0:{}'),
(875,	256,	'bankConnection',	'b:0;',	'Zeige Bankverbindungshinweis an',	NULL,	'boolean',	0,	0,	1,	'a:0:{}'),
(876,	256,	'additionalFreeText',	'b:0;',	'Zeige weiteren Hinweis an',	'Snippet: ConfirmTextOrderDefault',	'boolean',	0,	0,	1,	'a:0:{}'),
(877,	256,	'commentArticle',	'b:0;',	'Zeige weitere Optionen an',	'Artikel hinzuf&uuml;gen, Kommentarfunktion',	'boolean',	0,	0,	1,	'a:0:{}'),
(879,	256,	'premiumArticles',	'b:0;',	'Zeige Prämienartikel an',	NULL,	'boolean',	0,	0,	1,	'a:0:{}'),
(880,	256,	'countryNotice',	'b:1;',	'Zeige Länder-Beschreibung an',	NULL,	'boolean',	0,	0,	1,	'a:0:{}'),
(881,	256,	'nettoNotice',	'b:0;',	'Zeige Hinweis für Netto-Bestellungen an',	NULL,	'boolean',	0,	0,	1,	'a:0:{}'),
(885,	256,	'mainFeatures',	's:878:\"{if $sBasketItem.purchaseunit && $sBasketItem.purchaseunit != 0}\n                <span class=\"price--label label--purchase-unit is--bold is--nowrap\">\n                    Inhalt:\n                </span>\n            \n                <span class=\"is--nowrap\">\n                    {$sBasketItem.purchaseunit|floatval} {$sBasketItem.additional_details.sUnit.description}\n                </span>\n            {/if}\n            \n            {if $sBasketItem.purchaseunit && $sBasketItem.additional_details.referenceunit && $sBasketItem.purchaseunit != $sBasketItem.additional_details.referenceunit}\n                <span class=\"is--nowrap\">\n                    ({$sBasketItem.additional_details.referenceprice|currency}\n                    * / {$sBasketItem.additional_details.referenceunit} {$sBasketItem.additional_details.sUnit.description})\n                </span>\n            {/if}\";',	'Template für die wesentliche Merkmale',	NULL,	'textarea',	0,	1,	1,	'a:0:{}'),
(886,	259,	'backendTimeout',	'i:7200;',	'PHP Timeout',	NULL,	'interval',	1,	0,	0,	'a:0:{}'),
(887,	259,	'backendLocales',	'a:2:{i:0;i:1;i:1;i:2;}',	'Auswählbare Sprachen',	NULL,	'select',	1,	0,	0,	'a:2:{s:5:\"store\";s:11:\"base.Locale\";s:11:\"multiSelect\";b:1;}'),
(888,	249,	'routerblogtemplate',	's:71:\"{sCategoryPath categoryID=$blogArticle.categoryId}/{$blogArticle.title}\";',	'SEO-Urls Blog-Template',	NULL,	'text',	0,	0,	1,	NULL),
(889,	256,	'detailModal',	'b:1;',	'Artikeldetails in Modalbox anzeigen',	NULL,	'boolean',	0,	0,	1,	NULL),
(893,	119,	'company',	's:0:\"\";',	'Firma',	NULL,	'textfield',	0,	0,	1,	NULL),
(894,	249,	'routercampaigntemplate',	's:16:\"{$campaign.name}\";',	'SEO-Urls Landingpage-Template',	NULL,	'text',	0,	0,	1,	NULL),
(897,	0,	'paymentSurchargeAbsolute',	's:25:\"Zuschlag für Zahlungsart\";',	'Pauschaler Aufschlag für Zahlungsart (Bezeichnung)',	NULL,	'text',	1,	0,	1,	NULL),
(898,	191,	'paymentSurchargeAbsoluteNumber',	's:19:\"sw-payment-absolute\";',	'Pauschaler Aufschlag für Zahlungsart (Bestellnummer)',	NULL,	'text',	1,	0,	1,	NULL),
(900,	263,	'MailCampaignsPerCall',	'i:1000;',	'Anzahl der Mails, die pro Cronjob-Aufruf versendet werden',	NULL,	'number',	1,	0,	0,	NULL),
(901,	189,	'own_filter',	'N;',	'Eigener Filter',	NULL,	'textarea',	0,	0,	0,	NULL),
(905,	157,	'accountPasswordCheck',	'b:1;',	'Aktuelles Passwort bei Passwort-Änderungen abfragen',	NULL,	'boolean',	1,	905,	0,	NULL),
(907,	249,	'preferBasePath',	'b:1;',	'Shopware-Kernel aus URL entfernen ',	'Entfernt \"shopware.php\" aus URLs. Verhindert, dass Suchmaschinen fälschlicherweise DuplicateContent im Shop erkennen. Wenn kein ModRewrite zur Verfügung steht, muss dieses Häcken entfernt werden.',	'boolean',	1,	0,	0,	NULL),
(909,	264,	'useShortDescriptionInListing',	'b:0;',	'In Listen-Ansichten immer die Artikel-Kurzbeschreibung anzeigen',	'Beeinflusst: Topseller, Kategorielisten, Einkaufswelten',	'checkbox',	0,	0,	0,	NULL),
(910,	265,	'defaultPasswordEncoder',	's:4:\"Auto\";',	'Passwort-Algorithmus',	'Beachte, dass manche Hashfunktionen nur angezeigt werden, wenn die dafür benötigte PHP-Version installiert ist<br>Wenn “Auto” gewählt ist, wird bcrypt verwendet. Sollte bcrypt nicht verfügbar sein, wird sha256 verwendet.',	'combo',	1,	0,	0,	'a:5:{s:8:\"editable\";b:0;s:10:\"valueField\";s:2:\"id\";s:12:\"displayField\";s:2:\"id\";s:13:\"triggerAction\";s:3:\"all\";s:5:\"store\";s:20:\"base.PasswordEncoder\";}'),
(911,	265,	'liveMigration',	'i:1;',	'Live Migration',	'Sollen vorhandene Benutzer-Passwörter mit anderen Passwort-Algorithmen beim nächsten Einloggen erneut gehasht werden? Das geschieht voll automatisch im Hintergrund, so dass die Passwörter sukzessiv auf einen neuen Algorithmus umgestellt werden können.',	'checkbox',	1,	0,	0,	NULL),
(912,	265,	'bcryptCost',	'i:10;',	'Bcrypt-Rechenaufwand',	'Je höher der Rechenaufwand, desto aufwändiger ist es für einen möglichen Angreifer, ein Klartext-Passwort für das verschlüsselte Passwort zu berechnen.',	'number',	1,	0,	0,	'a:2:{s:8:\"minValue\";s:1:\"4\";s:8:\"maxValue\";s:2:\"31\";}'),
(913,	265,	'sha256iterations',	'i:100000;',	'Sha256-Iterationen',	'Je höher der Rechenaufwand, desto aufwändiger ist es für einen möglichen Angreifer, ein Klartext-Passwort für das verschlüsselte Passwort zu berechnen.',	'number',	1,	0,	0,	'a:2:{s:8:\"minValue\";s:1:\"1\";s:8:\"maxValue\";s:7:\"1000000\";}'),
(914,	0,	'topSellerActive',	'i:1;',	'',	'',	'',	1,	0,	0,	NULL),
(915,	0,	'topSellerValidationTime',	'i:100;',	'',	'',	'',	1,	0,	0,	NULL),
(916,	0,	'topSellerRefreshStrategy',	'i:3;',	'',	'',	'',	1,	0,	0,	NULL),
(917,	0,	'topSellerPseudoSales',	'i:1;',	'',	'',	'',	1,	0,	0,	NULL),
(918,	0,	'seoRefreshStrategy',	'i:3;',	'',	'',	'',	1,	0,	0,	NULL),
(919,	0,	'searchRefreshStrategy',	'i:3;',	'',	'',	'',	1,	0,	0,	NULL),
(920,	0,	'showSupplierInCategories',	'i:1;',	'',	'',	'',	1,	0,	0,	NULL),
(922,	0,	'disableShopwareStatistics',	'i:0;',	'',	'',	'',	1,	0,	0,	NULL),
(923,	0,	'disableArticleNavigation',	'i:0;',	'',	'',	'',	1,	0,	0,	NULL),
(924,	0,	'similarRefreshStrategy',	'i:3;',	'',	'',	'',	1,	0,	0,	NULL),
(925,	0,	'similarActive',	'i:1;',	'',	'',	'',	1,	0,	0,	NULL),
(926,	0,	'similarValidationTime',	'i:100;',	'',	'',	'',	1,	0,	0,	NULL),
(927,	144,	'moveBatchModeEnabled',	'b:0;',	'Kategorien im Batch-Modus verschieben',	NULL,	'checkbox',	0,	0,	0,	'a:0:{}'),
(928,	0,	'traceSearch',	'i:1;',	'',	'',	'',	1,	0,	0,	NULL),
(930,	0,	'displayFiltersInListings',	'i:1;',	'',	'',	'boolean',	1,	0,	0,	NULL),
(933,	266,	'admin',	'b:0;',	'Admin-View',	'Cache bei Artikel-Vorschau und Schnellbestellung deaktivieren',	'boolean',	0,	0,	0,	'a:0:{}'),
(934,	266,	'cacheControllers',	's:381:\"frontend/listing 3600\nfrontend/index 3600\nfrontend/detail 3600\nfrontend/campaign 14400\nwidgets/listing 14400\nfrontend/custom 14400\nfrontend/sitemap 14400\nfrontend/blog 14400\nwidgets/index 3600\nwidgets/checkout 3600\nwidgets/compare 3600\nwidgets/emotion 14400\nwidgets/recommendation 14400\nwidgets/lastArticles 3600\nwidgets/campaign 3600\nfrontend/listing/layout 0\nfrontend/forms 14400\";',	'Cache-Controller / Zeiten',	NULL,	'textarea',	0,	0,	0,	'a:0:{}'),
(935,	266,	'noCacheControllers',	's:81:\"widgets/lastArticles detail\nwidgets/checkout checkout,slt\nwidgets/compare compare\";',	'NoCache-Controller / Tags',	NULL,	'textarea',	0,	0,	0,	'a:0:{}'),
(936,	266,	'proxy',	'N;',	'Alternative Proxy-Url',	'Link zum Http-Proxy mit „http://“ am Anfang.',	'text',	0,	0,	0,	'a:0:{}'),
(937,	266,	'proxyPrune',	'b:1;',	'Proxy-Prune aktivieren',	'Das automatische Leeren des Caches aktivieren.',	'boolean',	0,	0,	0,	'a:0:{}'),
(938,	253,	'downloadAvailablePaymentStatus',	'a:1:{i:0;i:12;}',	'Download freigeben bei Zahlstatus',	'Definiere hier den Zahlstatus bei dem ein Download des ESD-Artikels möglich ist.',	'select',	1,	3,	0,	'a:4:{s:5:\"store\";s:18:\"base.PaymentStatus\";s:12:\"displayField\";s:11:\"description\";s:10:\"valueField\";s:2:\"id\";s:11:\"multiSelect\";b:1;}'),
(939,	144,	'forceArticleMainImageInListing',	'b:0;',	'Immer das Artikel-Vorschaubild anzeigen',	'z.B. im Listing oder beim Auswahl- und Bildkonfigurator ohne ausgewählte Variante. Wichtig: Bei Variantenfilterung auf aufgefächerten Variantengruppen wird diese Option nicht beachtet.',	'checkbox',	0,	0,	0,	'a:0:{}'),
(940,	256,	'sendOrderMail',	'b:1;',	'Bestell-Abschluss-E-Mail versenden',	NULL,	'checkbox',	0,	0,	1,	'a:0:{}'),
(942,	157,	'requirePhoneField',	'b:0;',	'Telefon als Pflichtfeld behandeln',	'Beachte, dass du die Sternchenangabe über den Textbaustein RegisterLabelPhone konfigurieren musst',	'checkbox',	0,	942,	1,	'a:0:{}'),
(943,	267,	'sepaCompany',	's:0:\"\";',	'Firmenname',	NULL,	'text',	0,	1,	1,	NULL),
(944,	267,	'sepaHeaderText',	's:0:\"\";',	'Überschrift',	NULL,	'text',	0,	2,	1,	NULL),
(945,	267,	'sepaSellerId',	's:0:\"\";',	'Gläubiger-Identifikationsnummer',	NULL,	'text',	0,	3,	1,	NULL),
(946,	267,	'sepaSendEmail',	'i:1;',	'SEPA Mandat automatisch versenden',	NULL,	'checkbox',	0,	4,	1,	NULL),
(947,	267,	'sepaShowBic',	'i:1;',	'SEPA BIC Feld anzeigen',	NULL,	'checkbox',	0,	5,	1,	NULL),
(948,	267,	'sepaRequireBic',	'i:1;',	'SEPA BIC Feld erforderlich',	NULL,	'checkbox',	0,	6,	1,	NULL),
(949,	267,	'sepaShowBankName',	'i:1;',	'SEPA Kreditinstitut Feld anzeigen',	NULL,	'checkbox',	0,	7,	1,	NULL),
(950,	267,	'sepaRequireBankName',	'i:1;',	'SEPA Kreditinstitut Feld erforderlich',	NULL,	'checkbox',	0,	8,	1,	NULL),
(952,	249,	'seoSupplier',	'b:1;',	'Hersteller SEO-Informationen anwenden',	NULL,	'checkbox',	0,	0,	1,	'a:0:{}'),
(953,	249,	'seoSupplierRouteTemplate',	's:46:\"{createSupplierPath supplierID=$sSupplier.id}/\";',	'SEO-Urls Hersteller-Template',	NULL,	'text',	0,	0,	1,	'a:0:{}'),
(954,	268,	'logMail',	'i:0;',	'Fehler an Shopbetreiber senden',	NULL,	'checkbox',	0,	0,	0,	'a:0:{}'),
(955,	173,	'maximumReferrerAge',	's:2:\"90\";',	'Maximales Alter für Referrer Statistikdaten',	'Alte Referrer Daten werden über den Aufräumen Cronjob gelöscht, falls aktiv',	'text',	0,	0,	1,	'a:0:{}'),
(956,	173,	'maximumImpressionAge',	's:2:\"90\";',	'Maximales Alter für Artikel-Impressions',	'Alte Impression Daten werden über den Aufräumen Cronjob gelöscht, falls aktiv',	'text',	0,	0,	1,	'a:0:{}'),
(957,	255,	'showTellAFriend',	'b:0;',	'Artikel weiterempfehlen anzeigen',	NULL,	'boolean',	0,	7,	1,	NULL),
(958,	102,	'lastarticles_time',	'i:15;',	'Speicherfrist in Tagen',	NULL,	'number',	0,	0,	0,	'a:0:{}'),
(959,	253,	'esdDownloadStrategy',	'i:1;',	'Downloadoption für ESD Dateien',	'<b>Achtung</b>: Diese Einstellung könnte die Funktionalität der ESD Downloads beeinträchtigen. Sobald die Dateien nicht mehr lokal sind, wird aus Sicherheitsgründen nur noch \'PHP\' verwendet. <br><br>Downloadstrategie für ESD Dateien.<br><b>Link</b>: Unter Umständen unsicher, da der Link von außen eingesehen werden kann.<br><b>PHP</b>: Der Link kann nicht eingesehen werden. PHP liefert die Datei aus. Dies kann zu Problemen bei größeren Dateien führen.<br><b>X-Sendfile</b>: Unterstützt größere Dateien und ist sicher. Benötigt das X-Sendfile Apache Module. <br><b>X-Accel</b>: Äquivalent zum X-Sendfile. Benötigt das Nginx Modul X-Accel.',	'select',	1,	4,	0,	'a:1:{s:5:\"store\";a:4:{i:0;a:2:{i:0;i:0;i:1;s:4:\"Link\";}i:1;a:2:{i:0;i:1;i:1;s:3:\"PHP\";}i:2;a:2:{i:0;i:2;i:1;s:20:\"X-Sendfile (Apache2)\";}i:3;a:2:{i:0;i:3;i:1;s:15:\"X-Accel (Nginx)\";}}}'),
(966,	0,	'trackingUniqueId',	's:0:\"\";',	'Unique identifier',	NULL,	'text',	0,	0,	0,	'a:1:{s:6:\"hidden\";b:1;}'),
(968,	157,	'showphonenumberfield',	'b:0;',	'Telefon anzeigen',	NULL,	'checkbox',	0,	968,	1,	'a:0:{}'),
(969,	157,	'doublepasswordvalidation',	'b:0;',	'Passwort muss zweimal eingegeben werden',	'Passwort muss zweimal angegeben werden, um Tippfehler zu vermeiden.',	'checkbox',	0,	969,	1,	'a:0:{}'),
(970,	157,	'showbirthdayfield',	'b:0;',	'Geburtstag anzeigen',	NULL,	'checkbox',	0,	970,	1,	'a:0:{}'),
(971,	157,	'requirebirthdayfield',	'b:0;',	'Geburtstag als Pflichtfeld behandeln',	NULL,	'checkbox',	0,	971,	1,	'a:0:{}'),
(972,	157,	'showAdditionAddressLine1',	'b:0;',	'Adresszusatzzeile 1 anzeigen',	'',	'checkbox',	0,	972,	1,	'a:0:{}'),
(973,	157,	'showAdditionAddressLine2',	'b:0;',	'Adresszusatzzeile 2 anzeigen',	'',	'checkbox',	0,	973,	1,	'a:0:{}'),
(974,	157,	'requireAdditionAddressLine1',	'b:0;',	'Adresszusatzzeile 1 als Pflichtfeld behandeln',	'',	'checkbox',	0,	974,	1,	'a:0:{}'),
(975,	157,	'requireAdditionAddressLine2',	'b:0;',	'Adresszusatzzeile 2 als Pflichtfeld behandeln',	'',	'checkbox',	0,	975,	1,	'a:0:{}'),
(976,	270,	'addToQueuePerRequest',	'i:2048;',	'Number of products per queue request',	'The number of products, you want to add to queue per request. The higher the value, the longer a request will take. Too low values will result in overhead',	'number',	1,	0,	0,	'a:1:{s:10:\"attributes\";a:1:{s:8:\"minValue\";i:100;}}'),
(977,	270,	'batchItemsPerRequest',	'i:2048;',	'Products per batch request',	'The number of products, you want to be processed per request. The higher the value, the longer a request will take. Too low values will result in overhead',	'number',	1,	0,	0,	'a:1:{s:10:\"attributes\";a:1:{s:8:\"minValue\";i:50;}}'),
(978,	270,	'enableBackup',	'b:1;',	'Enable restore feature',	'Enable the restore feature.',	'checkbox',	0,	0,	0,	'a:0:{}'),
(979,	270,	'clearCache',	'b:0;',	'Invalidate products in batch mode',	'Will clear the cache for any product, which was changed in batch mode. When changing many products, this will be quite slow. Its recommended to clear the cache manually afterwards.',	'checkbox',	0,	0,	0,	'a:0:{}'),
(980,	147,	'basketShowCalculation',	'i:1;',	'Versandkostenberechnung im Warenkorb anzeigen',	'Bei aktivierter Einstellung wird ein Versandkostenrechner auf der Warenkorbseite dargestellt. Diese Funktion ist nur für nicht angemeldete Kunden verfügbar.',	'select',	0,	5,	1,	'a:5:{s:8:\"editable\";b:0;s:14:\"forceSelection\";b:1;s:22:\"translateUsingSnippets\";b:1;s:9:\"namespace\";s:24:\"backend/application/main\";s:5:\"store\";a:3:{i:0;a:2:{i:0;i:0;i:1;a:3:{s:7:\"snippet\";s:30:\"shipping_calculations_not_show\";s:5:\"en_GB\";s:2:\"No\";s:5:\"de_DE\";s:4:\"Nein\";}}i:1;a:2:{i:0;i:1;i:1;a:3:{s:7:\"snippet\";s:33:\"shipping_calculations_show_folded\";s:5:\"en_GB\";s:9:\"Collapsed\";s:5:\"de_DE\";s:11:\"Eingeklappt\";}}i:2;a:2:{i:0;i:2;i:1;a:3:{s:7:\"snippet\";s:35:\"shipping_calculations_show_expanded\";s:5:\"en_GB\";s:8:\"Expanded\";s:5:\"de_DE\";s:11:\"Ausgeklappt\";}}}}'),
(981,	249,	'PageNotFoundDestination',	'i:-2;',	'\"Seite nicht gefunden\" Ziel',	'Wenn der Besucher eine nicht existierende Seite aufruft, wird ihm diese angezeigt.',	'select',	1,	0,	1,	'a:5:{s:5:\"store\";s:35:\"base.PageNotFoundDestinationOptions\";s:12:\"displayField\";s:4:\"name\";s:10:\"valueField\";s:2:\"id\";s:10:\"allowBlank\";b:0;s:8:\"pageSize\";i:25;}'),
(982,	249,	'PageNotFoundCode',	'i:404;',	'\"Seite nicht gefunden\" Fehlercode',	'Übertragener HTTP Statuscode bei \"Seite nicht gefunden\" meldungen',	'number',	1,	0,	1,	NULL),
(983,	157,	'showCompanySelectField',	'i:0;',	'\"Ich bin\" Auswahlfeld anzeigen',	'Wenn das Auswahlfeld nicht angezeigt wird, wird die Registrierung immer als Privatkunde durchgeführt. Das Auswahlfeld wird nur bei der Registrierung ausgeblendent, danach ist es beim Ändern der Benutzerdaten trotzdem verfügbar.',	'select',	1,	983,	1,	'a:5:{s:8:\"editable\";b:0;s:14:\"forceSelection\";b:1;s:22:\"translateUsingSnippets\";b:1;s:9:\"namespace\";s:24:\"backend/application/main\";s:5:\"store\";a:3:{i:0;a:2:{i:0;i:0;i:1;a:3:{s:7:\"snippet\";s:22:\"i_am_select_field_show\";s:5:\"en_GB\";s:3:\"Yes\";s:5:\"de_DE\";s:2:\"Ja\";}}i:1;a:2:{i:0;i:1;i:1;a:3:{s:7:\"snippet\";s:30:\"i_am_select_field_not_show_b2c\";s:5:\"en_GB\";s:40:\"No. Customers register as B2C customers.\";s:5:\"de_DE\";s:43:\"Nein. Kunden melden sich als B2C Kunden an.\";}}i:2;a:2:{i:0;i:2;i:1;a:3:{s:7:\"snippet\";s:30:\"i_am_select_field_not_show_b2b\";s:5:\"en_GB\";s:40:\"No. Customers register as B2B customers.\";s:5:\"de_DE\";s:43:\"Nein. Kunden melden sich als B2B Kunden an.\";}}}}'),
(984,	119,	'metaIsFamilyFriendly',	'b:1;',	'Shop ist familienfreundlich',	'Setzt den Metatag \"isFamilyFriendly\" für Suchmaschinen',	'checkbox',	0,	0,	1,	'a:0:{}'),
(985,	249,	'seoCustomSiteRouteTemplate',	's:19:\"{$site.description}\";',	'SEO-Urls Shopseiten Template',	NULL,	'text',	0,	0,	1,	'a:0:{}'),
(986,	249,	'seoFormRouteTemplate',	's:12:\"{$form.name}\";',	'SEO-Urls Formular Template',	NULL,	'text',	0,	0,	1,	'a:0:{}'),
(987,	0,	'showImmediateDeliveryFacet',	'i:1;',	'',	'',	'boolean',	1,	0,	0,	NULL),
(988,	0,	'showShippingFreeFacet',	'i:1;',	'',	'',	'boolean',	1,	0,	0,	NULL),
(989,	0,	'showPriceFacet',	'i:1;',	'',	'',	'boolean',	1,	0,	0,	NULL),
(990,	0,	'showVoteAverageFacet',	'i:1;',	'',	'',	'boolean',	1,	0,	0,	NULL),
(991,	144,	'defaultListingSorting',	'i:1;',	'Kategorie Standard Sortierung',	'',	'custom-sorting-selection',	1,	0,	1,	NULL),
(992,	190,	'searchProductBoxLayout',	's:5:\"basic\";',	'Produkt Layout',	'Mit Hilfe des Produkt Layouts kannst du entscheiden, wie deine Produkte auf der Suchergebnis-Seite dargestellt werden sollen. Wähle eines der drei unterschiedlichen Layouts um die Ansicht perfekt auf dein Produktsortiment abzustimmen.',	'product-box-layout-select',	0,	0,	1,	NULL),
(993,	147,	'hideNoInStock',	'b:0;',	'Abverkaufsartikel ohne Lagerbestand ausblenden',	'Falls inaktiv, kann es zu längeren Ladezeiten im Listing kommen, wenn die aufgefächerte Variantenfilterung genutzt wird. Bei Nutzung von ElasticSearch tritt dieser Effekt nicht auf.',	'checkbox',	0,	0,	0,	NULL),
(994,	192,	'emailheaderplain',	's:0:\"\";',	'E-Mail Header Plaintext',	NULL,	'textarea',	0,	0,	1,	NULL),
(995,	192,	'emailfooterplain',	's:142:\"Mit freundlichen Grüßen\n\nIhr Team von {config name=shopName}\n\n{config name=address}<br/><br/>\nBankverbindung:<br/>\n{config name=bankAccount}\";',	'E-Mail Footer Plaintext',	NULL,	'textarea',	0,	0,	1,	NULL),
(996,	192,	'emailheaderhtml',	's:240:\"<div>\n    {if $theme.mobileLogo}\n        <img src=\"{link file=$theme.mobileLogo fullPath}\" alt=\"Logo\" />\n    {else}\n        <img src=\"{link file=\'frontend/_public/src/img/logos/logo--mobile.png\' fullPath}\" alt=\"Logo\" />\n    {/if}\n    <br />\";',	'E-Mail Header HTML',	NULL,	'textarea',	0,	0,	1,	NULL),
(997,	192,	'emailfooterhtml',	's:179:\"<br/>\nMit freundlichen Grüßen<br/><br/>\nIhr Team von {config name=shopName}</div>\n\n{{config name=address}|nl2br}<br/><br/>\nBankverbindung:<br/>\n{{config name=bankAccount}|nl2br}\";',	'E-Mail Footer HTML',	NULL,	'textarea',	0,	0,	1,	NULL),
(998,	253,	'showEsd',	'b:1;',	'Sofortdownloads im Account anzeigen',	'Sofortdownloads können weiterhin über die Bestellübersicht heruntergeladen werden.',	'boolean',	1,	5,	1,	NULL),
(999,	259,	'firstRunWizardEnabled',	'b:1;',	'\'First Run Wizard\' beim Aufruf des Backends starten',	NULL,	'checkbox',	0,	0,	0,	NULL),
(1000,	256,	'showEsdWarning',	'b:1;',	'Checkbox zum Widerrufsrecht bei ESD Artikeln anzeigen',	NULL,	'boolean',	0,	0,	1,	'a:0:{}'),
(1001,	256,	'serviceAttrField',	's:0:\"\";',	'Artikel-Freitextfeld für Dienstleistungsartikel',	NULL,	'text',	0,	0,	1,	'a:0:{}'),
(1002,	249,	'seoIndexPaginationLinks',	'b:0;',	'prev/next-Tag auf paginierten Seiten benutzen',	'Wenn aktiv, wird auf paginierten Seiten anstatt des Canoncial-Tags der prev/next-Tag benutzt.',	'checkbox',	0,	0,	0,	'a:0:{}'),
(1003,	271,	'thumbnailNoiseFilter',	'b:0;',	'Rauschfilterung bei Thumbnails',	'Filtert beim Generieren der Thumbnails Bildfehler heraus. Achtung! Bei aktivierter Option kann das Generieren der Thumbnails wesentlich länger dauern',	'checkbox',	0,	0,	0,	'a:0:{}'),
(1004,	0,	'tokenSecret',	's:0:\"\";',	'Secret für die API Kommunikation',	NULL,	'text',	0,	0,	0,	NULL),
(1005,	249,	'RelatedArticlesOnArticleNotFound',	'b:1;',	'Zeige ähnliche Artikel auf der \"Artikel nicht gefunden\" Seite an',	'Wenn aktiviert, zeigt die \"Artikel nicht gefunden\" Seite die ähnlichen Artikel Vorschläge an. Deaktiviere diese Einstellung um die Standard \"Seite nicht gefunden\" Seite darzustellen.',	'boolean',	1,	0,	1,	NULL),
(1006,	157,	'showZipBeforeCity',	'b:1;',	'PLZ vor dem Stadtfeld anzeigen',	'Legt fest ob die PLZ vor oder nach der Stadt angezeigt werden soll. Nur für Shopware 5 Themes.',	'checkbox',	0,	1006,	1,	'a:0:{}'),
(1007,	0,	'updateWizardStarted',	'b:1;',	'',	'',	'checkbox',	0,	0,	1,	NULL),
(1009,	144,	'calculateCheapestPriceWithMinPurchase',	'b:0;',	'Mindestabnahme bei der Günstigsten-Preis-Berechnung berücksichtigen',	NULL,	'checkbox',	0,	0,	1,	NULL),
(1010,	144,	'useLastGraduationForCheapestPrice',	'b:0;',	'Staffelpreise in der Günstigsten Preis Berechnung berücksichtigen',	NULL,	'checkbox',	0,	0,	1,	NULL),
(1011,	0,	'lastBacklogId',	'i:0;',	'',	'Last processed backlog id',	'',	0,	0,	0,	NULL),
(1012,	190,	'activateNumberSearch',	'i:1;',	'Nummern Suche aktivieren',	NULL,	'checkbox',	1,	0,	0,	NULL),
(1013,	190,	'enableAndSearchLogic',	'b:0;',	'\"Und\" Suchlogik verwenden',	'Die Suche zeigt nur Treffer an, in denen alle Suchbegriffe vorkommen.',	'checkbox',	0,	0,	1,	NULL),
(1014,	256,	'always_select_payment',	'b:0;',	'Zahlungsart bei Bestellung immer auswählen',	NULL,	'boolean',	0,	0,	1,	NULL),
(1015,	259,	'ajaxTimeout',	'i:30;',	'Ajax Timeout',	'Definiert die maximale Ausführungszeit für ExtJS Ajax Requests (in Sekunden)',	'number',	1,	0,	0,	'a:1:{s:8:\"minValue\";i:6;}'),
(1016,	157,	'shopsalutations',	's:17:\"mr,ms,not_defined\";',	'Verfügbare Anreden',	'Ermöglicht die Konfiguration welche Anreden in diesem Shop zur Verfügung stehen. Die hier definierten Keys werden automatisch als Textbaustein unter dem Namespace frontend/salutation angelegt und können dort übersetzt werden.',	'text',	0,	1016,	1,	NULL),
(1017,	157,	'displayprofiletitle',	'b:0;',	'Titel Feld anzeigen',	NULL,	'boolean',	0,	1017,	1,	NULL),
(1018,	0,	'installationDate',	's:16:\"2019-12-06 10:19\";',	'Installationsdatum',	NULL,	'text',	0,	0,	0,	NULL),
(1020,	0,	'assetTimestamp',	'i:0;',	'',	'Cache invalidation timestamp for assets',	'',	0,	0,	1,	NULL),
(1021,	277,	'sendRegisterConfirmation',	'b:1;',	'Bestätigungsmail nach Registrierung verschicken',	NULL,	'boolean',	0,	20,	0,	NULL),
(1022,	144,	'maxStoreFrontLimit',	'i:100;',	'Maximale Anzahl Produkte pro Seite',	NULL,	'number',	0,	0,	0,	NULL),
(1023,	189,	'strip_tags',	'b:1;',	'Global strip_tags verwenden',	'Wenn aktiviert wird jeder Formularinput im Frontend mittels strip_tags gefiltert.',	'checkbox',	1,	0,	0,	NULL),
(1024,	90,	'displayOnlySubShopVotes',	'b:0;',	'Nur Subshopspezifische Bewertungen anzeigen',	'Legt fest, ob zu einem Artikel nur die Bewertungen aus dem entsprechenden Shop angezeigt werden sollen.',	'checkbox',	0,	0,	1,	NULL),
(1025,	274,	'captchaMethod',	's:7:\"default\";',	'Captcha Methode',	'Wähle hier eine Methode aus, wie die Formulare gegen Spam-Bots geschützt werden sollen',	'combo',	1,	0,	1,	'a:5:{s:8:\"editable\";b:0;s:10:\"valueField\";s:2:\"id\";s:12:\"displayField\";s:11:\"displayname\";s:13:\"triggerAction\";s:3:\"all\";s:5:\"store\";s:12:\"base.Captcha\";}'),
(1026,	274,	'noCaptchaAfterLogin',	'b:0;',	'Nach Login ausblenden',	'Nach dem Login können Kunden Formulare ohne Captcha-Überprüfung absenden.',	'checkbox',	0,	1,	1,	NULL),
(1027,	144,	'displayListingBuyButton',	'b:0;',	'Kaufenbutton im Listing anzeigen',	'',	'checkbox',	1,	0,	1,	NULL),
(1028,	277,	'show_cookie_note',	'b:1;',	'Cookie Hinweis anzeigen',	'Wenn diese Option aktiv ist, wird eine Hinweismeldung angezeigt die den Nutzer über die Cookie-Richtlinien informiert. Der Inhalt kann über das Textbausteinmodul editiert werden.',	'boolean',	0,	21,	1,	NULL),
(1029,	277,	'data_privacy_statement_link',	's:0:\"\";',	'Link zur Datenschutzerklärung für Cookies',	NULL,	'text',	0,	20,	1,	NULL),
(1030,	0,	'listingMode',	's:16:\"full_page_reload\";',	'',	'',	'listing-filter-mode-select',	1,	0,	0,	NULL),
(1031,	157,	'registerCaptcha',	's:9:\"nocaptcha\";',	'Captcha in Registrierung verwenden',	'Wenn diese Option aktiv ist, wird ein Captcha zur Registrierung verwendent. Empfohlen für die Registrierung: Honeypot',	'combo',	1,	1031,	1,	'a:5:{s:8:\"editable\";b:0;s:10:\"valueField\";s:2:\"id\";s:12:\"displayField\";s:11:\"displayname\";s:13:\"triggerAction\";s:3:\"all\";s:5:\"store\";s:12:\"base.Captcha\";}'),
(1032,	190,	'searchSortings',	's:13:\"|7|1|2|3|4|5|\";',	'Verfügbare Sortierungen',	'',	'custom-sorting-grid',	1,	0,	1,	NULL),
(1033,	190,	'searchFacets',	's:15:\"|1|2|3|4|5|6|7|\";',	'Verfügbare filter',	'',	'custom-facet-grid',	0,	0,	1,	NULL),
(1034,	263,	'newsletterCaptcha',	's:9:\"nocaptcha\";',	'Captcha in Newsletter verwenden',	'Die hier ausgewählte Captcha Methode wird bei der Newsletterregistrierung im Frontend verwendet.',	'combo',	1,	0,	1,	'a:5:{s:8:\"editable\";b:0;s:10:\"valueField\";s:2:\"id\";s:12:\"displayField\";s:11:\"displayname\";s:13:\"triggerAction\";s:3:\"all\";s:5:\"store\";s:12:\"base.Captcha\";}'),
(1035,	157,	'useSltCookie',	'b:1;',	'Shopware Login Cookie erstellen',	'Es wird ein Cookie gespeichert, an dem der Benutzer wieder identifiziert werden kann. Dieser wird nur für das Setzen der aktuellen Kundengruppe sowie aktiven Customer Streams verwendet',	'boolean',	1,	1035,	0,	NULL),
(1036,	259,	'backendMenuOnHover',	'b:1;',	'Backend Menüeinträge automatisch ausklappen',	'Das Verhalten der Buttons in der oberen Menüleiste im Backend ändert sich mit dieser Option. Falls diese Option auf Nein gesetzt ist, müssen die Menüeinträge manuell durch einen Mausklick geöffnet werden. (Backend Cache leeren und Neuladen des Backends erforderlich)',	'checkbox',	0,	0,	0,	NULL),
(1037,	259,	'growlMessageDisplayPosition',	's:9:\"top-right\";',	'Benachrichtigungs Position',	'Mit dieser Option können die Backend Benachrichtungen an einer anderen Stelle angezeigt werden (Backend Cache leeren und Neuladen des Backends erforderlich)',	'select',	1,	0,	0,	'a:5:{s:8:\"editable\";b:0;s:10:\"valueField\";s:8:\"position\";s:12:\"displayField\";s:11:\"displayName\";s:9:\"queryMode\";s:5:\"local\";s:5:\"store\";s:19:\"base.CornerPosition\";}'),
(1038,	268,	'logMailAddress',	's:0:\"\";',	'Alternative E-Mail-Adressen für Fehlermeldungen',	'Wenn dieses Feld leer ist, wird die Shopbetreiber E-Mail-Adresse verwendet. Pro Zeile kann eine Empfängeradresse angegeben werden.',	'textarea',	0,	0,	0,	NULL),
(1039,	144,	'manufacturerProductBoxLayout',	's:5:\"basic\";',	'Produktlayout im Herstellerlisting',	'',	'product-box-layout-select',	0,	0,	1,	NULL),
(1040,	268,	'logMailLevel',	's:7:\"Warning\";',	'Log-Level',	'Hier wird festgelegt, ab welchem Log-Level E-Mails versendet werden. Im Standard werden E-Mails ab dem Log-Level \"Warning\" verschickt. Um nur E-Mails bei Fehlern zu bekommen, kannst du das Log-Level erhöhen, zum Beispiel auf \"Error\" oder höher.',	'select',	1,	0,	0,	'a:1:{s:5:\"store\";a:8:{i:0;a:2:{i:0;s:5:\"DEBUG\";i:1;s:5:\"Debug\";}i:1;a:2:{i:0;s:4:\"INFO\";i:1;s:4:\"Info\";}i:2;a:2:{i:0;s:6:\"NOTICE\";i:1;s:6:\"Notice\";}i:3;a:2:{i:0;s:7:\"WARNING\";i:1;s:7:\"Warning\";}i:4;a:2:{i:0;s:5:\"ERROR\";i:1;s:5:\"Error\";}i:5;a:2:{i:0;s:8:\"CRITICAL\";i:1;s:8:\"Critical\";}i:6;a:2:{i:0;s:5:\"ALERT\";i:1;s:5:\"Alert\";}i:7;a:2:{i:0;s:9:\"EMERGENCY\";i:1;s:9:\"Emergency\";}}}'),
(1041,	277,	'actdprtext',	'b:1;',	'Datenschutzhinweise anzeigen',	'Betrifft die Formulare der Registrierung, Blog- & Artikelkommentare, Newsletter, Produkt-Verfügbarkeitsbenachrichtigung (Notification-Plugin) sowie die eigenen Formulare',	'boolean',	0,	0,	0,	NULL),
(1042,	277,	'privacyGuestCustomerMonths',	'i:6;',	'Schnellbesteller ohne Bestellungen nach X Monaten löschen',	'Der Cronjob \"Guest customer cleanup\" muss hierfür aktiviert sein.',	'number',	1,	30,	0,	NULL),
(1043,	277,	'privacyBasketMonths',	'i:6;',	'Abgebrochene Bestellungen nach X Monaten löschen',	'Der Cronjob \"Cancelled baskets cleanup\" muss hierfür aktiviert sein.',	'number',	1,	30,	0,	NULL),
(1044,	277,	'anonymizeIp',	'b:1;',	'Kunden IPs anonymisieren',	'Entfernt die letzten zwei Blöcke einer IPv4, resp. drei Blöcke einer IPv6 Adresse in Statistiken und Bestellungen, um rechtliche Rahmenbedingungen einzuhalten.',	'boolean',	0,	40,	0,	NULL),
(1045,	277,	'optinregister',	'b:0;',	'Double-Opt-In für Registrierung',	NULL,	'boolean',	0,	15,	0,	NULL),
(1046,	277,	'optintimetodelete',	'i:3;',	'Tage ohne Verifizierung bis zur Löschung',	'Für Double-Opt-In: Zeitraum, nachdem nicht bestätigte Aktionen gelöscht werden.',	'number',	0,	17,	0,	NULL),
(1047,	277,	'optinaccountless',	'b:0;',	'Double-Opt-In für Schnellbesteller',	NULL,	'boolean',	0,	16,	0,	NULL),
(1048,	277,	'cookie_note_mode',	'i:1;',	'Cookie-Hinweis-Modus',	NULL,	'select',	0,	21,	0,	'a:2:{s:5:\"store\";s:35:\"Shopware.apps.Base.store.CookieMode\";s:9:\"queryMode\";s:5:\"local\";}'),
(1050,	147,	'proportionalTaxCalculation',	'b:0;',	'Anteilige Berechnung der Steuer-Positionen',	'',	'boolean',	0,	0,	0,	NULL),
(1051,	249,	'hrefLangEnabled',	'b:1;',	'href-lang in den Meta-Tags ausgeben',	'Wenn aktiv, werden in den Meta Tags alle Sprachen einer Seite ausgegeben',	'boolean',	0,	50,	0,	NULL),
(1052,	249,	'hrefLangCountry',	'b:1;',	'Im href-lang Sprache und Land verwenden',	'Wenn diese Option aktiviert ist, wird zusätzlich zur Sprache auch das Land ausgegeben, z.B. \"de-DE\" anstatt \"de\"',	'boolean',	0,	50,	1,	NULL),
(1053,	0,	'sitemapRefreshStrategy',	'i:3;',	'',	'',	'',	1,	0,	0,	NULL),
(1054,	0,	'sitemapRefreshTime',	'i:86400;',	'',	'',	'',	1,	0,	0,	NULL),
(1055,	0,	'sitemapLastRefresh',	'i:0;',	'',	'',	'',	1,	0,	0,	NULL),
(1056,	0,	'missingLicenseWarningThreshold',	'i:14;',	'',	'',	'',	1,	0,	0,	NULL),
(1057,	0,	'missingLicenseStopThreshold',	'i:21;',	'',	'',	'',	1,	0,	0,	NULL),
(1058,	249,	'hrefLangDefaultShop',	's:0:\"\";',	'href-lang Standardsprache',	'Gibt für diesen Shop \"x-default\" im href-lang-Tag aus und definiert damit die Sprache dieses Shops als Standardsprache.',	'combo',	0,	50,	0,	'a:4:{s:10:\"valueField\";s:2:\"id\";s:12:\"displayValue\";s:4:\"name\";s:5:\"store\";s:17:\"base.ShopLanguage\";s:9:\"queryMode\";s:6:\"remote\";}'),
(1059,	249,	'metaDescriptionLength',	's:3:\"150\";',	'Maximal erlaubte Länge der Meta Description',	'',	'number',	0,	0,	0,	NULL),
(1060,	278,	'displayOnlySubShopBlogComments',	'b:0;',	'Nur subshopspezifische Blog-Kommentare anzeigen',	'Wenn aktiv, werden nur Blog-Kommentare des zugehörigen Shops angezeigt.<br>Falls inaktiv, werden unabhängig vom Sprach- oder Subshop stets alle Blog-Kommentare angezeigt',	'checkbox',	0,	0,	1,	NULL),
(1061,	190,	'minSearchIndexLength',	'i:3;',	'Minimale Keyword-Länge für die Indexierung',	'Diese Einstellung bestimmt die minimale Keyword-Länge für die Indexierung. <b>Standard: 3 Zeichen</b>',	'number',	0,	0,	0,	NULL),
(1062,	256,	'alwaysShowMainFeatures',	'b:0;',	'Wesentliche Merkmale im gesamten Checkout Prozess anzeigen',	'Wenn aktiviert, werden die wesentlichen Merkmale im gesamten Checkout angezeigt. Andernfalls tauchen diese nur auf der Bestell-Bestätigungsseite auf.',	'boolean',	0,	1,	1,	NULL),
(1063,	147,	'showShippingCostsOffCanvas',	'i:1;',	'Versandkostenberechnung im Mini-/OffCanvas-Warenkorb anzeigen',	'Diese Option aktiviert die Versandkostenberechnung für den Mini- bzw. OffCanvas-Warenkorb. Dies ist nur für Kunden verfügbar, die nicht angemeldet sind.',	'select',	0,	6,	1,	'a:5:{s:8:\"editable\";b:0;s:14:\"forceSelection\";b:1;s:22:\"translateUsingSnippets\";b:1;s:9:\"namespace\";s:24:\"backend/application/main\";s:5:\"store\";a:3:{i:0;a:2:{i:0;i:0;i:1;a:3:{s:7:\"snippet\";s:30:\"shipping_calculations_not_show\";s:5:\"en_GB\";s:2:\"No\";s:5:\"de_DE\";s:4:\"Nein\";}}i:1;a:2:{i:0;i:1;i:1;a:3:{s:7:\"snippet\";s:33:\"shipping_calculations_show_folded\";s:5:\"en_GB\";s:9:\"Collapsed\";s:5:\"de_DE\";s:11:\"Eingeklappt\";}}i:2;a:2:{i:0;i:2;i:1;a:3:{s:7:\"snippet\";s:35:\"shipping_calculations_show_expanded\";s:5:\"en_GB\";s:8:\"Expanded\";s:5:\"de_DE\";s:11:\"Ausgeklappt\";}}}}'),
(1064,	0,	'http2Push',	'b:1;',	NULL,	NULL,	'',	1,	0,	0,	NULL),
(1065,	0,	'minifyHtml',	'b:0;',	NULL,	NULL,	'',	1,	0,	0,	NULL),
(1066,	256,	'showVoucherModeForCheckout',	'i:2;',	'Gutscheinfeld im Bestellabschluss anzeigen',	NULL,	'select',	0,	0,	0,	'a:5:{s:8:\"editable\";b:0;s:14:\"forceSelection\";b:1;s:22:\"translateUsingSnippets\";b:1;s:9:\"namespace\";s:24:\"backend/application/main\";s:5:\"store\";a:3:{i:0;a:2:{i:0;i:0;i:1;a:3:{s:7:\"snippet\";s:21:\"voucher_mode_not_show\";s:5:\"en_GB\";s:2:\"No\";s:5:\"de_DE\";s:4:\"Nein\";}}i:1;a:2:{i:0;i:1;i:1;a:3:{s:7:\"snippet\";s:24:\"voucher_mode_show_folded\";s:5:\"en_GB\";s:9:\"Collapsed\";s:5:\"de_DE\";s:11:\"Eingeklappt\";}}i:2;a:2:{i:0;i:2;i:1;a:3:{s:7:\"snippet\";s:26:\"voucher_mode_show_expanded\";s:5:\"en_GB\";s:8:\"Expanded\";s:5:\"de_DE\";s:11:\"Ausgeklappt\";}}}}'),
(1067,	147,	'showVoucherModeForCart',	'i:2;',	'Gutscheinfeld im Warenkorb anzeigen',	NULL,	'select',	0,	0,	0,	'a:5:{s:8:\"editable\";b:0;s:14:\"forceSelection\";b:1;s:22:\"translateUsingSnippets\";b:1;s:9:\"namespace\";s:24:\"backend/application/main\";s:5:\"store\";a:3:{i:0;a:2:{i:0;i:0;i:1;a:3:{s:7:\"snippet\";s:21:\"voucher_mode_not_show\";s:5:\"en_GB\";s:2:\"No\";s:5:\"de_DE\";s:4:\"Nein\";}}i:1;a:2:{i:0;i:1;i:1;a:3:{s:7:\"snippet\";s:24:\"voucher_mode_show_folded\";s:5:\"en_GB\";s:9:\"Collapsed\";s:5:\"de_DE\";s:11:\"Eingeklappt\";}}i:2;a:2:{i:0;i:2;i:1;a:3:{s:7:\"snippet\";s:26:\"voucher_mode_show_expanded\";s:5:\"en_GB\";s:8:\"Expanded\";s:5:\"de_DE\";s:11:\"Ausgeklappt\";}}}}'),
(1068,	147,	'roundNetAfterTax',	'b:1;',	'Netto-Bestellungen konsequent auf 2 Stellen runden',	'',	'boolean',	0,	0,	0,	NULL),
(1069,	147,	'clearBasketAfterLogout',	'b:1;',	'Warenkorb beim Logout leeren',	'Falls aktiv, wird der Warenkorb nach einem Logout geleert.<br>Falls inaktiv, wird der Warenkorb beim Logout nicht geleert und bleibt für einen späteren Login erhalten.',	'boolean',	0,	0,	0,	NULL),
(1070,	157,	'migrateCartAfterLogin',	'b:1;',	'Gespeicherte Warenkörbe beim Login wiederherstellen',	'Es wird nur wiederhergestellt, wenn der Warenkorb beim Login leer ist.',	'boolean',	0,	1070,	0,	NULL),
(1071,	157,	'shareSessionBetweenLanguageShops',	'b:1;',	'Browser-Sitzung zwischen Sprachshops teilen',	'Wenn aktiv, wird die Browser-Sitzung zwischen Sprachshops geteilt. Somit verliert der Kunde den Warenkorb beim Wechseln nicht.<br><br><strong>Achtung:</strong> Sind Artikel in einzelnen Sprachvarianten nicht verfügbar, werden diese aus Warenkörben entfernt und können innerhalb der Sitzung nicht mehr wiederhergestellt werden.',	'boolean',	0,	1071,	0,	NULL),
(1072,	0,	'mailLogActive',	'b:0;',	'Activate logging of e-mails',	'When this option is active, outgoing e-mails will be saved.',	'boolean',	0,	0,	0,	NULL),
(1073,	0,	'mailLogActiveFilters',	'a:0:{}',	'Active mail-type-filters',	'Filters listed here will be active.',	'text',	0,	0,	0,	NULL),
(1074,	0,	'mailLogCleanupMaximumAgeInDays',	'i:365;',	'Maximum age for log entries in days',	'The MailLogCleanup cronjob must be active for this setting to have an effect. When the cronjob is executed, entries older than configured here will be deleted.',	'number',	0,	0,	0,	NULL),
(1075,	157,	'birthdaySingleField',	'b:0;',	'Geburtstag als Datumsfeld anzeigen',	'Wenn aktiv, wird das Geburtsdatum als einzelnes Datumsfeld dargestellt, statt drei einzelnen Feldern.',	'boolean',	1,	971,	0,	NULL),
(1076,	191,	'shippingSurchargeNumber',	's:19:\"swShippingSurcharge\";',	'Aufschlag-Versandregel (Bestellnummer)',	NULL,	'text',	0,	0,	0,	NULL),
(1077,	277,	'cookie_show_button',	'b:0;',	'\"Alle akzeptieren\" Button in Cookie Hinweis anzeigen',	'Gilt nur für den Modus \"Technisch notwendige Cookies\". Bevor Du diese Einstellung änderst, solltest du die Verwendung vorab von Deiner Rechtsberatung prüfen lassen.',	'boolean',	0,	22,	0,	NULL),
(1078,	265,	'argon2MemoryCost',	'i:65536;',	'Argon2-Speicher',	'Ein höherer Speicherverbrauch macht es einem möglichen Angreifer schwerer, ein passendes Klartext-Passwort zu erzeugen.',	'number',	1,	0,	0,	'a:2:{s:8:\"minValue\";s:4:\"1024\";s:8:\"maxValue\";s:19:\"4611686018427387904\";}'),
(1079,	265,	'argon2TimeCost',	'i:4;',	'Argon2-Zeit',	'Ein höherer Zeitaufwand macht es einem möglichen Angreifer schwerer, ein passendes Klartext-Passwort zu erzeugen.',	'number',	1,	0,	0,	'a:2:{s:8:\"minValue\";s:1:\"1\";s:8:\"maxValue\";s:2:\"30\";}'),
(1080,	265,	'argon2Threads',	'i:1;',	'Argon2-Threads',	'Anzahl paralleler Threads zur Erzeugung nutzen',	'number',	1,	0,	0,	'a:2:{s:8:\"minValue\";s:1:\"1\";s:8:\"maxValue\";s:2:\"32\";}'),
(1081,	249,	'hrefLangJustSeoUrl',	'b:1;',	'Nur SEO-Urls in href-lang ausgeben',	'Wenn aktiv, werden in den Meta Tags \"href-lang\" nur SEO-Urls ausgegeben',	'boolean',	0,	200,	0,	NULL),
(1082,	147,	'show_all_countries',	'b:0;',	'Alle Länder im Lieferland-Dropdown anzeigen',	'',	'boolean',	0,	23,	0,	NULL),
(1083,	249,	'ignore_trailing_slash',	'b:1;',	'URLs ohne abschließenden Slash weiterleiten',	'Wenn aktiv, werden URLs, die normalerweise auf einen Slash (“/”) enden und ohne diesen aufgerufen werden, mittels http-code 301 auf die korrekte Seite mit Slash weitergeleitet. Der Canonical zeigt dabei immer auf die korrekte Seite mit Slash',	'boolean',	1,	0,	0,	NULL),
(1084,	144,	'hide_categories_in_offcanvas',	'b:0;',	'Kategorien auch im Offcanvas-Menü ausblenden',	'Wird eine Kategorie nicht in der Top-Navigation angezeigt, wird sie mit dieser Option auch nicht im Offcanvas-Menü gezeigt.',	'boolean',	0,	0,	0,	NULL),
(1085,	277,	'cookieTimeout',	'i:60;',	'Cookie nach X Tagen invalidieren',	'Invalidiert Cookies nach festgelegter Zeit',	'number',	1,	31,	0,	NULL),
(1086,	279,	'passwordResetCaptcha',	's:9:\"nocaptcha\";',	'Captcha für das Zurücksetzen des Passworts',	'Wenn diese Option aktiv ist, wird das Formular zum Zurücksetzen des Passworts mit einem Captcha geschützt.',	'combo',	1,	0,	1,	'a:5:{s:8:\"editable\";b:0;s:10:\"valueField\";s:2:\"id\";s:12:\"displayField\";s:11:\"displayname\";s:13:\"triggerAction\";s:3:\"all\";s:5:\"store\";s:12:\"base.Captcha\";}'),
(1087,	280,	'notificationCaptchaConfig',	's:9:\"nocaptcha\";',	'Captcha für E-Mail Benachrichtigungen von Produkten',	'Wenn diese Option aktiv ist, wird das Formular zur E-Mail Benachrichtigung für die Verfügbarkeit eines Produktes mit einem Captcha geschützt.',	'combo',	1,	0,	1,	'a:5:{s:8:\"editable\";b:0;s:10:\"valueField\";s:2:\"id\";s:12:\"displayField\";s:11:\"displayname\";s:13:\"triggerAction\";s:3:\"all\";s:5:\"store\";s:12:\"base.Captcha\";}'),
(1088,	157,	'shopSalutationRequired',	'b:1;',	'Anrede benötigt',	'Ob eine Anrede bei der Registrierung benötigt wird oder nicht.',	'boolean',	1,	1015,	1,	''),
(1089,	281,	'enforceSSL',	'b:1;',	'Enforce secure connection',	'Enforces an SSL encrypted connection for the API request.',	'boolean',	1,	0,	0,	'a:0:{}');

DROP TABLE IF EXISTS `s_core_config_form_translations`;
CREATE TABLE `s_core_config_form_translations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `form_id` int unsigned NOT NULL,
  `locale_id` int unsigned NOT NULL,
  `label` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_config_form_translations` (`id`, `form_id`, `locale_id`, `label`, `description`) VALUES
(1,	77,	2,	'Shop settings',	NULL),
(2,	78,	2,	'System',	NULL),
(3,	79,	2,	'Items',	NULL),
(4,	80,	2,	'Frontend',	NULL),
(5,	82,	2,	'Interfaces',	NULL),
(6,	83,	2,	'Payment methods',	NULL),
(7,	84,	2,	'Item numbers',	NULL),
(8,	86,	2,	'Other VAT rates',	NULL),
(9,	87,	2,	'Price groups',	NULL),
(10,	88,	2,	'Price units',	NULL),
(12,	90,	2,	'Customer reviews',	NULL),
(13,	92,	2,	'Additional settings',	NULL),
(14,	102,	2,	'Recently viewed items',	NULL),
(15,	118,	2,	'Shops',	NULL),
(16,	119,	2,	'Basic information',	NULL),
(17,	120,	2,	'Currencies',	NULL),
(18,	121,	2,	'Localizations',	NULL),
(19,	122,	2,	'Templates',	NULL),
(20,	123,	2,	'Taxes',	NULL),
(21,	124,	2,	'Mailers',	NULL),
(22,	125,	2,	'Number ranges',	NULL),
(23,	126,	2,	'Customer groups',	NULL),
(24,	127,	2,	'Caching',	NULL),
(25,	128,	2,	'Maintenance',	NULL),
(26,	133,	2,	'Advanced menu',	NULL),
(27,	134,	2,	'Item comparison',	NULL),
(28,	135,	2,	'Tag cloud',	NULL),
(29,	144,	2,	'Categories / lists',	NULL),
(30,	145,	2,	'Top seller / novelties',	NULL),
(31,	146,	2,	'Cross selling / item details',	NULL),
(32,	147,	2,	'Shopping cart / item details',	NULL),
(33,	157,	2,	'Login / registration',	NULL),
(34,	173,	2,	'Statstics',	NULL),
(35,	174,	2,	'Google Analytics',	NULL),
(36,	175,	2,	'HttpCache',	NULL),
(37,	176,	2,	'Log',	NULL),
(38,	177,	2,	'Debug',	NULL),
(39,	180,	2,	'Countries',	NULL),
(40,	189,	2,	'Input filter',	NULL),
(41,	190,	2,	'Search',	NULL),
(42,	191,	2,	'Discounts / surcharges',	NULL),
(43,	192,	2,	'Email settings',	NULL),
(44,	247,	2,	'Shipping costs module',	NULL),
(46,	249,	2,	'SEO / router settings',	NULL),
(48,	251,	2,	'Country areas',	NULL),
(50,	253,	2,	'ESD',	NULL),
(51,	255,	2,	'Item recommendations',	NULL),
(52,	256,	2,	'Checkout',	NULL),
(53,	257,	2,	'Shop page groups',	NULL),
(54,	258,	2,	'Cronjobs',	NULL),
(55,	259,	2,	'Backend',	NULL),
(56,	261,	2,	'PDF document creation',	NULL),
(57,	262,	2,	'Store API',	NULL),
(58,	264,	2,	'Legacy options',	NULL),
(59,	265,	2,	'Passwords',	NULL),
(61,	267,	2,	'SEPA configuration',	NULL),
(62,	271,	2,	'Media',	''),
(63,	270,	2,	'Multi edit',	NULL),
(64,	272,	2,	'Sitemap',	NULL),
(65,	273,	2,	'Shopware license',	NULL),
(67,	276,	2,	'Filter / Sorting',	NULL),
(68,	277,	2,	'Privacy',	NULL),
(69,	278,	2,	'Blog',	NULL),
(70,	279,	2,	'Password Reset',	NULL),
(71,	280,	2,	'Product Notification',	NULL);

DROP TABLE IF EXISTS `s_core_config_forms`;
CREATE TABLE `s_core_config_forms` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int unsigned DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `label` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `position` int NOT NULL,
  `plugin_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `plugin_id` (`plugin_id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_config_forms` (`id`, `parent_id`, `name`, `label`, `description`, `position`, `plugin_id`) VALUES
(77,	NULL,	'Base',	'Shopeinstellungen',	NULL,	0,	NULL),
(78,	NULL,	'Core',	'System',	NULL,	10,	NULL),
(79,	NULL,	'Product',	'Artikel',	NULL,	20,	NULL),
(80,	NULL,	'Frontend',	'Storefront',	NULL,	30,	NULL),
(82,	NULL,	'Interface',	'Schnittstellen',	NULL,	50,	NULL),
(83,	NULL,	'Payment',	'Zahlungsarten',	NULL,	60,	NULL),
(84,	79,	'Product29',	'Artikelnummern',	NULL,	1,	NULL),
(86,	79,	'Product35',	'Sonstige MwSt.-Sätze',	NULL,	4,	NULL),
(87,	79,	'PriceGroup',	'Preisgruppen',	NULL,	5,	NULL),
(88,	79,	'Unit',	'Preiseinheiten',	NULL,	6,	NULL),
(90,	80,	'Rating',	'Artikelbewertungen',	NULL,	8,	NULL),
(92,	NULL,	'Other',	'Weitere Einstellungen',	NULL,	60,	NULL),
(102,	80,	'LastArticles',	'Artikelverlauf',	'',	0,	NULL),
(118,	77,	'Shop',	'Shops',	NULL,	0,	NULL),
(119,	77,	'MasterData',	'Stammdaten',	NULL,	10,	NULL),
(120,	77,	'Currency',	'Währungen',	NULL,	20,	NULL),
(121,	77,	'Locale',	'Lokalisierungen',	NULL,	30,	NULL),
(123,	77,	'Tax',	'Steuern',	NULL,	50,	NULL),
(124,	77,	'Mail',	'Mailer',	NULL,	60,	NULL),
(125,	77,	'Number',	'Nummernkreise',	NULL,	70,	NULL),
(126,	77,	'CustomerGroup',	'Kundengruppen',	NULL,	80,	NULL),
(128,	78,	'Service',	'Wartung',	NULL,	20,	NULL),
(133,	80,	'AdvancedMenu',	'Erweitertes Menü',	'',	0,	29),
(134,	80,	'Compare',	'Artikelvergleich',	NULL,	0,	NULL),
(144,	80,	'Frontend30',	'Kategorien / Listen',	NULL,	1,	NULL),
(145,	80,	'Frontend76',	'Topseller / Neuheiten',	NULL,	2,	NULL),
(146,	80,	'Frontend77',	'Cross-Selling / Ähnliche Art.',	NULL,	3,	NULL),
(147,	80,	'Frontend79',	'Warenkorb / Artikeldetails',	NULL,	5,	NULL),
(157,	80,	'Frontend33',	'Anmeldung / Registrierung',	NULL,	0,	NULL),
(173,	78,	'Statistics',	'Statistiken',	'',	0,	31),
(180,	77,	'Country',	'Länder',	NULL,	50,	NULL),
(189,	78,	'InputFilter',	'InputFilter',	'',	0,	35),
(190,	80,	'Search',	'Suche',	NULL,	4,	NULL),
(191,	80,	'Frontend71',	'Rabatte / Zuschläge',	NULL,	5,	NULL),
(192,	80,	'Frontend60',	'E-Mail-Einstellungen',	NULL,	10,	NULL),
(247,	80,	'Frontend93',	'Versandkosten-Modul',	NULL,	11,	NULL),
(249,	80,	'Frontend100',	'SEO/Router-Einstellungen',	NULL,	12,	NULL),
(251,	77,	'CountryArea',	'Länder-Zonen',	NULL,	51,	NULL),
(253,	79,	'Esd',	'ESD',	NULL,	0,	NULL),
(255,	80,	'Recommendation',	'Artikelempfehlungen',	NULL,	8,	NULL),
(256,	80,	'Checkout',	'Bestellabschluss',	NULL,	0,	NULL),
(257,	77,	'PageGroup',	'Shopseiten-Gruppen',	NULL,	90,	NULL),
(258,	78,	'CronJob',	'Cronjobs',	NULL,	50,	NULL),
(259,	78,	'Auth',	'Backend',	'',	0,	36),
(261,	77,	'Document',	'PDF-Belegerstellung',	NULL,	90,	NULL),
(263,	92,	'Newsletter',	'Newsletter',	NULL,	0,	NULL),
(264,	92,	'LegacyOptions',	'Abwärtskompatibilität',	NULL,	0,	NULL),
(265,	78,	'Passwörter',	'Passwörter',	NULL,	0,	49),
(266,	78,	'HttpCache',	'Frontend cache (HTTP cache)',	NULL,	0,	52),
(267,	80,	'SEPA',	'SEPA-Konfiguration',	NULL,	0,	NULL),
(268,	78,	'Log',	'Log',	NULL,	0,	2),
(270,	92,	'MultiEdit',	'Mehrfachänderung',	'',	0,	NULL),
(271,	80,	'Media',	'Medien',	NULL,	13,	NULL),
(273,	92,	'CoreLicense',	'Shopware-Lizenz',	NULL,	0,	NULL),
(274,	80,	'Captcha',	'Captcha',	NULL,	0,	NULL),
(276,	80,	'CustomSearch',	'Filter / Sortierung',	NULL,	0,	NULL),
(277,	92,	'Privacy',	'Datenschutz',	NULL,	0,	NULL),
(278,	80,	'Blog',	'Blog',	NULL,	0,	NULL),
(279,	80,	'passwordReset',	'Passwort Zurücksetzen',	NULL,	0,	NULL),
(280,	80,	'notificationCaptchaConfig',	'Produkt Benachrichtigungen',	NULL,	0,	NULL),
(281,	92,	'SwagMigrationConnector',	'Shopware Migration Connector',	'The Migration Connector provides API endpoints that allow Shopware 6 to create a secure data connection with the active Shopware 5 shop which support the data migration to Shopware 6.',	0,	61);

DROP TABLE IF EXISTS `s_core_config_mails`;
CREATE TABLE `s_core_config_mails` (
  `id` int NOT NULL AUTO_INCREMENT,
  `stateId` int DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `frommail` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `fromname` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `subject` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `content` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `contentHTML` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `ishtml` int NOT NULL,
  `attachment` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `mailtype` int NOT NULL DEFAULT '1',
  `context` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `dirty` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `stateId` (`stateId`),
  CONSTRAINT `s_core_config_mails_ibfk_1` FOREIGN KEY (`stateId`) REFERENCES `s_core_states` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_config_mails` (`id`, `stateId`, `name`, `frommail`, `fromname`, `subject`, `content`, `contentHTML`, `ishtml`, `attachment`, `mailtype`, `context`, `dirty`) VALUES
(1,	NULL,	'sREGISTERCONFIRMATION',	'{config name=mail}',	'{config name=shopName}',	'Ihre Anmeldung bei {config name=shopName}',	'{include file=\"string:{config name=emailheaderplain}\"}\n        \nHallo {$firstname} {$lastname},\n\nvielen Dank für Ihre Anmeldung in unserem Shop.\nSie erhalten Zugriff über Ihre E-Mail-Adresse {$sMAIL} und dem von Ihnen gewählten Kennwort.\nSie können Ihr Kennwort jederzeit nachträglich ändern.\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n            {include file=\"string:{config name=emailheaderhtml}\"}\n            <br/><br/>\n            <p>\n                Hallo {$firstname} {$lastname},<br/>\n                <br/>\n                vielen Dank für Ihre Anmeldung in unserem Shop.<br/>\n                Sie erhalten Zugriff über Ihre E-Mail-Adresse <strong>{$sMAIL}</strong> und dem von Ihnen gewählten Kennwort.<br/>\n                Sie können Ihr Kennwort jederzeit nachträglich ändern.\n            </p>\n            {include file=\"string:{config name=emailfooterhtml}\"}\n        </div>',	1,	'',	2,	'a:16:{s:5:\"sMAIL\";s:13:\"test@test.com\";s:5:\"sShop\";s:13:\"swagfiveseven\";s:8:\"sShopURL\";s:49:\"http://swagfiveseven.de\";s:7:\"sConfig\";a:0:{}s:6:\"street\";s:13:\"teststraße 1\";s:7:\"zipcode\";s:5:\"12345\";s:4:\"city\";s:7:\"testort\";s:7:\"country\";s:1:\"2\";s:5:\"state\";N;s:13:\"customer_type\";s:7:\"private\";s:10:\"salutation\";s:4:\"Herr\";s:9:\"firstname\";s:4:\"Test\";s:8:\"lastname\";s:4:\"Test\";s:11:\"accountmode\";s:1:\"0\";s:5:\"email\";s:13:\"test@test.com\";s:10:\"additional\";a:1:{s:13:\"customer_type\";s:7:\"private\";}}',	0),
(2,	NULL,	'sORDER',	'{config name=mail}',	'{config name=shopName}',	'Ihre Bestellung im {config name=shopName}',	'{include file=\"string:{config name=emailheaderplain}\"}\n        \nHallo {$billingaddress.firstname} {$billingaddress.lastname},\n\nvielen Dank für Ihre Bestellung im {config name=shopName} (Nummer: {$sOrderNumber}) am {$sOrderDay} um {$sOrderTime}.\nInformationen zu Ihrer Bestellung:\n\nPos.  Art.Nr.               Beschreibung                                      Menge       Preis       Summe\n{foreach item=details key=position from=$sOrderDetails}\n{{$position+1}|fill:4}  {$details.ordernumber|fill:20}  {$details.articlename|fill:49}  {$details.quantity|fill:6}  {$details.price|padding:8|currency|unescape:\"htmlall\"}      {$details.amount|padding:8|currency|unescape:\"htmlall\"}\n{/foreach}\n\nVersandkosten: {$sShippingCosts|currency|unescape:\"htmlall\"}\nGesamtkosten Netto: {$sAmountNet|currency|unescape:\"htmlall\"}\n{if !$sNet}\n{foreach $sTaxRates as $rate => $value}\nzzgl. {$rate|number_format:0}% MwSt. {$value|currency|unescape:\"htmlall\"}\n{/foreach}\nGesamtkosten Brutto: {$sAmount|currency|unescape:\"htmlall\"}\n{/if}\n\nGewählte Zahlungsart: {$additional.payment.description}\n{$additional.payment.additionaldescription}\n{if $additional.payment.name == \"debit\"}\nIhre Bankverbindung:\nKontonr: {$sPaymentTable.account}\nBLZ: {$sPaymentTable.bankcode}\nInstitut: {$sPaymentTable.bankname}\nKontoinhaber: {$sPaymentTable.bankholder}\n\nWir ziehen den Betrag in den nächsten Tagen von Ihrem Konto ein.\n{/if}\n{if $additional.payment.name == \"prepayment\"}\n\nUnsere Bankverbindung:\nKonto: ###\nBLZ: ###\n{/if}\n\n\nGewählte Versandart: {$sDispatch.name}\n{$sDispatch.description}\n\n{if $sComment}\nIhr Kommentar:\n{$sComment}\n{/if}\n\nRechnungsadresse:\n{$billingaddress.company}\n{$billingaddress.firstname} {$billingaddress.lastname}\n{$billingaddress.street} {$billingaddress.streetnumber}\n{if {config name=showZipBeforeCity}}{$billingaddress.zipcode} {$billingaddress.city}{else}{$billingaddress.city} {$billingaddress.zipcode}{/if}\n\n{$additional.country.countryname}\n\nLieferadresse:\n{$shippingaddress.company}\n{$shippingaddress.firstname} {$shippingaddress.lastname}\n{$shippingaddress.street} {$shippingaddress.streetnumber}\n{if {config name=showZipBeforeCity}}{$shippingaddress.zipcode} {$shippingaddress.city}{else}{$shippingaddress.city} {$shippingaddress.zipcode}{/if}\n\n{$additional.countryShipping.countryname}\n\n{if $billingaddress.ustid}\nIhre Umsatzsteuer-ID: {$billingaddress.ustid}\nBei erfolgreicher Prüfung und sofern Sie aus dem EU-Ausland\nbestellen, erhalten Sie Ihre Ware umsatzsteuerbefreit.\n{/if}\n\n\nFür Rückfragen stehen wir Ihnen jederzeit gerne zur Verfügung.\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n            {include file=\"string:{config name=emailheaderhtml}\"}\n            <br/><br/>\n            <p>Hallo {$billingaddress.firstname} {$billingaddress.lastname},<br/>\n                <br/>\n                vielen Dank für Ihre Bestellung bei {config name=shopName} (Nummer: {$sOrderNumber}) am {$sOrderDay} um {$sOrderTime}.<br/>\n                <br/>\n                <strong>Informationen zu Ihrer Bestellung:</strong></p><br/>\n            <table width=\"80%\" border=\"0\" style=\"font-family:Arial, Helvetica, sans-serif; font-size:12px;\">\n                <tr>\n                    <td bgcolor=\"#F7F7F2\" style=\"border-bottom:1px solid #cccccc;\"><strong>Pos.</strong></td>\n                    <td bgcolor=\"#F7F7F2\" style=\"border-bottom:1px solid #cccccc;\"><strong>Artikel</strong></td>\n                    <td bgcolor=\"#F7F7F2\" style=\"border-bottom:1px solid #cccccc;\">Bezeichnung</td>\n                    <td bgcolor=\"#F7F7F2\" style=\"border-bottom:1px solid #cccccc;\"><strong>Menge</strong></td>\n                    <td bgcolor=\"#F7F7F2\" style=\"border-bottom:1px solid #cccccc;\"><strong>Preis</strong></td>\n                    <td bgcolor=\"#F7F7F2\" style=\"border-bottom:1px solid #cccccc;\"><strong>Summe</strong></td>\n                </tr>\n\n                {foreach item=details key=position from=$sOrderDetails}\n                <tr>\n                    <td style=\"border-bottom:1px solid #cccccc;\">{$position+1|fill:4} </td>\n                    <td style=\"border-bottom:1px solid #cccccc;\">{if $details.image.src.0 && $details.modus == 0}<img style=\"height: 57px;\" height=\"57\" src=\"{$details.image.src.0}\" alt=\"{$details.articlename}\" />{else} {/if}</td>\n                    <td style=\"border-bottom:1px solid #cccccc;\">\n                      {$details.articlename|wordwrap:80|indent:4}<br>\n                      Artikel-Nr: {$details.ordernumber|fill:20}\n                    </td>\n                    <td style=\"border-bottom:1px solid #cccccc;\">{$details.quantity|fill:6}</td>\n                    <td style=\"border-bottom:1px solid #cccccc;\">{$details.price|padding:8|currency}</td>\n                    <td style=\"border-bottom:1px solid #cccccc;\">{$details.amount|padding:8|currency}</td>\n                </tr>\n                {/foreach}\n\n            </table>\n        \n            <p>\n                <br/>\n                <br/>\n                Versandkosten: {$sShippingCosts|currency}<br/>\n                Gesamtkosten Netto: {$sAmountNet|currency}<br/>\n                {if !$sNet}\n                {foreach $sTaxRates as $rate => $value}\n                zzgl. {$rate|number_format:0}% MwSt. {$value|currency}<br/>\n                {/foreach}\n                <strong>Gesamtkosten Brutto: {$sAmount|currency}</strong><br/>\n                {/if}\n                <br/>\n                <br/>\n                <strong>Gewählte Zahlungsart:</strong> {$additional.payment.description}<br/>\n                {$additional.payment.additionaldescription}\n                {if $additional.payment.name == \"debit\"}\n                Ihre Bankverbindung:<br/>\n                Kontonr: {$sPaymentTable.account}<br/>\n                BLZ: {$sPaymentTable.bankcode}<br/>\n                Institut: {$sPaymentTable.bankname}<br/>\n                Kontoinhaber: {$sPaymentTable.bankholder}<br/>\n                <br/>\n                Wir ziehen den Betrag in den nächsten Tagen von Ihrem Konto ein.<br/>\n                {/if}\n                <br/>\n                <br/>\n                {if $additional.payment.name == \"prepayment\"}\n                Unsere Bankverbindung:<br/>\n                Konto: ###<br/>\n                BLZ: ###<br/>\n                {/if}\n                <br/>\n                <br/>\n                <strong>Gewählte Versandart:</strong> {$sDispatch.name}<br/>\n                {$sDispatch.description}<br/>\n            </p>\n            <p>\n                {if $sComment}\n                <strong>Ihr Kommentar:</strong><br/>\n                {$sComment}<br/>\n                {/if}\n                <br/>\n                <br/>\n                <strong>Rechnungsadresse:</strong><br/>\n                {$billingaddress.company}<br/>\n                {$billingaddress.firstname} {$billingaddress.lastname}<br/>\n                {$billingaddress.street} {$billingaddress.streetnumber}<br/>\n                {if {config name=showZipBeforeCity}}{$billingaddress.zipcode} {$billingaddress.city}{else}{$billingaddress.city} {$billingaddress.zipcode}{/if}<br/>\n                {$additional.country.countryname}<br/>\n                <br/>\n                <br/>\n                <strong>Lieferadresse:</strong><br/>\n                {$shippingaddress.company}<br/>\n                {$shippingaddress.firstname} {$shippingaddress.lastname}<br/>\n                {$shippingaddress.street} {$shippingaddress.streetnumber}<br/>\n                {if {config name=showZipBeforeCity}}{$shippingaddress.zipcode} {$shippingaddress.city}{else}{$shippingaddress.city} {$shippingaddress.zipcode}{/if}<br/>\n                {$additional.countryShipping.countryname}<br/>\n                <br/>\n                {if $billingaddress.ustid}\n                Ihre Umsatzsteuer-ID: {$billingaddress.ustid}<br/>\n                Bei erfolgreicher Prüfung und sofern Sie aus dem EU-Ausland<br/>\n                bestellen, erhalten Sie Ihre Ware umsatzsteuerbefreit.<br/>\n                {/if}\n                <br/>\n                <br/>\n                Für Rückfragen stehen wir Ihnen jederzeit gerne zur Verfügung.<br/>\n                {include file=\"string:{config name=emailfooterhtml}\"}\n            </p>\n        </div>',	1,	'',	2,	'a:22:{s:13:\"sOrderDetails\";a:2:{i:0;a:54:{s:2:\"id\";s:3:\"670\";s:9:\"sessionID\";s:26:\"hkkhfl82i1jejfvd2f0ucr6om4\";s:6:\"userID\";s:1:\"0\";s:11:\"articlename\";s:11:\"ELASTIC CAP\";s:9:\"articleID\";s:3:\"152\";s:11:\"ordernumber\";s:7:\"SW10153\";s:12:\"shippingfree\";s:1:\"0\";s:8:\"quantity\";s:1:\"1\";s:5:\"price\";s:5:\"29,95\";s:8:\"netprice\";s:15:\"25.168067226891\";s:8:\"tax_rate\";s:2:\"19\";s:5:\"datum\";s:19:\"2017-08-07 14:09:12\";s:5:\"modus\";s:1:\"0\";s:10:\"esdarticle\";s:1:\"0\";s:9:\"partnerID\";s:0:\"\";s:12:\"lastviewport\";s:8:\"register\";s:9:\"useragent\";s:76:\"Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:54.0) Gecko/20100101 Firefox/54.0\";s:6:\"config\";s:0:\"\";s:14:\"currencyFactor\";s:1:\"1\";s:8:\"packunit\";s:0:\"\";s:12:\"mainDetailId\";s:3:\"707\";s:15:\"articleDetailId\";s:3:\"708\";s:11:\"minpurchase\";s:1:\"1\";s:5:\"taxID\";s:1:\"1\";s:7:\"instock\";s:2:\"12\";s:14:\"suppliernumber\";s:0:\"\";s:11:\"maxpurchase\";s:3:\"100\";s:13:\"purchasesteps\";i:1;s:12:\"purchaseunit\";N;s:9:\"laststock\";s:1:\"0\";s:12:\"shippingtime\";s:0:\"\";s:11:\"releasedate\";N;s:12:\"sReleaseDate\";N;s:3:\"ean\";s:0:\"\";s:8:\"stockmin\";s:1:\"0\";s:8:\"ob_attr1\";s:0:\"\";s:8:\"ob_attr2\";N;s:8:\"ob_attr3\";N;s:8:\"ob_attr4\";N;s:8:\"ob_attr5\";N;s:8:\"ob_attr6\";N;s:12:\"shippinginfo\";b:1;s:3:\"esd\";s:1:\"0\";s:18:\"additional_details\";a:94:{s:9:\"articleID\";i:152;s:16:\"articleDetailsID\";i:708;s:11:\"ordernumber\";s:9:\"SW10152.1\";s:9:\"highlight\";b:0;s:11:\"description\";s:0:\"\";s:16:\"description_long\";s:2404:\"<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo.</p><p>Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue.</p>  <p>Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. Donec vitae sapien ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt.</p> <p>Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc, quis gravida magna mi a libero. Fusce vulputate eleifend sapien. Vestibulum purus quam, scelerisque ut, mollis sed, nonummy id, metus. Nullam accumsan lorem in dui. Cras ultricies mi eu turpis hendrerit fringilla.</p> <p>Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; In ac dui quis mi consectetuer lacinia. Nam pretium turpis et arcu. Duis arcu tortor, suscipit eget, imperdiet nec, imperdiet iaculis, ipsum. Sed aliquam ultrices mauris. Integer ante arcu, accumsan a, consectetuer eget, posuere ut, mauris. Praesent adipiscing. Phasellus ullamcorper ipsum rutrum nunc. Nunc nonummy metus. Vestibulum volutpat pretium libero. Cras id dui. Aenean ut eros et nisl sagittis vestibulum. Nullam nulla eros, ultricies sit amet, nonummy id, imperdiet feugiat, pede. Sed lectus. Donec mollis hendrerit risus. Phasellus nec sem in justo pellentesque facilisis. Etiam imperdiet imperdiet orci. Nunc nec neque. Phasellus leo dolor, tempus non, auctor et, hendrerit quis, nisi.</p>\";s:3:\"esd\";b:0;s:11:\"articleName\";s:23:\"WINDSTOPPER MÜTZE WARM\";s:5:\"taxID\";i:1;s:3:\"tax\";i:19;s:7:\"instock\";i:12;s:11:\"isAvailable\";b:1;s:6:\"weight\";i:0;s:12:\"shippingtime\";N;s:16:\"pricegroupActive\";b:0;s:12:\"pricegroupID\";N;s:6:\"length\";i:0;s:6:\"height\";i:0;s:5:\"width\";i:0;s:9:\"laststock\";b:0;s:14:\"additionaltext\";s:0:\"\";s:5:\"datum\";s:10:\"2015-02-05\";s:5:\"sales\";i:0;s:13:\"filtergroupID\";i:8;s:17:\"priceStartingFrom\";N;s:18:\"pseudopricePercent\";N;s:15:\"sVariantArticle\";N;s:13:\"sConfigurator\";b:1;s:9:\"metaTitle\";s:0:\"\";s:12:\"shippingfree\";b:0;s:14:\"suppliernumber\";s:0:\"\";s:12:\"notification\";b:0;s:3:\"ean\";s:0:\"\";s:8:\"keywords\";s:0:\"\";s:12:\"sReleasedate\";s:0:\"\";s:8:\"template\";s:0:\"\";s:10:\"attributes\";a:2:{s:4:\"core\";a:23:{s:2:\"id\";s:3:\"720\";s:9:\"articleID\";s:3:\"152\";s:16:\"articledetailsID\";s:3:\"708\";s:5:\"attr1\";s:0:\"\";s:5:\"attr2\";s:0:\"\";s:5:\"attr3\";s:0:\"\";s:5:\"attr4\";s:0:\"\";s:5:\"attr5\";s:0:\"\";s:5:\"attr6\";s:0:\"\";s:5:\"attr7\";s:0:\"\";s:5:\"attr8\";s:0:\"\";s:5:\"attr9\";s:0:\"\";s:6:\"attr10\";s:0:\"\";s:6:\"attr11\";s:0:\"\";s:6:\"attr12\";s:0:\"\";s:6:\"attr13\";s:0:\"\";s:6:\"attr14\";s:0:\"\";s:6:\"attr15\";s:0:\"\";s:6:\"attr16\";s:0:\"\";s:6:\"attr17\";N;s:6:\"attr18\";s:0:\"\";s:6:\"attr19\";s:0:\"\";s:6:\"attr20\";s:0:\"\";}s:9:\"marketing\";a:4:{s:5:\"isNew\";b:0;s:11:\"isTopSeller\";b:0;s:10:\"comingSoon\";b:0;s:7:\"storage\";a:0:{}}}s:17:\"allowBuyInListing\";b:0;s:5:\"attr1\";s:0:\"\";s:5:\"attr2\";s:0:\"\";s:5:\"attr3\";s:0:\"\";s:5:\"attr4\";s:0:\"\";s:5:\"attr5\";s:0:\"\";s:5:\"attr6\";s:0:\"\";s:5:\"attr7\";s:0:\"\";s:5:\"attr8\";s:0:\"\";s:5:\"attr9\";s:0:\"\";s:6:\"attr10\";s:0:\"\";s:6:\"attr11\";s:0:\"\";s:6:\"attr12\";s:0:\"\";s:6:\"attr13\";s:0:\"\";s:6:\"attr14\";s:0:\"\";s:6:\"attr15\";s:0:\"\";s:6:\"attr16\";s:0:\"\";s:6:\"attr17\";N;s:6:\"attr18\";s:0:\"\";s:6:\"attr19\";s:0:\"\";s:6:\"attr20\";s:0:\"\";s:12:\"supplierName\";s:8:\"LÖFFLER\";s:11:\"supplierImg\";s:68:\"https://www.shopwaredemo.de/media/image/e3/46/f9/SW10153_200x200.jpg\";s:10:\"supplierID\";i:10;s:19:\"supplierDescription\";s:1267:\"<p>L&Ouml;FFLER ist anders. Denn anders als die meisten Mitbewerber hat sich L&Ouml;FFLER schon Anfang der 1990er Jahre entschieden, auch weiterhin in &Ouml;sterreich zu produzieren. Nat&uuml;rlich nach h&ouml;chsten ethischen und &ouml;kologischen Standards, wie sie nur in &Ouml;sterreich bzw. in der Europ&auml;ischen Union gelten. Mit gut ausgebildeten, kompetenten und motivierten Mitarbeiterinnen und Mitarbeitern.</p>  <p>Viele Sportswear-Konzerne haben im Streben nach h&ouml;chsten Gewinnmargen ihre Fertigung l&auml;ngst in Billiglohnl&auml;nder verlagert. Miserable Arbeitsbedingungen, Hungerl&ouml;hne und Kinderarbeit sind dort immer wieder an der Tagesordnung. H&ouml;chst fragw&uuml;rdig sind auch die Umweltzerst&ouml;rung durch r&uuml;cksichtslose Produktionsmethoden und die hohe Schadstoffbelastung der auf diese Weise hergestellten Textilien.</p> <p>70 Prozent aller Stoffe, die L&Ouml;FFLER verarbeitet, kommen aus der eigenen Strickerei in Ried im Innkreis. Das ist einzigartig - und eine wichtige Grundlage f&uuml;r die herausragende Qualit&auml;t, die Fair Sportswear von L&Ouml;FFLER auszeichnet.</p> <p>Weitere Informationen zu dem Hersteller finden Sie <a title=\"www.loeffler.at\" href=\"http://www.loeffler.at/\" target=\"_blank\">hier</a>.</p>\";s:19:\"supplier_attributes\";a:0:{}s:10:\"newArticle\";b:0;s:9:\"sUpcoming\";b:0;s:9:\"topseller\";b:0;s:7:\"valFrom\";i:1;s:5:\"valTo\";N;s:4:\"from\";i:1;s:2:\"to\";N;s:5:\"price\";s:5:\"29,95\";s:11:\"pseudoprice\";s:1:\"0\";s:14:\"referenceprice\";N;s:15:\"has_pseudoprice\";b:0;s:13:\"price_numeric\";d:29.949999999999999;s:19:\"pseudoprice_numeric\";i:0;s:16:\"price_attributes\";a:0:{}s:10:\"pricegroup\";s:2:\"EK\";s:11:\"minpurchase\";i:1;s:11:\"maxpurchase\";s:3:\"100\";s:13:\"purchasesteps\";i:1;s:12:\"purchaseunit\";N;s:13:\"referenceunit\";N;s:8:\"packunit\";s:0:\"\";s:6:\"unitID\";N;s:5:\"sUnit\";a:2:{s:4:\"unit\";N;s:11:\"description\";N;}s:15:\"unit_attributes\";a:0:{}s:5:\"image\";a:12:{s:2:\"id\";i:366;s:8:\"position\";N;s:6:\"source\";s:68:\"https://www.shopwaredemo.de/media/image/e3/46/f9/SW10153_200x200.jpg\";s:11:\"description\";s:0:\"\";s:9:\"extension\";s:3:\"jpg\";s:4:\"main\";b:0;s:8:\"parentId\";N;s:5:\"width\";i:1492;s:6:\"height\";i:1500;s:10:\"thumbnails\";a:3:{i:0;a:6:{s:6:\"source\";s:68:\"https://www.shopwaredemo.de/media/image/e3/46/f9/SW10153_200x200.jpg\";s:12:\"retinaSource\";s:68:\"https://www.shopwaredemo.de/media/image/e3/46/f9/SW10153_200x200.jpg\";s:9:\"sourceSet\";s:141:\"https://www.shopwaredemo.de/media/image/e3/46/f9/SW10153_200x200.jpg, https://www.shopwaredemo.de/media/image/e3/46/f9/SW10153_200x200.jpg 2x\";s:8:\"maxWidth\";s:3:\"200\";s:9:\"maxHeight\";s:3:\"200\";s:10:\"attributes\";a:0:{}}i:1;a:6:{s:6:\"source\";s:68:\"https://www.shopwaredemo.de/media/image/e3/46/f9/SW10153_200x200.jpg\";s:12:\"retinaSource\";s:68:\"https://www.shopwaredemo.de/media/image/e3/46/f9/SW10153_200x200.jpg\";s:9:\"sourceSet\";s:141:\"https://www.shopwaredemo.de/media/image/e3/46/f9/SW10153_200x200.jpg, https://www.shopwaredemo.de/media/image/e3/46/f9/SW10153_200x200.jpg 2x\";s:8:\"maxWidth\";s:3:\"600\";s:9:\"maxHeight\";s:3:\"600\";s:10:\"attributes\";a:0:{}}i:2;a:6:{s:6:\"source\";s:68:\"https://www.shopwaredemo.de/media/image/e3/46/f9/SW10153_200x200.jpg\";s:12:\"retinaSource\";s:68:\"https://www.shopwaredemo.de/media/image/e3/46/f9/SW10153_200x200.jpg\";s:9:\"sourceSet\";s:141:\"https://www.shopwaredemo.de/media/image/e3/46/f9/SW10153_200x200.jpg, https://www.shopwaredemo.de/media/image/e3/46/f9/SW10153_200x200.jpg 2x\";s:8:\"maxWidth\";s:4:\"1280\";s:9:\"maxHeight\";s:4:\"1280\";s:10:\"attributes\";a:0:{}}}s:10:\"attributes\";a:0:{}s:9:\"attribute\";a:0:{}}s:6:\"prices\";a:1:{i:0;a:22:{s:7:\"valFrom\";i:1;s:5:\"valTo\";N;s:4:\"from\";i:1;s:2:\"to\";N;s:5:\"price\";s:5:\"29,95\";s:11:\"pseudoprice\";s:1:\"0\";s:14:\"referenceprice\";s:1:\"0\";s:18:\"pseudopricePercent\";N;s:15:\"has_pseudoprice\";b:0;s:13:\"price_numeric\";d:29.949999999999999;s:19:\"pseudoprice_numeric\";i:0;s:16:\"price_attributes\";a:0:{}s:10:\"pricegroup\";s:2:\"EK\";s:11:\"minpurchase\";i:1;s:11:\"maxpurchase\";s:3:\"100\";s:13:\"purchasesteps\";i:1;s:12:\"purchaseunit\";N;s:13:\"referenceunit\";N;s:8:\"packunit\";s:0:\"\";s:6:\"unitID\";N;s:5:\"sUnit\";a:2:{s:4:\"unit\";N;s:11:\"description\";N;}s:15:\"unit_attributes\";a:0:{}}}s:10:\"linkBasket\";s:42:\"shopware.php?sViewport=basket&sAdd=SW10153\";s:11:\"linkDetails\";s:42:\"shopware.php?sViewport=detail&sArticle=152\";s:11:\"linkVariant\";s:57:\"shopware.php?sViewport=detail&sArticle=152&number=SW10153\";s:11:\"sProperties\";a:3:{i:1;a:11:{s:2:\"id\";i:1;s:8:\"optionID\";i:1;s:4:\"name\";s:10:\"Artikeltyp\";s:7:\"groupID\";i:8;s:9:\"groupName\";s:7:\"Fashion\";s:5:\"value\";s:16:\"Bildkonfigurator\";s:6:\"values\";a:1:{i:4;s:16:\"Bildkonfigurator\";}s:12:\"isFilterable\";b:1;s:7:\"options\";a:1:{i:0;a:3:{s:2:\"id\";i:4;s:4:\"name\";s:16:\"Bildkonfigurator\";s:10:\"attributes\";a:0:{}}}s:5:\"media\";a:0:{}s:10:\"attributes\";a:0:{}}i:3;a:11:{s:2:\"id\";i:3;s:8:\"optionID\";i:3;s:4:\"name\";s:8:\"Material\";s:7:\"groupID\";i:8;s:9:\"groupName\";s:7:\"Fashion\";s:5:\"value\";s:20:\"Polyester, Baumwolle\";s:6:\"values\";a:2:{i:108;s:9:\"Polyester\";i:163;s:9:\"Baumwolle\";}s:12:\"isFilterable\";b:1;s:7:\"options\";a:2:{i:0;a:3:{s:2:\"id\";i:108;s:4:\"name\";s:9:\"Polyester\";s:10:\"attributes\";a:0:{}}i:1;a:3:{s:2:\"id\";i:163;s:4:\"name\";s:9:\"Baumwolle\";s:10:\"attributes\";a:0:{}}}s:5:\"media\";a:0:{}s:10:\"attributes\";a:0:{}}i:18;a:11:{s:2:\"id\";i:18;s:8:\"optionID\";i:18;s:4:\"name\";s:5:\"Farbe\";s:7:\"groupID\";i:8;s:9:\"groupName\";s:7:\"Fashion\";s:5:\"value\";s:12:\"Rot, Schwarz\";s:6:\"values\";a:2:{i:166;s:3:\"Rot\";i:155;s:7:\"Schwarz\";}s:12:\"isFilterable\";b:1;s:7:\"options\";a:2:{i:0;a:3:{s:2:\"id\";i:166;s:4:\"name\";s:3:\"Rot\";s:10:\"attributes\";a:0:{}}i:1;a:3:{s:2:\"id\";i:155;s:4:\"name\";s:7:\"Schwarz\";s:10:\"attributes\";a:0:{}}}s:5:\"media\";a:2:{i:166;a:13:{s:7:\"valueId\";i:166;s:2:\"id\";i:355;s:8:\"position\";N;s:6:\"source\";s:68:\"https://www.shopwaredemo.de/media/image/e3/46/f9/SW10153_200x200.jpg\";s:11:\"description\";s:3:\"rot\";s:9:\"extension\";s:3:\"jpg\";s:4:\"main\";N;s:8:\"parentId\";N;s:5:\"width\";i:40;s:6:\"height\";i:40;s:10:\"thumbnails\";a:0:{}s:10:\"attributes\";a:0:{}s:9:\"attribute\";a:0:{}}i:155;a:13:{s:7:\"valueId\";i:155;s:2:\"id\";i:357;s:8:\"position\";N;s:6:\"source\";s:68:\"https://www.shopwaredemo.de/media/image/e3/46/f9/SW10153_200x200.jpg\";s:11:\"description\";s:7:\"schwarz\";s:9:\"extension\";s:3:\"jpg\";s:4:\"main\";N;s:8:\"parentId\";N;s:5:\"width\";i:40;s:6:\"height\";i:40;s:10:\"thumbnails\";a:0:{}s:10:\"attributes\";a:0:{}s:9:\"attribute\";a:0:{}}}s:10:\"attributes\";a:0:{}}}s:10:\"properties\";s:106:\"Artikeltyp:&nbsp;Bildkonfigurator,&nbsp;Material:&nbsp;Polyester, Baumwolle,&nbsp;Farbe:&nbsp;Rot, Schwarz\";}s:6:\"amount\";s:5:\"29,95\";s:9:\"amountnet\";s:5:\"25,17\";s:12:\"priceNumeric\";s:5:\"29.95\";s:5:\"image\";a:15:{s:2:\"id\";i:366;s:8:\"position\";N;s:6:\"source\";s:68:\"https://www.shopwaredemo.de/media/image/e3/46/f9/SW10153_200x200.jpg\";s:11:\"description\";s:0:\"\";s:9:\"extension\";s:3:\"jpg\";s:4:\"main\";b:0;s:8:\"parentId\";N;s:5:\"width\";i:1492;s:6:\"height\";i:1500;s:10:\"thumbnails\";a:3:{i:0;a:6:{s:6:\"source\";s:68:\"https://www.shopwaredemo.de/media/image/e3/46/f9/SW10153_200x200.jpg\";s:12:\"retinaSource\";s:68:\"https://www.shopwaredemo.de/media/image/e3/46/f9/SW10153_200x200.jpg\";s:9:\"sourceSet\";s:141:\"https://www.shopwaredemo.de/media/image/e3/46/f9/SW10153_200x200.jpg, https://www.shopwaredemo.de/media/image/e3/46/f9/SW10153_200x200.jpg 2x\";s:8:\"maxWidth\";s:3:\"200\";s:9:\"maxHeight\";s:3:\"200\";s:10:\"attributes\";a:0:{}}i:1;a:6:{s:6:\"source\";s:68:\"https://www.shopwaredemo.de/media/image/e3/46/f9/SW10153_200x200.jpg\";s:12:\"retinaSource\";s:68:\"https://www.shopwaredemo.de/media/image/e3/46/f9/SW10153_200x200.jpg\";s:9:\"sourceSet\";s:141:\"https://www.shopwaredemo.de/media/image/e3/46/f9/SW10153_200x200.jpg, https://www.shopwaredemo.de/media/image/e3/46/f9/SW10153_200x200.jpg 2x\";s:8:\"maxWidth\";s:3:\"600\";s:9:\"maxHeight\";s:3:\"600\";s:10:\"attributes\";a:0:{}}i:2;a:6:{s:6:\"source\";s:68:\"https://www.shopwaredemo.de/media/image/e3/46/f9/SW10153_200x200.jpg\";s:12:\"retinaSource\";s:68:\"https://www.shopwaredemo.de/media/image/e3/46/f9/SW10153_200x200.jpg\";s:9:\"sourceSet\";s:141:\"https://www.shopwaredemo.de/media/image/e3/46/f9/SW10153_200x200.jpg, https://www.shopwaredemo.de/media/image/e3/46/f9/SW10153_200x200.jpg 2x\";s:8:\"maxWidth\";s:4:\"1280\";s:9:\"maxHeight\";s:4:\"1280\";s:10:\"attributes\";a:0:{}}}s:10:\"attributes\";a:0:{}s:9:\"attribute\";a:0:{}s:3:\"src\";a:4:{s:8:\"original\";s:68:\"https://www.shopwaredemo.de/media/image/e3/46/f9/SW10153_200x200.jpg\";i:0;s:68:\"https://www.shopwaredemo.de/media/image/e3/46/f9/SW10153_200x200.jpg\";i:1;s:68:\"https://www.shopwaredemo.de/media/image/e3/46/f9/SW10153_200x200.jpg\";i:2;s:68:\"https://www.shopwaredemo.de/media/image/e3/46/f9/SW10153_200x200.jpg\";}s:5:\"srchd\";a:4:{s:8:\"original\";s:68:\"https://www.shopwaredemo.de/media/image/e3/46/f9/SW10153_200x200.jpg\";i:0;s:68:\"https://www.shopwaredemo.de/media/image/e3/46/f9/SW10153_200x200.jpg\";i:1;s:68:\"https://www.shopwaredemo.de/media/image/e3/46/f9/SW10153_200x200.jpg\";i:2;s:68:\"https://www.shopwaredemo.de/media/image/e3/46/f9/SW10153_200x200.jpg\";}s:3:\"res\";a:1:{s:8:\"original\";a:2:{s:5:\"width\";i:1500;s:6:\"height\";i:1492;}}}s:11:\"linkDetails\";s:42:\"shopware.php?sViewport=detail&sArticle=152\";s:10:\"linkDelete\";s:41:\"shopware.php?sViewport=basket&sDelete=670\";s:8:\"linkNote\";s:40:\"shopware.php?sViewport=note&sAdd=SW10153\";s:3:\"tax\";s:4:\"4,78\";s:13:\"orderDetailId\";s:3:\"208\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";s:0:\"\";s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}i:1;a:52:{s:2:\"id\";s:3:\"673\";s:9:\"sessionID\";s:26:\"hkkhfl82i1jejfvd2f0ucr6om4\";s:6:\"userID\";s:1:\"0\";s:11:\"articlename\";s:15:\"Warenkorbrabatt\";s:9:\"articleID\";s:1:\"0\";s:11:\"ordernumber\";s:16:\"SHIPPINGDISCOUNT\";s:12:\"shippingfree\";s:1:\"0\";s:8:\"quantity\";s:1:\"1\";s:5:\"price\";s:5:\"-2,00\";s:8:\"netprice\";s:5:\"-1.68\";s:8:\"tax_rate\";s:2:\"19\";s:5:\"datum\";s:19:\"2017-08-07 14:09:20\";s:5:\"modus\";s:1:\"4\";s:10:\"esdarticle\";s:1:\"0\";s:9:\"partnerID\";s:0:\"\";s:12:\"lastviewport\";s:0:\"\";s:9:\"useragent\";s:0:\"\";s:6:\"config\";s:0:\"\";s:14:\"currencyFactor\";s:1:\"1\";s:8:\"packunit\";N;s:12:\"mainDetailId\";N;s:15:\"articleDetailId\";N;s:11:\"minpurchase\";i:1;s:5:\"taxID\";N;s:7:\"instock\";N;s:14:\"suppliernumber\";N;s:11:\"maxpurchase\";s:3:\"100\";s:13:\"purchasesteps\";i:1;s:12:\"purchaseunit\";N;s:9:\"laststock\";N;s:12:\"shippingtime\";N;s:11:\"releasedate\";N;s:12:\"sReleaseDate\";N;s:3:\"ean\";N;s:8:\"stockmin\";N;s:8:\"ob_attr1\";N;s:8:\"ob_attr2\";N;s:8:\"ob_attr3\";N;s:8:\"ob_attr4\";N;s:8:\"ob_attr5\";N;s:8:\"ob_attr6\";N;s:12:\"shippinginfo\";b:0;s:3:\"esd\";s:1:\"0\";s:6:\"amount\";s:5:\"-2,00\";s:9:\"amountnet\";s:5:\"-1,68\";s:12:\"priceNumeric\";s:2:\"-2\";s:11:\"linkDetails\";s:40:\"shopware.php?sViewport=detail&sArticle=0\";s:10:\"linkDelete\";s:41:\"shopware.php?sViewport=basket&sDelete=673\";s:8:\"linkNote\";s:49:\"shopware.php?sViewport=note&sAdd=SHIPPINGDISCOUNT\";s:3:\"tax\";s:5:\"-0,32\";s:13:\"orderDetailId\";s:3:\"209\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}}s:14:\"billingaddress\";a:26:{s:2:\"id\";s:1:\"5\";s:7:\"company\";s:0:\"\";s:10:\"department\";s:0:\"\";s:10:\"salutation\";s:2:\"mr\";s:9:\"firstname\";s:3:\"Max\";s:5:\"title\";s:0:\"\";s:8:\"lastname\";s:10:\"Mustermann\";s:6:\"street\";s:15:\"Musterstraße 1\";s:7:\"zipcode\";s:5:\"12345\";s:4:\"city\";s:11:\"Musterstadt\";s:5:\"phone\";s:0:\"\";s:5:\"vatId\";s:0:\"\";s:22:\"additionalAddressLine1\";s:0:\"\";s:22:\"additionalAddressLine2\";s:0:\"\";s:9:\"countryId\";s:1:\"2\";s:7:\"stateId\";s:0:\"\";s:8:\"customer\";N;s:7:\"country\";N;s:5:\"state\";s:0:\"\";s:6:\"userID\";s:1:\"3\";s:9:\"countryID\";s:1:\"2\";s:7:\"stateID\";s:0:\"\";s:5:\"ustid\";s:0:\"\";s:24:\"additional_address_line1\";s:0:\"\";s:24:\"additional_address_line2\";s:0:\"\";s:10:\"attributes\";N;}s:15:\"shippingaddress\";a:26:{s:2:\"id\";s:1:\"5\";s:7:\"company\";s:0:\"\";s:10:\"department\";s:0:\"\";s:10:\"salutation\";s:2:\"mr\";s:9:\"firstname\";s:3:\"Max\";s:5:\"title\";s:0:\"\";s:8:\"lastname\";s:10:\"Mustermann\";s:6:\"street\";s:15:\"Musterstraße 1\";s:7:\"zipcode\";s:5:\"12345\";s:4:\"city\";s:11:\"Musterstadt\";s:5:\"phone\";s:0:\"\";s:5:\"vatId\";s:0:\"\";s:22:\"additionalAddressLine1\";s:0:\"\";s:22:\"additionalAddressLine2\";s:0:\"\";s:9:\"countryId\";s:1:\"2\";s:7:\"stateId\";s:0:\"\";s:8:\"customer\";N;s:7:\"country\";N;s:5:\"state\";s:0:\"\";s:6:\"userID\";s:1:\"3\";s:9:\"countryID\";s:1:\"2\";s:7:\"stateID\";s:0:\"\";s:5:\"ustid\";s:0:\"\";s:24:\"additional_address_line1\";s:0:\"\";s:24:\"additional_address_line2\";s:0:\"\";s:10:\"attributes\";N;}s:10:\"additional\";a:8:{s:7:\"country\";a:15:{s:2:\"id\";s:1:\"2\";s:11:\"countryname\";s:11:\"Deutschland\";s:10:\"countryiso\";s:2:\"DE\";s:6:\"areaID\";s:1:\"1\";s:9:\"countryen\";s:7:\"GERMANY\";s:8:\"position\";s:1:\"1\";s:6:\"notice\";s:0:\"\";s:7:\"taxfree\";s:1:\"0\";s:13:\"taxfree_ustid\";s:1:\"0\";s:21:\"taxfree_ustid_checked\";s:1:\"0\";s:6:\"active\";s:1:\"1\";s:4:\"iso3\";s:3:\"DEU\";s:29:\"display_state_in_registration\";s:1:\"0\";s:27:\"force_state_in_registration\";s:1:\"0\";s:11:\"countryarea\";s:11:\"deutschland\";}s:5:\"state\";a:0:{}s:4:\"user\";a:33:{s:2:\"id\";s:1:\"3\";s:6:\"userID\";s:1:\"3\";s:8:\"password\";s:60:\"$2y$10$AzjwzOob83DJ2LG6yxXcBeghK9ciBB1zsK3UeBZADZCl10pTQN62W\";s:7:\"encoder\";s:6:\"bcrypt\";s:5:\"email\";s:14:\"xy@example.org\";s:6:\"active\";s:1:\"1\";s:11:\"accountmode\";s:1:\"0\";s:15:\"confirmationkey\";s:0:\"\";s:9:\"paymentID\";s:1:\"5\";s:10:\"firstlogin\";s:10:\"2017-08-07\";s:9:\"lastlogin\";s:19:\"2017-08-07 14:09:20\";s:9:\"sessionID\";s:26:\"hkkhfl82i1jejfvd2f0ucr6om4\";s:10:\"newsletter\";i:0;s:10:\"validation\";s:0:\"\";s:9:\"affiliate\";s:1:\"0\";s:13:\"customergroup\";s:2:\"EK\";s:13:\"paymentpreset\";s:1:\"0\";s:8:\"language\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:7:\"referer\";s:0:\"\";s:12:\"pricegroupID\";N;s:15:\"internalcomment\";s:0:\"\";s:12:\"failedlogins\";s:1:\"0\";s:11:\"lockeduntil\";N;s:26:\"default_billing_address_id\";s:1:\"5\";s:27:\"default_shipping_address_id\";s:1:\"5\";s:5:\"title\";N;s:10:\"salutation\";s:2:\"mr\";s:9:\"firstname\";s:3:\"Max\";s:8:\"lastname\";s:10:\"Mustermann\";s:8:\"birthday\";N;s:14:\"customernumber\";s:5:\"20005\";s:11:\"login_token\";s:38:\"1239c089-6b2f-4461-9134-c02026970bff.1\";}s:15:\"countryShipping\";a:15:{s:2:\"id\";s:1:\"2\";s:11:\"countryname\";s:11:\"Deutschland\";s:10:\"countryiso\";s:2:\"DE\";s:6:\"areaID\";s:1:\"1\";s:9:\"countryen\";s:7:\"GERMANY\";s:8:\"position\";s:1:\"1\";s:6:\"notice\";s:0:\"\";s:7:\"taxfree\";s:1:\"0\";s:13:\"taxfree_ustid\";s:1:\"0\";s:21:\"taxfree_ustid_checked\";s:1:\"0\";s:6:\"active\";s:1:\"1\";s:4:\"iso3\";s:3:\"DEU\";s:29:\"display_state_in_registration\";s:1:\"0\";s:27:\"force_state_in_registration\";s:1:\"0\";s:11:\"countryarea\";s:11:\"deutschland\";}s:13:\"stateShipping\";a:0:{}s:7:\"payment\";a:21:{s:2:\"id\";s:1:\"5\";s:4:\"name\";s:10:\"prepayment\";s:11:\"description\";s:8:\"Vorkasse\";s:8:\"template\";s:14:\"prepayment.tpl\";s:5:\"class\";s:14:\"prepayment.php\";s:5:\"table\";s:0:\"\";s:4:\"hide\";s:1:\"0\";s:21:\"additionaldescription\";s:108:\"Sie zahlen einfach vorab und erhalten die Ware bequem und günstig bei Zahlungseingang nach Hause geliefert.\";s:13:\"debit_percent\";s:1:\"0\";s:9:\"surcharge\";s:1:\"0\";s:15:\"surchargestring\";s:0:\"\";s:8:\"position\";s:1:\"1\";s:6:\"active\";s:1:\"1\";s:9:\"esdactive\";s:1:\"0\";s:11:\"embediframe\";s:0:\"\";s:12:\"hideprospect\";s:1:\"0\";s:6:\"action\";N;s:8:\"pluginID\";N;s:6:\"source\";N;s:15:\"mobile_inactive\";s:1:\"0\";s:10:\"validation\";a:0:{}}s:10:\"charge_vat\";b:1;s:8:\"show_net\";b:1;}s:9:\"sTaxRates\";a:1:{s:5:\"19.00\";d:5.0800000000000001;}s:14:\"sShippingCosts\";s:8:\"3,90 EUR\";s:7:\"sAmount\";s:9:\"31,85 EUR\";s:14:\"sAmountNumeric\";d:31.850000000000001;s:10:\"sAmountNet\";s:9:\"26,77 EUR\";s:17:\"sAmountNetNumeric\";d:26.77;s:12:\"sOrderNumber\";i:20003;s:9:\"sOrderDay\";s:10:\"07.08.2017\";s:10:\"sOrderTime\";s:5:\"14:09\";s:8:\"sComment\";s:0:\"\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}s:9:\"sCurrency\";s:3:\"EUR\";s:9:\"sLanguage\";i:1;s:8:\"sSubShop\";i:1;s:4:\"sEsd\";N;s:4:\"sNet\";b:0;s:13:\"sPaymentTable\";a:0:{}s:9:\"sDispatch\";a:10:{s:2:\"id\";s:1:\"9\";s:4:\"name\";s:16:\"Standard Versand\";s:11:\"description\";s:0:\"\";s:11:\"calculation\";s:1:\"1\";s:11:\"status_link\";s:0:\"\";s:21:\"surcharge_calculation\";s:1:\"3\";s:17:\"bind_shippingfree\";s:1:\"0\";s:12:\"shippingfree\";N;s:15:\"tax_calculation\";s:1:\"0\";s:21:\"tax_calculation_value\";N;}}',	0),
(3,	NULL,	'sTELLAFRIEND',	'{config name=mail}',	'{config name=shopName}',	'{$sName} empfiehlt Ihnen {$sArticle}',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo,\n\n{$sName} hat für Sie bei {$sShop} ein interessantes Produkt gefunden, das Sie sich anschauen sollten:\n\n{$sArticle}\n{$sLink}\n\n{$sComment}\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n        Hallo,<br/>\n        <br/>\n		{$sName} hat für Sie bei {$sShop} ein interessantes Produkt gefunden, das Sie sich anschauen sollten:<br/>\n        <br/>\n        <strong><a href=\"{$sLink}\">{$sArticle}</a></strong><br/>\n    </p>\n    {if $sComment}\n        <div style=\"border: 2px solid black; border-radius: 5px; padding: 5px;\"><p>{$sComment}</p></div><br/>\n    {/if}\n    \n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	2,	'a:4:{s:5:\"sName\";s:11:\"Peter Meyer\";s:8:\"sArticle\";s:10:\"Blumenvase\";s:5:\"sLink\";s:31:\"http://shopware.example/test123\";s:8:\"sComment\";s:36:\"Hey Peter - das musst du dir ansehen\";}',	0),
(5,	NULL,	'sNOSERIALS',	'{config name=mail}',	'{config name=shopName}',	'Achtung - keine freien Seriennummern für {$sArticleName}',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo,\n\nes sind keine weiteren freien Seriennummern für den Artikel\n\n{$sArticleName}\n\nverfügbar. Bitte stelle umgehend neue Seriennummern ein oder deaktiviere den Artikel.\nAußerdem weise dem Kunden {$sMail} bitte manuell eine Seriennummer zu.\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n        Hallo,<br/>\n        <br/>\n        es sind keine weiteren freien Seriennummern für den Artikel<br/>\n    </p>\n    <strong>{$sArticleName}</strong><br/>\n    <p>\n        verfügbar. Bitte stelle umgehend neue Seriennummern ein oder deaktiviere den Artikel.<br/>\n        Außerdem weise dem Kunden {$sMail} bitte manuell eine Seriennummer zu.\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	2,	'a:2:{s:12:\"sArticleName\";s:20:\"ESD Download Artikel\";s:5:\"sMail\";s:23:\"max.mustermann@mail.com\";}',	0),
(7,	NULL,	'sVOUCHER',	'{config name=mail}',	'{config name=shopName}',	'Ihr Gutschein',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo {$customer},\n\n{$user} ist Ihrer Empfehlung gefolgt und hat soeben bei {$sShop} bestellt.\nWir schenken Ihnen deshalb einen X € Gutschein, den Sie bei Ihrer nächsten Bestellung einlösen können.\n\nIhr Gutschein-Code lautet: XXX\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n        Hallo {$customer},<br/>\n        <br/>\n        {$user} ist Ihrer Empfehlung gefolgt und hat soeben bei {$sShop} bestellt.<br/>\n        Wir schenken Ihnen deshalb einen X € Gutschein, den Sie bei Ihrer nächsten Bestellung einlösen können.<br/>\n        <br/>\n        <strong>Ihr Gutschein-Code lautet: XXX</strong>\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	2,	'a:2:{s:8:\"customer\";s:11:\"Peter Meyer\";s:4:\"user\";s:11:\"Hans Maiser\";}',	0),
(12,	NULL,	'sCUSTOMERGROUPHACCEPTED',	'{config name=mail}',	'{config name=shopName}',	'Ihr Händleraccount wurde freigeschaltet',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo,\n\nIhr Händleraccount bei {$sShop} wurde freigeschaltet.\nAb sofort kaufen Sie zum Netto-EK bei uns ein.\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n        Hallo,<br/>\n        <br/>\n        Ihr Händleraccount bei {$sShop} wurde freigeschaltet.<br/>\n        Ab sofort kaufen Sie zum Netto-EK bei uns ein.\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	2,	NULL,	0),
(13,	NULL,	'sCUSTOMERGROUPHREJECTED',	'{config name=mail}',	'{config name=shopName}',	'Ihr Händleraccount wurde abgelehnt',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo,\n\nvielen Dank für Ihr Interesse an unseren Fachhandelspreisen. Leider liegt uns aber noch kein Gewerbenachweis vor bzw. leider können wir Sie nicht als Fachhändler anerkennen.\nBei Rückfragen aller Art können Sie uns gerne telefonisch, per Fax oder per Mail diesbezüglich erreichen.\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n        Hallo,<br/>\n		<br/>\n        vielen Dank für Ihr Interesse an unseren Fachhandelspreisen. Leider liegt uns aber noch kein Gewerbenachweis vor bzw. leider können wir Sie nicht als Fachhändler anerkennen.<br/>\n        Bei Rückfragen aller Art können Sie uns gerne telefonisch, per Fax oder per Mail diesbezüglich erreichen.\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	2,	NULL,	0),
(19,	NULL,	'sCANCELEDQUESTION',	'{config name=mail}',	'{config name=shopName}',	'Ihre abgebrochene Bestellung - Jetzt Feedback geben und Gutschein kassieren',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo,\n \nSie haben vor kurzem Ihre Bestellung auf {$sShop} nicht bis zum Ende durchgeführt - wir sind stets bemüht unseren Kunden das Einkaufen in unserem Shop so angenehm wie möglich zu machen und würden deshalb gerne wissen, woran Ihr Einkauf bei uns gescheitert ist. Bitte lassen Sie uns doch den Grund für Ihren Bestellabbruch zukommen, Ihren Aufwand entschädigen wir Ihnen in jedem Fall mit einem 5,00 € Gutschein.\nVielen Dank für Ihre Unterstützung.\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n        Hallo,<br/>\n        <br/>\n        Sie haben vor kurzem Ihre Bestellung auf {$sShop} nicht bis zum Ende durchgeführt - wir sind stets bemüht unseren Kunden das Einkaufen in unserem Shop so angenehm wie möglich zu machen und würden deshalb gerne wissen, woran Ihr Einkauf bei uns gescheitert ist. Bitte lassen Sie uns doch den Grund für Ihren Bestellabbruch zukommen, Ihren Aufwand entschädigen wir Ihnen in jedem Fall mit einem 5,00 € Gutschein.<br/>\n        Vielen Dank für Ihre Unterstützung.\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	2,	NULL,	0),
(20,	NULL,	'sCANCELEDVOUCHER',	'{config name=mail}',	'{config name=shopName}',	'Ihre abgebrochene Bestellung - Gutschein-Code anbei',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo,\n \nSie haben vor kurzem Ihre Bestellung bei {$sShop} nicht bis zum Ende durchgeführt - wir möchten Ihnen heute einen {if $sVoucherpercental == \"1\"}{$sVouchervalue} %{else}{$sVouchervalue|currency|unescape:\"htmlall\"}{/if} Gutschein zukommen lassen - und Ihnen hiermit die Bestell-Entscheidung bei {$sShop} erleichtern. Ihr Gutschein ist 2 Monate gültig und kann mit dem Code \"{$sVouchercode}\" eingelöst werden. Wir würden uns freuen, Ihre Bestellung entgegen nehmen zu dürfen.\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n         Hallo,<br/>\n         <br/>\n         Sie haben vor kurzem Ihre Bestellung bei {$sShop} nicht bis zum Ende durchgeführt - wir möchten Ihnen heute einen {if $sVoucherpercental == \"1\"}{$sVouchervalue} %{else}{$sVouchervalue|currency}{/if} Gutschein zukommen lassen - und Ihnen hiermit die Bestell-Entscheidung bei {$sShop} erleichtern. Ihr Gutschein ist 2 Monate gültig und kann mit dem Code \"<strong>{$sVouchercode}</strong>\" eingelöst werden. Wir würden uns freuen, Ihre Bestellung entgegen nehmen zu dürfen.\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	2,	'a:5:{s:12:\"sVouchercode\";s:8:\"23A7BCA4\";s:13:\"sVouchervalue\";i:15;s:15:\"sVouchervalidto\";N;s:17:\"sVouchervalidfrom\";N;s:17:\"sVoucherpercental\";i:0;}',	0),
(21,	9,	'sORDERSTATEMAIL9',	'{config name=mail}',	'{config name=shopName}',	'Statusänderung zur Bestellung bei {config name=shopName}',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo {$sUser.billing_firstname} {$sUser.billing_lastname},\n\nder Zahlungsstatus für Ihre Bestellung {$sOrder.ordernumber} vom {$sOrder.ordertime|date_format:\"%d.%m.%Y\"} hat sich geändert!\nDie Bestellung hat jetzt den Zahlungsstatus: {$sOrder.cleared_description}.\n\nDen aktuellen Status Ihrer Bestellung können Sie auch jederzeit auf unserer Webseite im  Bereich \"Mein Konto\" - \"Meine Bestellungen\" abrufen. Sollten Sie allerdings den Kauf ohne Registrierung, also ohne Anlage eines Kundenkontos, gewählt haben, steht Ihnen diese Möglichkeit nicht zur Verfügung.\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n        Hallo {$sUser.billing_firstname} {$sUser.billing_lastname},<br/>\n        <br/>\n        der Zahlungsstatus für Ihre Bestellung {$sOrder.ordernumber} vom {$sOrder.ordertime|date_format:\"%d.%m.%Y\"} hat sich geändert!<br/>\n        <strong>Die Bestellung hat jetzt den Zahlungsstatus: {$sOrder.cleared_description}.</strong><br/>\n        <br/>\n        Den aktuellen Status Ihrer Bestellung können Sie auch jederzeit auf unserer Webseite im  Bereich \"Mein Konto\" - \"Meine Bestellungen\" abrufen. Sollten Sie allerdings den Kauf ohne Registrierung, also ohne Anlage eines Kundenkontos, gewählt haben, steht Ihnen diese Möglichkeit nicht zur Verfügung.\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	3,	'a:4:{s:6:\"sOrder\";a:40:{s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:12:\"order_number\";s:5:\"20003\";s:6:\"userID\";s:1:\"3\";s:10:\"customerID\";s:1:\"3\";s:14:\"invoice_amount\";s:5:\"31.85\";s:18:\"invoice_amount_net\";s:5:\"26.77\";s:16:\"invoice_shipping\";s:3:\"3.9\";s:20:\"invoice_shipping_net\";s:4:\"3.28\";s:9:\"ordertime\";s:19:\"2017-08-07 14:09:26\";s:6:\"status\";s:1:\"8\";s:8:\"statusID\";s:1:\"8\";s:7:\"cleared\";s:1:\"9\";s:9:\"clearedID\";s:1:\"9\";s:9:\"paymentID\";s:1:\"5\";s:13:\"transactionID\";s:0:\"\";s:7:\"comment\";s:0:\"\";s:15:\"customercomment\";s:0:\"\";s:3:\"net\";s:1:\"0\";s:5:\"netto\";s:1:\"0\";s:9:\"partnerID\";s:0:\"\";s:11:\"temporaryID\";s:0:\"\";s:7:\"referer\";s:0:\"\";s:11:\"cleareddate\";N;s:12:\"cleared_date\";N;s:12:\"trackingcode\";s:0:\"\";s:8:\"language\";s:1:\"1\";s:8:\"currency\";s:3:\"EUR\";s:14:\"currencyFactor\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:10:\"dispatchID\";s:1:\"9\";s:10:\"currencyID\";s:1:\"1\";s:12:\"cleared_name\";s:18:\"partially_invoiced\";s:19:\"cleared_description\";s:30:\"Teilweise in Rechnung gestellt\";s:11:\"status_name\";s:22:\"clarification_required\";s:18:\"status_description\";s:18:\"Klärung notwendig\";s:19:\"payment_description\";s:8:\"Vorkasse\";s:20:\"dispatch_description\";s:16:\"Standard Versand\";s:20:\"currency_description\";s:4:\"Euro\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}s:13:\"sOrderDetails\";a:2:{i:0;a:20:{s:14:\"orderdetailsID\";s:3:\"208\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:3:\"152\";s:18:\"articleordernumber\";s:9:\"SW10152.1\";s:5:\"price\";s:5:\"29.95\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:5:\"29.95\";s:4:\"name\";s:31:\"WINDSTOPPER MÜTZE WARM Schwarz\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"0\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"1\";s:3:\"tax\";s:5:\"19.00\";s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"0\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";s:0:\"\";s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}i:1;a:20:{s:14:\"orderdetailsID\";s:3:\"209\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:1:\"0\";s:18:\"articleordernumber\";s:16:\"SHIPPINGDISCOUNT\";s:5:\"price\";s:2:\"-2\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:2:\"-2\";s:4:\"name\";s:15:\"Warenkorbrabatt\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"4\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"0\";s:3:\"tax\";N;s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"0\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}}s:5:\"sUser\";a:82:{s:15:\"billing_company\";s:0:\"\";s:18:\"billing_department\";s:0:\"\";s:18:\"billing_salutation\";s:2:\"mr\";s:14:\"customernumber\";s:5:\"20005\";s:17:\"billing_firstname\";s:3:\"Max\";s:16:\"billing_lastname\";s:10:\"Mustermann\";s:14:\"billing_street\";s:15:\"Musterstraße 1\";s:32:\"billing_additional_address_line1\";s:0:\"\";s:32:\"billing_additional_address_line2\";s:0:\"\";s:15:\"billing_zipcode\";s:5:\"12345\";s:12:\"billing_city\";s:11:\"Musterstadt\";s:5:\"phone\";s:0:\"\";s:13:\"billing_phone\";s:0:\"\";s:17:\"billing_countryID\";s:1:\"2\";s:15:\"billing_stateID\";N;s:15:\"billing_country\";s:11:\"Deutschland\";s:18:\"billing_countryiso\";s:2:\"DE\";s:19:\"billing_countryarea\";s:11:\"deutschland\";s:17:\"billing_countryen\";s:7:\"GERMANY\";s:5:\"ustid\";s:0:\"\";s:13:\"billing_text1\";N;s:13:\"billing_text2\";N;s:13:\"billing_text3\";N;s:13:\"billing_text4\";N;s:13:\"billing_text5\";N;s:13:\"billing_text6\";N;s:7:\"orderID\";s:2:\"59\";s:16:\"shipping_company\";s:0:\"\";s:19:\"shipping_department\";s:0:\"\";s:19:\"shipping_salutation\";s:2:\"mr\";s:18:\"shipping_firstname\";s:3:\"Max\";s:17:\"shipping_lastname\";s:10:\"Mustermann\";s:15:\"shipping_street\";s:15:\"Musterstraße 1\";s:33:\"shipping_additional_address_line1\";s:0:\"\";s:33:\"shipping_additional_address_line2\";s:0:\"\";s:16:\"shipping_zipcode\";s:5:\"12345\";s:13:\"shipping_city\";s:11:\"Musterstadt\";s:16:\"shipping_stateID\";N;s:18:\"shipping_countryID\";s:1:\"2\";s:16:\"shipping_country\";s:11:\"Deutschland\";s:19:\"shipping_countryiso\";s:2:\"DE\";s:20:\"shipping_countryarea\";s:11:\"deutschland\";s:18:\"shipping_countryen\";s:7:\"GERMANY\";s:14:\"shipping_text1\";N;s:14:\"shipping_text2\";N;s:14:\"shipping_text3\";N;s:14:\"shipping_text4\";N;s:14:\"shipping_text5\";N;s:14:\"shipping_text6\";N;s:2:\"id\";s:1:\"3\";s:8:\"password\";s:60:\"$2y$10$AzjwzOob83DJ2LG6yxXcBeghK9ciBB1zsK3UeBZADZCl10pTQN62W\";s:7:\"encoder\";s:6:\"bcrypt\";s:5:\"email\";s:14:\"xy@example.org\";s:6:\"active\";s:1:\"1\";s:11:\"accountmode\";s:1:\"0\";s:15:\"confirmationkey\";s:0:\"\";s:9:\"paymentID\";s:1:\"5\";s:10:\"firstlogin\";s:10:\"2017-08-07\";s:9:\"lastlogin\";s:19:\"2017-08-07 14:09:26\";s:9:\"sessionID\";s:26:\"hkkhfl82i1jejfvd2f0ucr6om4\";s:10:\"newsletter\";s:1:\"0\";s:10:\"validation\";s:0:\"\";s:9:\"affiliate\";s:1:\"0\";s:13:\"customergroup\";s:2:\"EK\";s:13:\"paymentpreset\";s:1:\"0\";s:8:\"language\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:7:\"referer\";s:0:\"\";s:12:\"pricegroupID\";N;s:15:\"internalcomment\";s:0:\"\";s:12:\"failedlogins\";s:1:\"0\";s:11:\"lockeduntil\";N;s:26:\"default_billing_address_id\";s:1:\"5\";s:27:\"default_shipping_address_id\";s:1:\"5\";s:5:\"title\";N;s:10:\"salutation\";s:2:\"mr\";s:9:\"firstname\";s:3:\"Max\";s:8:\"lastname\";s:10:\"Mustermann\";s:8:\"birthday\";N;s:11:\"login_token\";s:38:\"1239c089-6b2f-4461-9134-c02026970bff.1\";s:11:\"preisgruppe\";s:1:\"1\";s:11:\"billing_net\";s:1:\"1\";}s:9:\"sDispatch\";a:2:{s:4:\"name\";s:16:\"Standard Versand\";s:11:\"description\";s:0:\"\";}}',	0),
(22,	10,	'sORDERSTATEMAIL10',	'{config name=mail}',	'{config name=shopName}',	'Statusänderung zur Bestellung bei {config name=shopName}',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo {$sUser.billing_firstname} {$sUser.billing_lastname},\n\nder Zahlungsstatus für Ihre Bestellung {$sOrder.ordernumber} vom {$sOrder.ordertime|date_format:\"%d.%m.%Y\"} hat sich geändert!\nDie Bestellung hat jetzt den Zahlungsstatus: {$sOrder.cleared_description}.\n\nDen aktuellen Status Ihrer Bestellung können Sie auch jederzeit auf unserer Webseite im  Bereich \"Mein Konto\" - \"Meine Bestellungen\" abrufen. Sollten Sie allerdings den Kauf ohne Registrierung, also ohne Anlage eines Kundenkontos, gewählt haben, steht Ihnen diese Möglichkeit nicht zur Verfügung.\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n        Hallo {$sUser.billing_firstname} {$sUser.billing_lastname},<br/>\n        <br/>\n        der Zahlungsstatus für Ihre Bestellung {$sOrder.ordernumber} vom {$sOrder.ordertime|date_format:\"%d.%m.%Y\"} hat sich geändert!<br/>\n        <strong>Die Bestellung hat jetzt den Zahlungsstatus: {$sOrder.cleared_description}.</strong><br/>\n        <br/>\n        Den aktuellen Status Ihrer Bestellung können Sie auch jederzeit auf unserer Webseite im  Bereich \"Mein Konto\" - \"Meine Bestellungen\" abrufen. Sollten Sie allerdings den Kauf ohne Registrierung, also ohne Anlage eines Kundenkontos, gewählt haben, steht Ihnen diese Möglichkeit nicht zur Verfügung.\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	3,	'a:4:{s:6:\"sOrder\";a:40:{s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:12:\"order_number\";s:5:\"20003\";s:6:\"userID\";s:1:\"3\";s:10:\"customerID\";s:1:\"3\";s:14:\"invoice_amount\";s:5:\"31.85\";s:18:\"invoice_amount_net\";s:5:\"26.77\";s:16:\"invoice_shipping\";s:3:\"3.9\";s:20:\"invoice_shipping_net\";s:4:\"3.28\";s:9:\"ordertime\";s:19:\"2017-08-07 14:09:26\";s:6:\"status\";s:1:\"8\";s:8:\"statusID\";s:1:\"8\";s:7:\"cleared\";s:2:\"10\";s:9:\"clearedID\";s:2:\"10\";s:9:\"paymentID\";s:1:\"5\";s:13:\"transactionID\";s:0:\"\";s:7:\"comment\";s:0:\"\";s:15:\"customercomment\";s:0:\"\";s:3:\"net\";s:1:\"0\";s:5:\"netto\";s:1:\"0\";s:9:\"partnerID\";s:0:\"\";s:11:\"temporaryID\";s:0:\"\";s:7:\"referer\";s:0:\"\";s:11:\"cleareddate\";N;s:12:\"cleared_date\";N;s:12:\"trackingcode\";s:0:\"\";s:8:\"language\";s:1:\"1\";s:8:\"currency\";s:3:\"EUR\";s:14:\"currencyFactor\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:10:\"dispatchID\";s:1:\"9\";s:10:\"currencyID\";s:1:\"1\";s:12:\"cleared_name\";s:19:\"completely_invoiced\";s:19:\"cleared_description\";s:29:\"Komplett in Rechnung gestellt\";s:11:\"status_name\";s:22:\"clarification_required\";s:18:\"status_description\";s:18:\"Klärung notwendig\";s:19:\"payment_description\";s:8:\"Vorkasse\";s:20:\"dispatch_description\";s:16:\"Standard Versand\";s:20:\"currency_description\";s:4:\"Euro\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}s:13:\"sOrderDetails\";a:2:{i:0;a:20:{s:14:\"orderdetailsID\";s:3:\"208\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:3:\"152\";s:18:\"articleordernumber\";s:9:\"SW10152.1\";s:5:\"price\";s:5:\"29.95\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:5:\"29.95\";s:4:\"name\";s:31:\"WINDSTOPPER MÜTZE WARM Schwarz\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"0\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"1\";s:3:\"tax\";s:5:\"19.00\";s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"0\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";s:0:\"\";s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}i:1;a:20:{s:14:\"orderdetailsID\";s:3:\"209\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:1:\"0\";s:18:\"articleordernumber\";s:16:\"SHIPPINGDISCOUNT\";s:5:\"price\";s:2:\"-2\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:2:\"-2\";s:4:\"name\";s:15:\"Warenkorbrabatt\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"4\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"0\";s:3:\"tax\";N;s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"0\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}}s:5:\"sUser\";a:82:{s:15:\"billing_company\";s:0:\"\";s:18:\"billing_department\";s:0:\"\";s:18:\"billing_salutation\";s:2:\"mr\";s:14:\"customernumber\";s:5:\"20005\";s:17:\"billing_firstname\";s:3:\"Max\";s:16:\"billing_lastname\";s:10:\"Mustermann\";s:14:\"billing_street\";s:15:\"Musterstraße 1\";s:32:\"billing_additional_address_line1\";s:0:\"\";s:32:\"billing_additional_address_line2\";s:0:\"\";s:15:\"billing_zipcode\";s:5:\"12345\";s:12:\"billing_city\";s:11:\"Musterstadt\";s:5:\"phone\";s:0:\"\";s:13:\"billing_phone\";s:0:\"\";s:17:\"billing_countryID\";s:1:\"2\";s:15:\"billing_stateID\";N;s:15:\"billing_country\";s:11:\"Deutschland\";s:18:\"billing_countryiso\";s:2:\"DE\";s:19:\"billing_countryarea\";s:11:\"deutschland\";s:17:\"billing_countryen\";s:7:\"GERMANY\";s:5:\"ustid\";s:0:\"\";s:13:\"billing_text1\";N;s:13:\"billing_text2\";N;s:13:\"billing_text3\";N;s:13:\"billing_text4\";N;s:13:\"billing_text5\";N;s:13:\"billing_text6\";N;s:7:\"orderID\";s:2:\"59\";s:16:\"shipping_company\";s:0:\"\";s:19:\"shipping_department\";s:0:\"\";s:19:\"shipping_salutation\";s:2:\"mr\";s:18:\"shipping_firstname\";s:3:\"Max\";s:17:\"shipping_lastname\";s:10:\"Mustermann\";s:15:\"shipping_street\";s:15:\"Musterstraße 1\";s:33:\"shipping_additional_address_line1\";s:0:\"\";s:33:\"shipping_additional_address_line2\";s:0:\"\";s:16:\"shipping_zipcode\";s:5:\"12345\";s:13:\"shipping_city\";s:11:\"Musterstadt\";s:16:\"shipping_stateID\";N;s:18:\"shipping_countryID\";s:1:\"2\";s:16:\"shipping_country\";s:11:\"Deutschland\";s:19:\"shipping_countryiso\";s:2:\"DE\";s:20:\"shipping_countryarea\";s:11:\"deutschland\";s:18:\"shipping_countryen\";s:7:\"GERMANY\";s:14:\"shipping_text1\";N;s:14:\"shipping_text2\";N;s:14:\"shipping_text3\";N;s:14:\"shipping_text4\";N;s:14:\"shipping_text5\";N;s:14:\"shipping_text6\";N;s:2:\"id\";s:1:\"3\";s:8:\"password\";s:60:\"$2y$10$AzjwzOob83DJ2LG6yxXcBeghK9ciBB1zsK3UeBZADZCl10pTQN62W\";s:7:\"encoder\";s:6:\"bcrypt\";s:5:\"email\";s:14:\"xy@example.org\";s:6:\"active\";s:1:\"1\";s:11:\"accountmode\";s:1:\"0\";s:15:\"confirmationkey\";s:0:\"\";s:9:\"paymentID\";s:1:\"5\";s:10:\"firstlogin\";s:10:\"2017-08-07\";s:9:\"lastlogin\";s:19:\"2017-08-07 14:09:26\";s:9:\"sessionID\";s:26:\"hkkhfl82i1jejfvd2f0ucr6om4\";s:10:\"newsletter\";s:1:\"0\";s:10:\"validation\";s:0:\"\";s:9:\"affiliate\";s:1:\"0\";s:13:\"customergroup\";s:2:\"EK\";s:13:\"paymentpreset\";s:1:\"0\";s:8:\"language\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:7:\"referer\";s:0:\"\";s:12:\"pricegroupID\";N;s:15:\"internalcomment\";s:0:\"\";s:12:\"failedlogins\";s:1:\"0\";s:11:\"lockeduntil\";N;s:26:\"default_billing_address_id\";s:1:\"5\";s:27:\"default_shipping_address_id\";s:1:\"5\";s:5:\"title\";N;s:10:\"salutation\";s:2:\"mr\";s:9:\"firstname\";s:3:\"Max\";s:8:\"lastname\";s:10:\"Mustermann\";s:8:\"birthday\";N;s:11:\"login_token\";s:38:\"1239c089-6b2f-4461-9134-c02026970bff.1\";s:11:\"preisgruppe\";s:1:\"1\";s:11:\"billing_net\";s:1:\"1\";}s:9:\"sDispatch\";a:2:{s:4:\"name\";s:16:\"Standard Versand\";s:11:\"description\";s:0:\"\";}}',	0),
(24,	13,	'sORDERSTATEMAIL13',	'{config name=mail}',	'{config name=shopName}',	'1. Mahnung zur Bestellung bei {config name=shopName}',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo {$sUser.billing_firstname} {$sUser.billing_lastname},\n\ndies ist Ihre erste Mahnung zu Ihrer Bestellung {$sOrder.ordernumber} vom {$sOrder.ordertime|date_format:\"%d.%m.%Y\"}!\nDie Bestellung hat jetzt den Zahlungsstatus: {$sOrder.cleared_description}.\n\nBitte begleichen Sie schnellstmöglich Ihre Rechnung!\n\nDen aktuellen Status Ihrer Bestellung können Sie auch jederzeit auf unserer Webseite im  Bereich \"Mein Konto\" - \"Meine Bestellungen\" abrufen. Sollten Sie allerdings den Kauf ohne Registrierung, also ohne Anlage eines Kundenkontos, gewählt haben, steht Ihnen diese Möglichkeit nicht zur Verfügung.\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n        Hallo {$sUser.billing_firstname} {$sUser.billing_lastname},<br/>\n        <br/>\n        dies ist Ihre erste Mahnung zu Ihrer Bestellung {$sOrder.ordernumber} vom {$sOrder.ordertime|date_format:\"%d.%m.%Y\"}!<br/>\n        <strong>Die Bestellung hat jetzt den Zahlungsstatus: {$sOrder.cleared_description}.</strong><br/>\n        <br/>\n        <strong>Bitte begleichen Sie schnellstmöglich Ihre Rechnung!</strong><br/>\n        <br/>\n        Den aktuellen Status Ihrer Bestellung können Sie auch jederzeit auf unserer Webseite im  Bereich \"Mein Konto\" - \"Meine Bestellungen\" abrufen. Sollten Sie allerdings den Kauf ohne Registrierung, also ohne Anlage eines Kundenkontos, gewählt haben, steht Ihnen diese Möglichkeit nicht zur Verfügung.\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	3,	'a:4:{s:6:\"sOrder\";a:40:{s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:12:\"order_number\";s:5:\"20003\";s:6:\"userID\";s:1:\"3\";s:10:\"customerID\";s:1:\"3\";s:14:\"invoice_amount\";s:5:\"31.85\";s:18:\"invoice_amount_net\";s:5:\"26.77\";s:16:\"invoice_shipping\";s:3:\"3.9\";s:20:\"invoice_shipping_net\";s:4:\"3.28\";s:9:\"ordertime\";s:19:\"2017-08-07 14:09:26\";s:6:\"status\";s:1:\"8\";s:8:\"statusID\";s:1:\"8\";s:7:\"cleared\";s:2:\"13\";s:9:\"clearedID\";s:2:\"13\";s:9:\"paymentID\";s:1:\"5\";s:13:\"transactionID\";s:0:\"\";s:7:\"comment\";s:0:\"\";s:15:\"customercomment\";s:0:\"\";s:3:\"net\";s:1:\"0\";s:5:\"netto\";s:1:\"0\";s:9:\"partnerID\";s:0:\"\";s:11:\"temporaryID\";s:0:\"\";s:7:\"referer\";s:0:\"\";s:11:\"cleareddate\";N;s:12:\"cleared_date\";N;s:12:\"trackingcode\";s:0:\"\";s:8:\"language\";s:1:\"1\";s:8:\"currency\";s:3:\"EUR\";s:14:\"currencyFactor\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:10:\"dispatchID\";s:1:\"9\";s:10:\"currencyID\";s:1:\"1\";s:12:\"cleared_name\";s:12:\"1st_reminder\";s:19:\"cleared_description\";s:10:\"1. Mahnung\";s:11:\"status_name\";s:22:\"clarification_required\";s:18:\"status_description\";s:18:\"Klärung notwendig\";s:19:\"payment_description\";s:8:\"Vorkasse\";s:20:\"dispatch_description\";s:16:\"Standard Versand\";s:20:\"currency_description\";s:4:\"Euro\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}s:13:\"sOrderDetails\";a:2:{i:0;a:20:{s:14:\"orderdetailsID\";s:3:\"208\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:3:\"152\";s:18:\"articleordernumber\";s:9:\"SW10152.1\";s:5:\"price\";s:5:\"29.95\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:5:\"29.95\";s:4:\"name\";s:31:\"WINDSTOPPER MÜTZE WARM Schwarz\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"0\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"1\";s:3:\"tax\";s:5:\"19.00\";s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"0\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";s:0:\"\";s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}i:1;a:20:{s:14:\"orderdetailsID\";s:3:\"209\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:1:\"0\";s:18:\"articleordernumber\";s:16:\"SHIPPINGDISCOUNT\";s:5:\"price\";s:2:\"-2\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:2:\"-2\";s:4:\"name\";s:15:\"Warenkorbrabatt\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"4\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"0\";s:3:\"tax\";N;s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"0\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}}s:5:\"sUser\";a:82:{s:15:\"billing_company\";s:0:\"\";s:18:\"billing_department\";s:0:\"\";s:18:\"billing_salutation\";s:2:\"mr\";s:14:\"customernumber\";s:5:\"20005\";s:17:\"billing_firstname\";s:3:\"Max\";s:16:\"billing_lastname\";s:10:\"Mustermann\";s:14:\"billing_street\";s:15:\"Musterstraße 1\";s:32:\"billing_additional_address_line1\";s:0:\"\";s:32:\"billing_additional_address_line2\";s:0:\"\";s:15:\"billing_zipcode\";s:5:\"12345\";s:12:\"billing_city\";s:11:\"Musterstadt\";s:5:\"phone\";s:0:\"\";s:13:\"billing_phone\";s:0:\"\";s:17:\"billing_countryID\";s:1:\"2\";s:15:\"billing_stateID\";N;s:15:\"billing_country\";s:11:\"Deutschland\";s:18:\"billing_countryiso\";s:2:\"DE\";s:19:\"billing_countryarea\";s:11:\"deutschland\";s:17:\"billing_countryen\";s:7:\"GERMANY\";s:5:\"ustid\";s:0:\"\";s:13:\"billing_text1\";N;s:13:\"billing_text2\";N;s:13:\"billing_text3\";N;s:13:\"billing_text4\";N;s:13:\"billing_text5\";N;s:13:\"billing_text6\";N;s:7:\"orderID\";s:2:\"59\";s:16:\"shipping_company\";s:0:\"\";s:19:\"shipping_department\";s:0:\"\";s:19:\"shipping_salutation\";s:2:\"mr\";s:18:\"shipping_firstname\";s:3:\"Max\";s:17:\"shipping_lastname\";s:10:\"Mustermann\";s:15:\"shipping_street\";s:15:\"Musterstraße 1\";s:33:\"shipping_additional_address_line1\";s:0:\"\";s:33:\"shipping_additional_address_line2\";s:0:\"\";s:16:\"shipping_zipcode\";s:5:\"12345\";s:13:\"shipping_city\";s:11:\"Musterstadt\";s:16:\"shipping_stateID\";N;s:18:\"shipping_countryID\";s:1:\"2\";s:16:\"shipping_country\";s:11:\"Deutschland\";s:19:\"shipping_countryiso\";s:2:\"DE\";s:20:\"shipping_countryarea\";s:11:\"deutschland\";s:18:\"shipping_countryen\";s:7:\"GERMANY\";s:14:\"shipping_text1\";N;s:14:\"shipping_text2\";N;s:14:\"shipping_text3\";N;s:14:\"shipping_text4\";N;s:14:\"shipping_text5\";N;s:14:\"shipping_text6\";N;s:2:\"id\";s:1:\"3\";s:8:\"password\";s:60:\"$2y$10$AzjwzOob83DJ2LG6yxXcBeghK9ciBB1zsK3UeBZADZCl10pTQN62W\";s:7:\"encoder\";s:6:\"bcrypt\";s:5:\"email\";s:14:\"xy@example.org\";s:6:\"active\";s:1:\"1\";s:11:\"accountmode\";s:1:\"0\";s:15:\"confirmationkey\";s:0:\"\";s:9:\"paymentID\";s:1:\"5\";s:10:\"firstlogin\";s:10:\"2017-08-07\";s:9:\"lastlogin\";s:19:\"2017-08-07 14:09:26\";s:9:\"sessionID\";s:26:\"hkkhfl82i1jejfvd2f0ucr6om4\";s:10:\"newsletter\";s:1:\"0\";s:10:\"validation\";s:0:\"\";s:9:\"affiliate\";s:1:\"0\";s:13:\"customergroup\";s:2:\"EK\";s:13:\"paymentpreset\";s:1:\"0\";s:8:\"language\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:7:\"referer\";s:0:\"\";s:12:\"pricegroupID\";N;s:15:\"internalcomment\";s:0:\"\";s:12:\"failedlogins\";s:1:\"0\";s:11:\"lockeduntil\";N;s:26:\"default_billing_address_id\";s:1:\"5\";s:27:\"default_shipping_address_id\";s:1:\"5\";s:5:\"title\";N;s:10:\"salutation\";s:2:\"mr\";s:9:\"firstname\";s:3:\"Max\";s:8:\"lastname\";s:10:\"Mustermann\";s:8:\"birthday\";N;s:11:\"login_token\";s:38:\"1239c089-6b2f-4461-9134-c02026970bff.1\";s:11:\"preisgruppe\";s:1:\"1\";s:11:\"billing_net\";s:1:\"1\";}s:9:\"sDispatch\";a:2:{s:4:\"name\";s:16:\"Standard Versand\";s:11:\"description\";s:0:\"\";}}',	0),
(25,	16,	'sORDERSTATEMAIL16',	'{config name=mail}',	'{config name=shopName}',	'Inkasso der Bestellung bei {config name=shopName}',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo {$sUser.billing_firstname} {$sUser.billing_lastname},\n\nSie haben inzwischen 3 Mahnungen zu Ihrer Bestellung {$sOrder.ordernumber} vom {$sOrder.ordertime|date_format:\"%d.%m.%Y\"} erhalten!\nDie Bestellung hat jetzt den Zahlungsstatus: {$sOrder.cleared_description}.\n\nSie werden in Kürze Post von einem Inkasso Unternehmen erhalten!\n\nDen aktuellen Status Ihrer Bestellung können Sie auch jederzeit auf unserer Webseite im  Bereich \"Mein Konto\" - \"Meine Bestellungen\" abrufen. Sollten Sie allerdings den Kauf ohne Registrierung, also ohne Anlage eines Kundenkontos, gewählt haben, steht Ihnen diese Möglichkeit nicht zur Verfügung.\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n        Hallo {$sUser.billing_firstname} {$sUser.billing_lastname},<br/>\n        <br/>\n        Sie haben inzwischen 3 Mahnungen zu Ihrer Bestellung {$sOrder.ordernumber} vom {$sOrder.ordertime|date_format:\"%d.%m.%Y\"} erhalten!<br/>\n        <strong>Die Bestellung hat jetzt den Zahlungsstatus: {$sOrder.cleared_description}.</strong><br/>\n        <br/>\n        <strong>Sie werden in Kürze Post von einem Inkasso Unternehmen erhalten!</strong><br/>\n        <br/>\n        Den aktuellen Status Ihrer Bestellung können Sie auch jederzeit auf unserer Webseite im  Bereich \"Mein Konto\" - \"Meine Bestellungen\" abrufen. Sollten Sie allerdings den Kauf ohne Registrierung, also ohne Anlage eines Kundenkontos, gewählt haben, steht Ihnen diese Möglichkeit nicht zur Verfügung.\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	3,	'a:4:{s:6:\"sOrder\";a:40:{s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:12:\"order_number\";s:5:\"20003\";s:6:\"userID\";s:1:\"3\";s:10:\"customerID\";s:1:\"3\";s:14:\"invoice_amount\";s:5:\"31.85\";s:18:\"invoice_amount_net\";s:5:\"26.77\";s:16:\"invoice_shipping\";s:3:\"3.9\";s:20:\"invoice_shipping_net\";s:4:\"3.28\";s:9:\"ordertime\";s:19:\"2017-08-07 14:09:26\";s:6:\"status\";s:1:\"8\";s:8:\"statusID\";s:1:\"8\";s:7:\"cleared\";s:2:\"16\";s:9:\"clearedID\";s:2:\"16\";s:9:\"paymentID\";s:1:\"5\";s:13:\"transactionID\";s:0:\"\";s:7:\"comment\";s:0:\"\";s:15:\"customercomment\";s:0:\"\";s:3:\"net\";s:1:\"0\";s:5:\"netto\";s:1:\"0\";s:9:\"partnerID\";s:0:\"\";s:11:\"temporaryID\";s:0:\"\";s:7:\"referer\";s:0:\"\";s:11:\"cleareddate\";N;s:12:\"cleared_date\";N;s:12:\"trackingcode\";s:0:\"\";s:8:\"language\";s:1:\"1\";s:8:\"currency\";s:3:\"EUR\";s:14:\"currencyFactor\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:10:\"dispatchID\";s:1:\"9\";s:10:\"currencyID\";s:1:\"1\";s:12:\"cleared_name\";s:10:\"encashment\";s:19:\"cleared_description\";s:7:\"Inkasso\";s:11:\"status_name\";s:22:\"clarification_required\";s:18:\"status_description\";s:18:\"Klärung notwendig\";s:19:\"payment_description\";s:8:\"Vorkasse\";s:20:\"dispatch_description\";s:16:\"Standard Versand\";s:20:\"currency_description\";s:4:\"Euro\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}s:13:\"sOrderDetails\";a:2:{i:0;a:20:{s:14:\"orderdetailsID\";s:3:\"208\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:3:\"152\";s:18:\"articleordernumber\";s:9:\"SW10152.1\";s:5:\"price\";s:5:\"29.95\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:5:\"29.95\";s:4:\"name\";s:31:\"WINDSTOPPER MÜTZE WARM Schwarz\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"0\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"1\";s:3:\"tax\";s:5:\"19.00\";s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"0\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";s:0:\"\";s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}i:1;a:20:{s:14:\"orderdetailsID\";s:3:\"209\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:1:\"0\";s:18:\"articleordernumber\";s:16:\"SHIPPINGDISCOUNT\";s:5:\"price\";s:2:\"-2\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:2:\"-2\";s:4:\"name\";s:15:\"Warenkorbrabatt\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"4\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"0\";s:3:\"tax\";N;s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"0\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}}s:5:\"sUser\";a:82:{s:15:\"billing_company\";s:0:\"\";s:18:\"billing_department\";s:0:\"\";s:18:\"billing_salutation\";s:2:\"mr\";s:14:\"customernumber\";s:5:\"20005\";s:17:\"billing_firstname\";s:3:\"Max\";s:16:\"billing_lastname\";s:10:\"Mustermann\";s:14:\"billing_street\";s:15:\"Musterstraße 1\";s:32:\"billing_additional_address_line1\";s:0:\"\";s:32:\"billing_additional_address_line2\";s:0:\"\";s:15:\"billing_zipcode\";s:5:\"12345\";s:12:\"billing_city\";s:11:\"Musterstadt\";s:5:\"phone\";s:0:\"\";s:13:\"billing_phone\";s:0:\"\";s:17:\"billing_countryID\";s:1:\"2\";s:15:\"billing_stateID\";N;s:15:\"billing_country\";s:11:\"Deutschland\";s:18:\"billing_countryiso\";s:2:\"DE\";s:19:\"billing_countryarea\";s:11:\"deutschland\";s:17:\"billing_countryen\";s:7:\"GERMANY\";s:5:\"ustid\";s:0:\"\";s:13:\"billing_text1\";N;s:13:\"billing_text2\";N;s:13:\"billing_text3\";N;s:13:\"billing_text4\";N;s:13:\"billing_text5\";N;s:13:\"billing_text6\";N;s:7:\"orderID\";s:2:\"59\";s:16:\"shipping_company\";s:0:\"\";s:19:\"shipping_department\";s:0:\"\";s:19:\"shipping_salutation\";s:2:\"mr\";s:18:\"shipping_firstname\";s:3:\"Max\";s:17:\"shipping_lastname\";s:10:\"Mustermann\";s:15:\"shipping_street\";s:15:\"Musterstraße 1\";s:33:\"shipping_additional_address_line1\";s:0:\"\";s:33:\"shipping_additional_address_line2\";s:0:\"\";s:16:\"shipping_zipcode\";s:5:\"12345\";s:13:\"shipping_city\";s:11:\"Musterstadt\";s:16:\"shipping_stateID\";N;s:18:\"shipping_countryID\";s:1:\"2\";s:16:\"shipping_country\";s:11:\"Deutschland\";s:19:\"shipping_countryiso\";s:2:\"DE\";s:20:\"shipping_countryarea\";s:11:\"deutschland\";s:18:\"shipping_countryen\";s:7:\"GERMANY\";s:14:\"shipping_text1\";N;s:14:\"shipping_text2\";N;s:14:\"shipping_text3\";N;s:14:\"shipping_text4\";N;s:14:\"shipping_text5\";N;s:14:\"shipping_text6\";N;s:2:\"id\";s:1:\"3\";s:8:\"password\";s:60:\"$2y$10$AzjwzOob83DJ2LG6yxXcBeghK9ciBB1zsK3UeBZADZCl10pTQN62W\";s:7:\"encoder\";s:6:\"bcrypt\";s:5:\"email\";s:14:\"xy@example.org\";s:6:\"active\";s:1:\"1\";s:11:\"accountmode\";s:1:\"0\";s:15:\"confirmationkey\";s:0:\"\";s:9:\"paymentID\";s:1:\"5\";s:10:\"firstlogin\";s:10:\"2017-08-07\";s:9:\"lastlogin\";s:19:\"2017-08-07 14:09:26\";s:9:\"sessionID\";s:26:\"hkkhfl82i1jejfvd2f0ucr6om4\";s:10:\"newsletter\";s:1:\"0\";s:10:\"validation\";s:0:\"\";s:9:\"affiliate\";s:1:\"0\";s:13:\"customergroup\";s:2:\"EK\";s:13:\"paymentpreset\";s:1:\"0\";s:8:\"language\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:7:\"referer\";s:0:\"\";s:12:\"pricegroupID\";N;s:15:\"internalcomment\";s:0:\"\";s:12:\"failedlogins\";s:1:\"0\";s:11:\"lockeduntil\";N;s:26:\"default_billing_address_id\";s:1:\"5\";s:27:\"default_shipping_address_id\";s:1:\"5\";s:5:\"title\";N;s:10:\"salutation\";s:2:\"mr\";s:9:\"firstname\";s:3:\"Max\";s:8:\"lastname\";s:10:\"Mustermann\";s:8:\"birthday\";N;s:11:\"login_token\";s:38:\"1239c089-6b2f-4461-9134-c02026970bff.1\";s:11:\"preisgruppe\";s:1:\"1\";s:11:\"billing_net\";s:1:\"1\";}s:9:\"sDispatch\";a:2:{s:4:\"name\";s:16:\"Standard Versand\";s:11:\"description\";s:0:\"\";}}',	0),
(26,	15,	'sORDERSTATEMAIL15',	'{config name=mail}',	'{config name=shopName}',	'3. Mahnung zur Bestellung bei {config name=shopName}',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo {$sUser.billing_firstname} {$sUser.billing_lastname},\n\ndies ist Ihre dritte und letzte Mahnung zu Ihrer Bestellung {$sOrder.ordernumber} vom {$sOrder.ordertime|date_format:\"%d.%m.%Y\"}!\nDie Bestellung hat jetzt den Zahlungsstatus: {$sOrder.cleared_description}.\n\nBitte begleichen Sie schnellstmöglich Ihre Rechnung!\n\nDen aktuellen Status Ihrer Bestellung können Sie auch jederzeit auf unserer Webseite im  Bereich \"Mein Konto\" - \"Meine Bestellungen\" abrufen. Sollten Sie allerdings den Kauf ohne Registrierung, also ohne Anlage eines Kundenkontos, gewählt haben, steht Ihnen diese Möglichkeit nicht zur Verfügung.\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n        Hallo {$sUser.billing_firstname} {$sUser.billing_lastname},<br/>\n        <br/>\n        dies ist Ihre dritte und letzte Mahnung zu Ihrer Bestellung {$sOrder.ordernumber} vom {$sOrder.ordertime|date_format:\"%d.%m.%Y\"}!<br/>\n        <strong>Die Bestellung hat jetzt den Zahlungsstatus: {$sOrder.cleared_description}.</strong><br/>\n        <br/>\n        <strong>Bitte begleichen Sie schnellstmöglich Ihre Rechnung!</strong><br/>\n        <br/>\n        Den aktuellen Status Ihrer Bestellung können Sie auch jederzeit auf unserer Webseite im  Bereich \"Mein Konto\" - \"Meine Bestellungen\" abrufen. Sollten Sie allerdings den Kauf ohne Registrierung, also ohne Anlage eines Kundenkontos, gewählt haben, steht Ihnen diese Möglichkeit nicht zur Verfügung.\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	3,	'a:4:{s:6:\"sOrder\";a:40:{s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:12:\"order_number\";s:5:\"20003\";s:6:\"userID\";s:1:\"3\";s:10:\"customerID\";s:1:\"3\";s:14:\"invoice_amount\";s:5:\"31.85\";s:18:\"invoice_amount_net\";s:5:\"26.77\";s:16:\"invoice_shipping\";s:3:\"3.9\";s:20:\"invoice_shipping_net\";s:4:\"3.28\";s:9:\"ordertime\";s:19:\"2017-08-07 14:09:26\";s:6:\"status\";s:1:\"8\";s:8:\"statusID\";s:1:\"8\";s:7:\"cleared\";s:2:\"15\";s:9:\"clearedID\";s:2:\"15\";s:9:\"paymentID\";s:1:\"5\";s:13:\"transactionID\";s:0:\"\";s:7:\"comment\";s:0:\"\";s:15:\"customercomment\";s:0:\"\";s:3:\"net\";s:1:\"0\";s:5:\"netto\";s:1:\"0\";s:9:\"partnerID\";s:0:\"\";s:11:\"temporaryID\";s:0:\"\";s:7:\"referer\";s:0:\"\";s:11:\"cleareddate\";N;s:12:\"cleared_date\";N;s:12:\"trackingcode\";s:0:\"\";s:8:\"language\";s:1:\"1\";s:8:\"currency\";s:3:\"EUR\";s:14:\"currencyFactor\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:10:\"dispatchID\";s:1:\"9\";s:10:\"currencyID\";s:1:\"1\";s:12:\"cleared_name\";s:12:\"3rd_reminder\";s:19:\"cleared_description\";s:10:\"3. Mahnung\";s:11:\"status_name\";s:22:\"clarification_required\";s:18:\"status_description\";s:18:\"Klärung notwendig\";s:19:\"payment_description\";s:8:\"Vorkasse\";s:20:\"dispatch_description\";s:16:\"Standard Versand\";s:20:\"currency_description\";s:4:\"Euro\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}s:13:\"sOrderDetails\";a:2:{i:0;a:20:{s:14:\"orderdetailsID\";s:3:\"208\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:3:\"152\";s:18:\"articleordernumber\";s:9:\"SW10152.1\";s:5:\"price\";s:5:\"29.95\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:5:\"29.95\";s:4:\"name\";s:31:\"WINDSTOPPER MÜTZE WARM Schwarz\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"0\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"1\";s:3:\"tax\";s:5:\"19.00\";s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"0\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";s:0:\"\";s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}i:1;a:20:{s:14:\"orderdetailsID\";s:3:\"209\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:1:\"0\";s:18:\"articleordernumber\";s:16:\"SHIPPINGDISCOUNT\";s:5:\"price\";s:2:\"-2\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:2:\"-2\";s:4:\"name\";s:15:\"Warenkorbrabatt\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"4\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"0\";s:3:\"tax\";N;s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"0\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}}s:5:\"sUser\";a:82:{s:15:\"billing_company\";s:0:\"\";s:18:\"billing_department\";s:0:\"\";s:18:\"billing_salutation\";s:2:\"mr\";s:14:\"customernumber\";s:5:\"20005\";s:17:\"billing_firstname\";s:3:\"Max\";s:16:\"billing_lastname\";s:10:\"Mustermann\";s:14:\"billing_street\";s:15:\"Musterstraße 1\";s:32:\"billing_additional_address_line1\";s:0:\"\";s:32:\"billing_additional_address_line2\";s:0:\"\";s:15:\"billing_zipcode\";s:5:\"12345\";s:12:\"billing_city\";s:11:\"Musterstadt\";s:5:\"phone\";s:0:\"\";s:13:\"billing_phone\";s:0:\"\";s:17:\"billing_countryID\";s:1:\"2\";s:15:\"billing_stateID\";N;s:15:\"billing_country\";s:11:\"Deutschland\";s:18:\"billing_countryiso\";s:2:\"DE\";s:19:\"billing_countryarea\";s:11:\"deutschland\";s:17:\"billing_countryen\";s:7:\"GERMANY\";s:5:\"ustid\";s:0:\"\";s:13:\"billing_text1\";N;s:13:\"billing_text2\";N;s:13:\"billing_text3\";N;s:13:\"billing_text4\";N;s:13:\"billing_text5\";N;s:13:\"billing_text6\";N;s:7:\"orderID\";s:2:\"59\";s:16:\"shipping_company\";s:0:\"\";s:19:\"shipping_department\";s:0:\"\";s:19:\"shipping_salutation\";s:2:\"mr\";s:18:\"shipping_firstname\";s:3:\"Max\";s:17:\"shipping_lastname\";s:10:\"Mustermann\";s:15:\"shipping_street\";s:15:\"Musterstraße 1\";s:33:\"shipping_additional_address_line1\";s:0:\"\";s:33:\"shipping_additional_address_line2\";s:0:\"\";s:16:\"shipping_zipcode\";s:5:\"12345\";s:13:\"shipping_city\";s:11:\"Musterstadt\";s:16:\"shipping_stateID\";N;s:18:\"shipping_countryID\";s:1:\"2\";s:16:\"shipping_country\";s:11:\"Deutschland\";s:19:\"shipping_countryiso\";s:2:\"DE\";s:20:\"shipping_countryarea\";s:11:\"deutschland\";s:18:\"shipping_countryen\";s:7:\"GERMANY\";s:14:\"shipping_text1\";N;s:14:\"shipping_text2\";N;s:14:\"shipping_text3\";N;s:14:\"shipping_text4\";N;s:14:\"shipping_text5\";N;s:14:\"shipping_text6\";N;s:2:\"id\";s:1:\"3\";s:8:\"password\";s:60:\"$2y$10$AzjwzOob83DJ2LG6yxXcBeghK9ciBB1zsK3UeBZADZCl10pTQN62W\";s:7:\"encoder\";s:6:\"bcrypt\";s:5:\"email\";s:14:\"xy@example.org\";s:6:\"active\";s:1:\"1\";s:11:\"accountmode\";s:1:\"0\";s:15:\"confirmationkey\";s:0:\"\";s:9:\"paymentID\";s:1:\"5\";s:10:\"firstlogin\";s:10:\"2017-08-07\";s:9:\"lastlogin\";s:19:\"2017-08-07 14:09:26\";s:9:\"sessionID\";s:26:\"hkkhfl82i1jejfvd2f0ucr6om4\";s:10:\"newsletter\";s:1:\"0\";s:10:\"validation\";s:0:\"\";s:9:\"affiliate\";s:1:\"0\";s:13:\"customergroup\";s:2:\"EK\";s:13:\"paymentpreset\";s:1:\"0\";s:8:\"language\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:7:\"referer\";s:0:\"\";s:12:\"pricegroupID\";N;s:15:\"internalcomment\";s:0:\"\";s:12:\"failedlogins\";s:1:\"0\";s:11:\"lockeduntil\";N;s:26:\"default_billing_address_id\";s:1:\"5\";s:27:\"default_shipping_address_id\";s:1:\"5\";s:5:\"title\";N;s:10:\"salutation\";s:2:\"mr\";s:9:\"firstname\";s:3:\"Max\";s:8:\"lastname\";s:10:\"Mustermann\";s:8:\"birthday\";N;s:11:\"login_token\";s:38:\"1239c089-6b2f-4461-9134-c02026970bff.1\";s:11:\"preisgruppe\";s:1:\"1\";s:11:\"billing_net\";s:1:\"1\";}s:9:\"sDispatch\";a:2:{s:4:\"name\";s:16:\"Standard Versand\";s:11:\"description\";s:0:\"\";}}',	0),
(27,	14,	'sORDERSTATEMAIL14',	'{config name=mail}',	'{config name=shopName}',	'2. Mahnung zur Bestellung bei {config name=shopName}',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo {$sUser.billing_firstname} {$sUser.billing_lastname},\n\ndies ist Ihre zweite Mahnung zu Ihrer Bestellung {$sOrder.ordernumber} vom {$sOrder.ordertime|date_format:\"%d.%m.%Y\"}!\nDie Bestellung hat jetzt den Zahlungsstatus: {$sOrder.cleared_description}.\n\nBitte begleichen Sie schnellstmöglich Ihre Rechnung!\n\nDen aktuellen Status Ihrer Bestellung können Sie auch jederzeit auf unserer Webseite im  Bereich \"Mein Konto\" - \"Meine Bestellungen\" abrufen. Sollten Sie allerdings den Kauf ohne Registrierung, also ohne Anlage eines Kundenkontos, gewählt haben, steht Ihnen diese Möglichkeit nicht zur Verfügung.\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n        Hallo {$sUser.billing_firstname} {$sUser.billing_lastname},<br/>\n        <br/>\n        dies ist Ihre zweite Mahnung zu Ihrer Bestellung {$sOrder.ordernumber} vom {$sOrder.ordertime|date_format:\"%d.%m.%Y\"}!<br/>\n        <strong>Die Bestellung hat jetzt den Zahlungsstatus: {$sOrder.cleared_description}.</strong><br/>\n        <br/>\n        <strong>Bitte begleichen Sie schnellstmöglich Ihre Rechnung!</strong><br/>\n        <br/>\n        Den aktuellen Status Ihrer Bestellung können Sie auch jederzeit auf unserer Webseite im  Bereich \"Mein Konto\" - \"Meine Bestellungen\" abrufen. Sollten Sie allerdings den Kauf ohne Registrierung, also ohne Anlage eines Kundenkontos, gewählt haben, steht Ihnen diese Möglichkeit nicht zur Verfügung.\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	3,	'a:4:{s:6:\"sOrder\";a:40:{s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:12:\"order_number\";s:5:\"20003\";s:6:\"userID\";s:1:\"3\";s:10:\"customerID\";s:1:\"3\";s:14:\"invoice_amount\";s:5:\"31.85\";s:18:\"invoice_amount_net\";s:5:\"26.77\";s:16:\"invoice_shipping\";s:3:\"3.9\";s:20:\"invoice_shipping_net\";s:4:\"3.28\";s:9:\"ordertime\";s:19:\"2017-08-07 14:09:26\";s:6:\"status\";s:1:\"8\";s:8:\"statusID\";s:1:\"8\";s:7:\"cleared\";s:2:\"14\";s:9:\"clearedID\";s:2:\"14\";s:9:\"paymentID\";s:1:\"5\";s:13:\"transactionID\";s:0:\"\";s:7:\"comment\";s:0:\"\";s:15:\"customercomment\";s:0:\"\";s:3:\"net\";s:1:\"0\";s:5:\"netto\";s:1:\"0\";s:9:\"partnerID\";s:0:\"\";s:11:\"temporaryID\";s:0:\"\";s:7:\"referer\";s:0:\"\";s:11:\"cleareddate\";N;s:12:\"cleared_date\";N;s:12:\"trackingcode\";s:0:\"\";s:8:\"language\";s:1:\"1\";s:8:\"currency\";s:3:\"EUR\";s:14:\"currencyFactor\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:10:\"dispatchID\";s:1:\"9\";s:10:\"currencyID\";s:1:\"1\";s:12:\"cleared_name\";s:12:\"2nd_reminder\";s:19:\"cleared_description\";s:10:\"2. Mahnung\";s:11:\"status_name\";s:22:\"clarification_required\";s:18:\"status_description\";s:18:\"Klärung notwendig\";s:19:\"payment_description\";s:8:\"Vorkasse\";s:20:\"dispatch_description\";s:16:\"Standard Versand\";s:20:\"currency_description\";s:4:\"Euro\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}s:13:\"sOrderDetails\";a:2:{i:0;a:20:{s:14:\"orderdetailsID\";s:3:\"208\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:3:\"152\";s:18:\"articleordernumber\";s:9:\"SW10152.1\";s:5:\"price\";s:5:\"29.95\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:5:\"29.95\";s:4:\"name\";s:31:\"WINDSTOPPER MÜTZE WARM Schwarz\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"0\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"1\";s:3:\"tax\";s:5:\"19.00\";s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"0\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";s:0:\"\";s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}i:1;a:20:{s:14:\"orderdetailsID\";s:3:\"209\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:1:\"0\";s:18:\"articleordernumber\";s:16:\"SHIPPINGDISCOUNT\";s:5:\"price\";s:2:\"-2\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:2:\"-2\";s:4:\"name\";s:15:\"Warenkorbrabatt\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"4\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"0\";s:3:\"tax\";N;s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"0\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}}s:5:\"sUser\";a:82:{s:15:\"billing_company\";s:0:\"\";s:18:\"billing_department\";s:0:\"\";s:18:\"billing_salutation\";s:2:\"mr\";s:14:\"customernumber\";s:5:\"20005\";s:17:\"billing_firstname\";s:3:\"Max\";s:16:\"billing_lastname\";s:10:\"Mustermann\";s:14:\"billing_street\";s:15:\"Musterstraße 1\";s:32:\"billing_additional_address_line1\";s:0:\"\";s:32:\"billing_additional_address_line2\";s:0:\"\";s:15:\"billing_zipcode\";s:5:\"12345\";s:12:\"billing_city\";s:11:\"Musterstadt\";s:5:\"phone\";s:0:\"\";s:13:\"billing_phone\";s:0:\"\";s:17:\"billing_countryID\";s:1:\"2\";s:15:\"billing_stateID\";N;s:15:\"billing_country\";s:11:\"Deutschland\";s:18:\"billing_countryiso\";s:2:\"DE\";s:19:\"billing_countryarea\";s:11:\"deutschland\";s:17:\"billing_countryen\";s:7:\"GERMANY\";s:5:\"ustid\";s:0:\"\";s:13:\"billing_text1\";N;s:13:\"billing_text2\";N;s:13:\"billing_text3\";N;s:13:\"billing_text4\";N;s:13:\"billing_text5\";N;s:13:\"billing_text6\";N;s:7:\"orderID\";s:2:\"59\";s:16:\"shipping_company\";s:0:\"\";s:19:\"shipping_department\";s:0:\"\";s:19:\"shipping_salutation\";s:2:\"mr\";s:18:\"shipping_firstname\";s:3:\"Max\";s:17:\"shipping_lastname\";s:10:\"Mustermann\";s:15:\"shipping_street\";s:15:\"Musterstraße 1\";s:33:\"shipping_additional_address_line1\";s:0:\"\";s:33:\"shipping_additional_address_line2\";s:0:\"\";s:16:\"shipping_zipcode\";s:5:\"12345\";s:13:\"shipping_city\";s:11:\"Musterstadt\";s:16:\"shipping_stateID\";N;s:18:\"shipping_countryID\";s:1:\"2\";s:16:\"shipping_country\";s:11:\"Deutschland\";s:19:\"shipping_countryiso\";s:2:\"DE\";s:20:\"shipping_countryarea\";s:11:\"deutschland\";s:18:\"shipping_countryen\";s:7:\"GERMANY\";s:14:\"shipping_text1\";N;s:14:\"shipping_text2\";N;s:14:\"shipping_text3\";N;s:14:\"shipping_text4\";N;s:14:\"shipping_text5\";N;s:14:\"shipping_text6\";N;s:2:\"id\";s:1:\"3\";s:8:\"password\";s:60:\"$2y$10$AzjwzOob83DJ2LG6yxXcBeghK9ciBB1zsK3UeBZADZCl10pTQN62W\";s:7:\"encoder\";s:6:\"bcrypt\";s:5:\"email\";s:14:\"xy@example.org\";s:6:\"active\";s:1:\"1\";s:11:\"accountmode\";s:1:\"0\";s:15:\"confirmationkey\";s:0:\"\";s:9:\"paymentID\";s:1:\"5\";s:10:\"firstlogin\";s:10:\"2017-08-07\";s:9:\"lastlogin\";s:19:\"2017-08-07 14:09:26\";s:9:\"sessionID\";s:26:\"hkkhfl82i1jejfvd2f0ucr6om4\";s:10:\"newsletter\";s:1:\"0\";s:10:\"validation\";s:0:\"\";s:9:\"affiliate\";s:1:\"0\";s:13:\"customergroup\";s:2:\"EK\";s:13:\"paymentpreset\";s:1:\"0\";s:8:\"language\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:7:\"referer\";s:0:\"\";s:12:\"pricegroupID\";N;s:15:\"internalcomment\";s:0:\"\";s:12:\"failedlogins\";s:1:\"0\";s:11:\"lockeduntil\";N;s:26:\"default_billing_address_id\";s:1:\"5\";s:27:\"default_shipping_address_id\";s:1:\"5\";s:5:\"title\";N;s:10:\"salutation\";s:2:\"mr\";s:9:\"firstname\";s:3:\"Max\";s:8:\"lastname\";s:10:\"Mustermann\";s:8:\"birthday\";N;s:11:\"login_token\";s:38:\"1239c089-6b2f-4461-9134-c02026970bff.1\";s:11:\"preisgruppe\";s:1:\"1\";s:11:\"billing_net\";s:1:\"1\";}s:9:\"sDispatch\";a:2:{s:4:\"name\";s:16:\"Standard Versand\";s:11:\"description\";s:0:\"\";}}',	0),
(28,	12,	'sORDERSTATEMAIL12',	'{config name=mail}',	'{config name=shopName}',	'Bestellung bei {config name=shopName} ist komplett bezahlt',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo {$sUser.billing_firstname} {$sUser.billing_lastname},\n\nder Zahlungsstatus für Ihre Bestellung {$sOrder.ordernumber} vom {$sOrder.ordertime|date_format:\"%d.%m.%Y\"} hat sich geändert!\nDie Bestellung hat jetzt den Zahlungsstatus: {$sOrder.cleared_description}.\n\nDen aktuellen Status Ihrer Bestellung können Sie auch jederzeit auf unserer Webseite im  Bereich \"Mein Konto\" - \"Meine Bestellungen\" abrufen. Sollten Sie allerdings den Kauf ohne Registrierung, also ohne Anlage eines Kundenkontos, gewählt haben, steht Ihnen diese Möglichkeit nicht zur Verfügung.\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n        Hallo {$sUser.billing_firstname} {$sUser.billing_lastname},<br/>\n        <br/>\n        der Zahlungsstatus für Ihre Bestellung {$sOrder.ordernumber} vom {$sOrder.ordertime|date_format:\"%d.%m.%Y\"} hat sich geändert!<br/>\n        <strong>Die Bestellung hat jetzt den Zahlungsstatus: {$sOrder.cleared_description}.</strong><br/>\n        <br/>\n        Den aktuellen Status Ihrer Bestellung können Sie auch jederzeit auf unserer Webseite im  Bereich \"Mein Konto\" - \"Meine Bestellungen\" abrufen. Sollten Sie allerdings den Kauf ohne Registrierung, also ohne Anlage eines Kundenkontos, gewählt haben, steht Ihnen diese Möglichkeit nicht zur Verfügung.\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	3,	'a:4:{s:6:\"sOrder\";a:40:{s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:12:\"order_number\";s:5:\"20003\";s:6:\"userID\";s:1:\"3\";s:10:\"customerID\";s:1:\"3\";s:14:\"invoice_amount\";s:5:\"31.85\";s:18:\"invoice_amount_net\";s:5:\"26.77\";s:16:\"invoice_shipping\";s:3:\"3.9\";s:20:\"invoice_shipping_net\";s:4:\"3.28\";s:9:\"ordertime\";s:19:\"2017-08-07 14:09:26\";s:6:\"status\";s:1:\"8\";s:8:\"statusID\";s:1:\"8\";s:7:\"cleared\";s:2:\"12\";s:9:\"clearedID\";s:2:\"12\";s:9:\"paymentID\";s:1:\"5\";s:13:\"transactionID\";s:0:\"\";s:7:\"comment\";s:0:\"\";s:15:\"customercomment\";s:0:\"\";s:3:\"net\";s:1:\"0\";s:5:\"netto\";s:1:\"0\";s:9:\"partnerID\";s:0:\"\";s:11:\"temporaryID\";s:0:\"\";s:7:\"referer\";s:0:\"\";s:11:\"cleareddate\";N;s:12:\"cleared_date\";N;s:12:\"trackingcode\";s:0:\"\";s:8:\"language\";s:1:\"1\";s:8:\"currency\";s:3:\"EUR\";s:14:\"currencyFactor\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:10:\"dispatchID\";s:1:\"9\";s:10:\"currencyID\";s:1:\"1\";s:12:\"cleared_name\";s:15:\"completely_paid\";s:19:\"cleared_description\";s:16:\"Komplett bezahlt\";s:11:\"status_name\";s:22:\"clarification_required\";s:18:\"status_description\";s:18:\"Klärung notwendig\";s:19:\"payment_description\";s:8:\"Vorkasse\";s:20:\"dispatch_description\";s:16:\"Standard Versand\";s:20:\"currency_description\";s:4:\"Euro\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}s:13:\"sOrderDetails\";a:2:{i:0;a:20:{s:14:\"orderdetailsID\";s:3:\"208\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:3:\"152\";s:18:\"articleordernumber\";s:9:\"SW10152.1\";s:5:\"price\";s:5:\"29.95\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:5:\"29.95\";s:4:\"name\";s:31:\"WINDSTOPPER MÜTZE WARM Schwarz\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"0\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"1\";s:3:\"tax\";s:5:\"19.00\";s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"0\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";s:0:\"\";s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}i:1;a:20:{s:14:\"orderdetailsID\";s:3:\"209\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:1:\"0\";s:18:\"articleordernumber\";s:16:\"SHIPPINGDISCOUNT\";s:5:\"price\";s:2:\"-2\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:2:\"-2\";s:4:\"name\";s:15:\"Warenkorbrabatt\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"4\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"0\";s:3:\"tax\";N;s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"0\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}}s:5:\"sUser\";a:82:{s:15:\"billing_company\";s:0:\"\";s:18:\"billing_department\";s:0:\"\";s:18:\"billing_salutation\";s:2:\"mr\";s:14:\"customernumber\";s:5:\"20005\";s:17:\"billing_firstname\";s:3:\"Max\";s:16:\"billing_lastname\";s:10:\"Mustermann\";s:14:\"billing_street\";s:15:\"Musterstraße 1\";s:32:\"billing_additional_address_line1\";s:0:\"\";s:32:\"billing_additional_address_line2\";s:0:\"\";s:15:\"billing_zipcode\";s:5:\"12345\";s:12:\"billing_city\";s:11:\"Musterstadt\";s:5:\"phone\";s:0:\"\";s:13:\"billing_phone\";s:0:\"\";s:17:\"billing_countryID\";s:1:\"2\";s:15:\"billing_stateID\";N;s:15:\"billing_country\";s:11:\"Deutschland\";s:18:\"billing_countryiso\";s:2:\"DE\";s:19:\"billing_countryarea\";s:11:\"deutschland\";s:17:\"billing_countryen\";s:7:\"GERMANY\";s:5:\"ustid\";s:0:\"\";s:13:\"billing_text1\";N;s:13:\"billing_text2\";N;s:13:\"billing_text3\";N;s:13:\"billing_text4\";N;s:13:\"billing_text5\";N;s:13:\"billing_text6\";N;s:7:\"orderID\";s:2:\"59\";s:16:\"shipping_company\";s:0:\"\";s:19:\"shipping_department\";s:0:\"\";s:19:\"shipping_salutation\";s:2:\"mr\";s:18:\"shipping_firstname\";s:3:\"Max\";s:17:\"shipping_lastname\";s:10:\"Mustermann\";s:15:\"shipping_street\";s:15:\"Musterstraße 1\";s:33:\"shipping_additional_address_line1\";s:0:\"\";s:33:\"shipping_additional_address_line2\";s:0:\"\";s:16:\"shipping_zipcode\";s:5:\"12345\";s:13:\"shipping_city\";s:11:\"Musterstadt\";s:16:\"shipping_stateID\";N;s:18:\"shipping_countryID\";s:1:\"2\";s:16:\"shipping_country\";s:11:\"Deutschland\";s:19:\"shipping_countryiso\";s:2:\"DE\";s:20:\"shipping_countryarea\";s:11:\"deutschland\";s:18:\"shipping_countryen\";s:7:\"GERMANY\";s:14:\"shipping_text1\";N;s:14:\"shipping_text2\";N;s:14:\"shipping_text3\";N;s:14:\"shipping_text4\";N;s:14:\"shipping_text5\";N;s:14:\"shipping_text6\";N;s:2:\"id\";s:1:\"3\";s:8:\"password\";s:60:\"$2y$10$AzjwzOob83DJ2LG6yxXcBeghK9ciBB1zsK3UeBZADZCl10pTQN62W\";s:7:\"encoder\";s:6:\"bcrypt\";s:5:\"email\";s:14:\"xy@example.org\";s:6:\"active\";s:1:\"1\";s:11:\"accountmode\";s:1:\"0\";s:15:\"confirmationkey\";s:0:\"\";s:9:\"paymentID\";s:1:\"5\";s:10:\"firstlogin\";s:10:\"2017-08-07\";s:9:\"lastlogin\";s:19:\"2017-08-07 14:09:26\";s:9:\"sessionID\";s:26:\"hkkhfl82i1jejfvd2f0ucr6om4\";s:10:\"newsletter\";s:1:\"0\";s:10:\"validation\";s:0:\"\";s:9:\"affiliate\";s:1:\"0\";s:13:\"customergroup\";s:2:\"EK\";s:13:\"paymentpreset\";s:1:\"0\";s:8:\"language\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:7:\"referer\";s:0:\"\";s:12:\"pricegroupID\";N;s:15:\"internalcomment\";s:0:\"\";s:12:\"failedlogins\";s:1:\"0\";s:11:\"lockeduntil\";N;s:26:\"default_billing_address_id\";s:1:\"5\";s:27:\"default_shipping_address_id\";s:1:\"5\";s:5:\"title\";N;s:10:\"salutation\";s:2:\"mr\";s:9:\"firstname\";s:3:\"Max\";s:8:\"lastname\";s:10:\"Mustermann\";s:8:\"birthday\";N;s:11:\"login_token\";s:38:\"1239c089-6b2f-4461-9134-c02026970bff.1\";s:11:\"preisgruppe\";s:1:\"1\";s:11:\"billing_net\";s:1:\"1\";}s:9:\"sDispatch\";a:2:{s:4:\"name\";s:16:\"Standard Versand\";s:11:\"description\";s:0:\"\";}}',	0),
(32,	17,	'sORDERSTATEMAIL17',	'{config name=mail}',	'{config name=shopName}',	'Statusänderung zur Bestellung bei {config name=shopName}',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo {$sUser.billing_firstname} {$sUser.billing_lastname},\n\nder Zahlungsstatus für Ihre Bestellung {$sOrder.ordernumber} vom {$sOrder.ordertime|date_format:\"%d.%m.%Y\"} hat sich geändert!\nDie Bestellung hat jetzt den Zahlungsstatus: {$sOrder.cleared_description}.\n\nDen aktuellen Status Ihrer Bestellung können Sie auch jederzeit auf unserer Webseite im  Bereich \"Mein Konto\" - \"Meine Bestellungen\" abrufen. Sollten Sie allerdings den Kauf ohne Registrierung, also ohne Anlage eines Kundenkontos, gewählt haben, steht Ihnen diese Möglichkeit nicht zur Verfügung.\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n        Hallo {$sUser.billing_firstname} {$sUser.billing_lastname},<br/>\n        <br/>\n        der Zahlungsstatus für Ihre Bestellung {$sOrder.ordernumber} vom {$sOrder.ordertime|date_format:\"%d.%m.%Y\"} hat sich geändert!<br/>\n        <strong>Die Bestellung hat jetzt den Zahlungsstatus: {$sOrder.cleared_description}.</strong><br/>\n        <br/>\n        Den aktuellen Status Ihrer Bestellung können Sie auch jederzeit auf unserer Webseite im  Bereich \"Mein Konto\" - \"Meine Bestellungen\" abrufen. Sollten Sie allerdings den Kauf ohne Registrierung, also ohne Anlage eines Kundenkontos, gewählt haben, steht Ihnen diese Möglichkeit nicht zur Verfügung.\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	3,	'a:4:{s:6:\"sOrder\";a:40:{s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:12:\"order_number\";s:5:\"20003\";s:6:\"userID\";s:1:\"3\";s:10:\"customerID\";s:1:\"3\";s:14:\"invoice_amount\";s:5:\"31.85\";s:18:\"invoice_amount_net\";s:5:\"26.77\";s:16:\"invoice_shipping\";s:3:\"3.9\";s:20:\"invoice_shipping_net\";s:4:\"3.28\";s:9:\"ordertime\";s:19:\"2017-08-07 14:09:26\";s:6:\"status\";s:1:\"8\";s:8:\"statusID\";s:1:\"8\";s:7:\"cleared\";s:2:\"17\";s:9:\"clearedID\";s:2:\"17\";s:9:\"paymentID\";s:1:\"5\";s:13:\"transactionID\";s:0:\"\";s:7:\"comment\";s:0:\"\";s:15:\"customercomment\";s:0:\"\";s:3:\"net\";s:1:\"0\";s:5:\"netto\";s:1:\"0\";s:9:\"partnerID\";s:0:\"\";s:11:\"temporaryID\";s:0:\"\";s:7:\"referer\";s:0:\"\";s:11:\"cleareddate\";N;s:12:\"cleared_date\";N;s:12:\"trackingcode\";s:0:\"\";s:8:\"language\";s:1:\"1\";s:8:\"currency\";s:3:\"EUR\";s:14:\"currencyFactor\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:10:\"dispatchID\";s:1:\"9\";s:10:\"currencyID\";s:1:\"1\";s:12:\"cleared_name\";s:4:\"open\";s:19:\"cleared_description\";s:5:\"Offen\";s:11:\"status_name\";s:22:\"clarification_required\";s:18:\"status_description\";s:18:\"Klärung notwendig\";s:19:\"payment_description\";s:8:\"Vorkasse\";s:20:\"dispatch_description\";s:16:\"Standard Versand\";s:20:\"currency_description\";s:4:\"Euro\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}s:13:\"sOrderDetails\";a:2:{i:0;a:20:{s:14:\"orderdetailsID\";s:3:\"208\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:3:\"152\";s:18:\"articleordernumber\";s:9:\"SW10152.1\";s:5:\"price\";s:5:\"29.95\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:5:\"29.95\";s:4:\"name\";s:31:\"WINDSTOPPER MÜTZE WARM Schwarz\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"0\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"1\";s:3:\"tax\";s:5:\"19.00\";s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"0\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";s:0:\"\";s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}i:1;a:20:{s:14:\"orderdetailsID\";s:3:\"209\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:1:\"0\";s:18:\"articleordernumber\";s:16:\"SHIPPINGDISCOUNT\";s:5:\"price\";s:2:\"-2\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:2:\"-2\";s:4:\"name\";s:15:\"Warenkorbrabatt\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"4\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"0\";s:3:\"tax\";N;s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"0\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}}s:5:\"sUser\";a:82:{s:15:\"billing_company\";s:0:\"\";s:18:\"billing_department\";s:0:\"\";s:18:\"billing_salutation\";s:2:\"mr\";s:14:\"customernumber\";s:5:\"20005\";s:17:\"billing_firstname\";s:3:\"Max\";s:16:\"billing_lastname\";s:10:\"Mustermann\";s:14:\"billing_street\";s:15:\"Musterstraße 1\";s:32:\"billing_additional_address_line1\";s:0:\"\";s:32:\"billing_additional_address_line2\";s:0:\"\";s:15:\"billing_zipcode\";s:5:\"12345\";s:12:\"billing_city\";s:11:\"Musterstadt\";s:5:\"phone\";s:0:\"\";s:13:\"billing_phone\";s:0:\"\";s:17:\"billing_countryID\";s:1:\"2\";s:15:\"billing_stateID\";N;s:15:\"billing_country\";s:11:\"Deutschland\";s:18:\"billing_countryiso\";s:2:\"DE\";s:19:\"billing_countryarea\";s:11:\"deutschland\";s:17:\"billing_countryen\";s:7:\"GERMANY\";s:5:\"ustid\";s:0:\"\";s:13:\"billing_text1\";N;s:13:\"billing_text2\";N;s:13:\"billing_text3\";N;s:13:\"billing_text4\";N;s:13:\"billing_text5\";N;s:13:\"billing_text6\";N;s:7:\"orderID\";s:2:\"59\";s:16:\"shipping_company\";s:0:\"\";s:19:\"shipping_department\";s:0:\"\";s:19:\"shipping_salutation\";s:2:\"mr\";s:18:\"shipping_firstname\";s:3:\"Max\";s:17:\"shipping_lastname\";s:10:\"Mustermann\";s:15:\"shipping_street\";s:15:\"Musterstraße 1\";s:33:\"shipping_additional_address_line1\";s:0:\"\";s:33:\"shipping_additional_address_line2\";s:0:\"\";s:16:\"shipping_zipcode\";s:5:\"12345\";s:13:\"shipping_city\";s:11:\"Musterstadt\";s:16:\"shipping_stateID\";N;s:18:\"shipping_countryID\";s:1:\"2\";s:16:\"shipping_country\";s:11:\"Deutschland\";s:19:\"shipping_countryiso\";s:2:\"DE\";s:20:\"shipping_countryarea\";s:11:\"deutschland\";s:18:\"shipping_countryen\";s:7:\"GERMANY\";s:14:\"shipping_text1\";N;s:14:\"shipping_text2\";N;s:14:\"shipping_text3\";N;s:14:\"shipping_text4\";N;s:14:\"shipping_text5\";N;s:14:\"shipping_text6\";N;s:2:\"id\";s:1:\"3\";s:8:\"password\";s:60:\"$2y$10$AzjwzOob83DJ2LG6yxXcBeghK9ciBB1zsK3UeBZADZCl10pTQN62W\";s:7:\"encoder\";s:6:\"bcrypt\";s:5:\"email\";s:14:\"xy@example.org\";s:6:\"active\";s:1:\"1\";s:11:\"accountmode\";s:1:\"0\";s:15:\"confirmationkey\";s:0:\"\";s:9:\"paymentID\";s:1:\"5\";s:10:\"firstlogin\";s:10:\"2017-08-07\";s:9:\"lastlogin\";s:19:\"2017-08-07 14:09:26\";s:9:\"sessionID\";s:26:\"hkkhfl82i1jejfvd2f0ucr6om4\";s:10:\"newsletter\";s:1:\"0\";s:10:\"validation\";s:0:\"\";s:9:\"affiliate\";s:1:\"0\";s:13:\"customergroup\";s:2:\"EK\";s:13:\"paymentpreset\";s:1:\"0\";s:8:\"language\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:7:\"referer\";s:0:\"\";s:12:\"pricegroupID\";N;s:15:\"internalcomment\";s:0:\"\";s:12:\"failedlogins\";s:1:\"0\";s:11:\"lockeduntil\";N;s:26:\"default_billing_address_id\";s:1:\"5\";s:27:\"default_shipping_address_id\";s:1:\"5\";s:5:\"title\";N;s:10:\"salutation\";s:2:\"mr\";s:9:\"firstname\";s:3:\"Max\";s:8:\"lastname\";s:10:\"Mustermann\";s:8:\"birthday\";N;s:11:\"login_token\";s:38:\"1239c089-6b2f-4461-9134-c02026970bff.1\";s:11:\"preisgruppe\";s:1:\"1\";s:11:\"billing_net\";s:1:\"1\";}s:9:\"sDispatch\";a:2:{s:4:\"name\";s:16:\"Standard Versand\";s:11:\"description\";s:0:\"\";}}',	0),
(35,	18,	'sORDERSTATEMAIL18',	'{config name=mail}',	'{config name=shopName}',	'Statusänderung zur Bestellung bei {config name=shopName}',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo {$sUser.billing_firstname} {$sUser.billing_lastname},\n\nder Zahlungsstatus für Ihre Bestellung {$sOrder.ordernumber} vom {$sOrder.ordertime|date_format:\"%d.%m.%Y\"} hat sich geändert!\nDie Bestellung hat jetzt den Zahlungsstatus: {$sOrder.cleared_description}.\n\nDen aktuellen Status Ihrer Bestellung können Sie auch jederzeit auf unserer Webseite im  Bereich \"Mein Konto\" - \"Meine Bestellungen\" abrufen. Sollten Sie allerdings den Kauf ohne Registrierung, also ohne Anlage eines Kundenkontos, gewählt haben, steht Ihnen diese Möglichkeit nicht zur Verfügung.\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n        Hallo {$sUser.billing_firstname} {$sUser.billing_lastname},<br/>\n        <br/>\n        der Zahlungsstatus für Ihre Bestellung {$sOrder.ordernumber} vom {$sOrder.ordertime|date_format:\"%d.%m.%Y\"} hat sich geändert!<br/>\n        <strong>Die Bestellung hat jetzt den Zahlungsstatus: {$sOrder.cleared_description}.</strong><br/>\n        <br/>\n        Den aktuellen Status Ihrer Bestellung können Sie auch jederzeit auf unserer Webseite im  Bereich \"Mein Konto\" - \"Meine Bestellungen\" abrufen. Sollten Sie allerdings den Kauf ohne Registrierung, also ohne Anlage eines Kundenkontos, gewählt haben, steht Ihnen diese Möglichkeit nicht zur Verfügung.\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	3,	'a:4:{s:6:\"sOrder\";a:40:{s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:12:\"order_number\";s:5:\"20003\";s:6:\"userID\";s:1:\"3\";s:10:\"customerID\";s:1:\"3\";s:14:\"invoice_amount\";s:5:\"31.85\";s:18:\"invoice_amount_net\";s:5:\"26.77\";s:16:\"invoice_shipping\";s:3:\"3.9\";s:20:\"invoice_shipping_net\";s:4:\"3.28\";s:9:\"ordertime\";s:19:\"2017-08-07 14:09:26\";s:6:\"status\";s:1:\"8\";s:8:\"statusID\";s:1:\"8\";s:7:\"cleared\";s:2:\"18\";s:9:\"clearedID\";s:2:\"18\";s:9:\"paymentID\";s:1:\"5\";s:13:\"transactionID\";s:0:\"\";s:7:\"comment\";s:0:\"\";s:15:\"customercomment\";s:0:\"\";s:3:\"net\";s:1:\"0\";s:5:\"netto\";s:1:\"0\";s:9:\"partnerID\";s:0:\"\";s:11:\"temporaryID\";s:0:\"\";s:7:\"referer\";s:0:\"\";s:11:\"cleareddate\";N;s:12:\"cleared_date\";N;s:12:\"trackingcode\";s:0:\"\";s:8:\"language\";s:1:\"1\";s:8:\"currency\";s:3:\"EUR\";s:14:\"currencyFactor\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:10:\"dispatchID\";s:1:\"9\";s:10:\"currencyID\";s:1:\"1\";s:12:\"cleared_name\";s:8:\"reserved\";s:19:\"cleared_description\";s:10:\"Reserviert\";s:11:\"status_name\";s:22:\"clarification_required\";s:18:\"status_description\";s:18:\"Klärung notwendig\";s:19:\"payment_description\";s:8:\"Vorkasse\";s:20:\"dispatch_description\";s:16:\"Standard Versand\";s:20:\"currency_description\";s:4:\"Euro\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}s:13:\"sOrderDetails\";a:2:{i:0;a:20:{s:14:\"orderdetailsID\";s:3:\"208\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:3:\"152\";s:18:\"articleordernumber\";s:9:\"SW10152.1\";s:5:\"price\";s:5:\"29.95\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:5:\"29.95\";s:4:\"name\";s:31:\"WINDSTOPPER MÜTZE WARM Schwarz\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"0\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"1\";s:3:\"tax\";s:5:\"19.00\";s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"0\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";s:0:\"\";s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}i:1;a:20:{s:14:\"orderdetailsID\";s:3:\"209\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:1:\"0\";s:18:\"articleordernumber\";s:16:\"SHIPPINGDISCOUNT\";s:5:\"price\";s:2:\"-2\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:2:\"-2\";s:4:\"name\";s:15:\"Warenkorbrabatt\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"4\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"0\";s:3:\"tax\";N;s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"0\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}}s:5:\"sUser\";a:82:{s:15:\"billing_company\";s:0:\"\";s:18:\"billing_department\";s:0:\"\";s:18:\"billing_salutation\";s:2:\"mr\";s:14:\"customernumber\";s:5:\"20005\";s:17:\"billing_firstname\";s:3:\"Max\";s:16:\"billing_lastname\";s:10:\"Mustermann\";s:14:\"billing_street\";s:15:\"Musterstraße 1\";s:32:\"billing_additional_address_line1\";s:0:\"\";s:32:\"billing_additional_address_line2\";s:0:\"\";s:15:\"billing_zipcode\";s:5:\"12345\";s:12:\"billing_city\";s:11:\"Musterstadt\";s:5:\"phone\";s:0:\"\";s:13:\"billing_phone\";s:0:\"\";s:17:\"billing_countryID\";s:1:\"2\";s:15:\"billing_stateID\";N;s:15:\"billing_country\";s:11:\"Deutschland\";s:18:\"billing_countryiso\";s:2:\"DE\";s:19:\"billing_countryarea\";s:11:\"deutschland\";s:17:\"billing_countryen\";s:7:\"GERMANY\";s:5:\"ustid\";s:0:\"\";s:13:\"billing_text1\";N;s:13:\"billing_text2\";N;s:13:\"billing_text3\";N;s:13:\"billing_text4\";N;s:13:\"billing_text5\";N;s:13:\"billing_text6\";N;s:7:\"orderID\";s:2:\"59\";s:16:\"shipping_company\";s:0:\"\";s:19:\"shipping_department\";s:0:\"\";s:19:\"shipping_salutation\";s:2:\"mr\";s:18:\"shipping_firstname\";s:3:\"Max\";s:17:\"shipping_lastname\";s:10:\"Mustermann\";s:15:\"shipping_street\";s:15:\"Musterstraße 1\";s:33:\"shipping_additional_address_line1\";s:0:\"\";s:33:\"shipping_additional_address_line2\";s:0:\"\";s:16:\"shipping_zipcode\";s:5:\"12345\";s:13:\"shipping_city\";s:11:\"Musterstadt\";s:16:\"shipping_stateID\";N;s:18:\"shipping_countryID\";s:1:\"2\";s:16:\"shipping_country\";s:11:\"Deutschland\";s:19:\"shipping_countryiso\";s:2:\"DE\";s:20:\"shipping_countryarea\";s:11:\"deutschland\";s:18:\"shipping_countryen\";s:7:\"GERMANY\";s:14:\"shipping_text1\";N;s:14:\"shipping_text2\";N;s:14:\"shipping_text3\";N;s:14:\"shipping_text4\";N;s:14:\"shipping_text5\";N;s:14:\"shipping_text6\";N;s:2:\"id\";s:1:\"3\";s:8:\"password\";s:60:\"$2y$10$AzjwzOob83DJ2LG6yxXcBeghK9ciBB1zsK3UeBZADZCl10pTQN62W\";s:7:\"encoder\";s:6:\"bcrypt\";s:5:\"email\";s:14:\"xy@example.org\";s:6:\"active\";s:1:\"1\";s:11:\"accountmode\";s:1:\"0\";s:15:\"confirmationkey\";s:0:\"\";s:9:\"paymentID\";s:1:\"5\";s:10:\"firstlogin\";s:10:\"2017-08-07\";s:9:\"lastlogin\";s:19:\"2017-08-07 14:09:26\";s:9:\"sessionID\";s:26:\"hkkhfl82i1jejfvd2f0ucr6om4\";s:10:\"newsletter\";s:1:\"0\";s:10:\"validation\";s:0:\"\";s:9:\"affiliate\";s:1:\"0\";s:13:\"customergroup\";s:2:\"EK\";s:13:\"paymentpreset\";s:1:\"0\";s:8:\"language\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:7:\"referer\";s:0:\"\";s:12:\"pricegroupID\";N;s:15:\"internalcomment\";s:0:\"\";s:12:\"failedlogins\";s:1:\"0\";s:11:\"lockeduntil\";N;s:26:\"default_billing_address_id\";s:1:\"5\";s:27:\"default_shipping_address_id\";s:1:\"5\";s:5:\"title\";N;s:10:\"salutation\";s:2:\"mr\";s:9:\"firstname\";s:3:\"Max\";s:8:\"lastname\";s:10:\"Mustermann\";s:8:\"birthday\";N;s:11:\"login_token\";s:38:\"1239c089-6b2f-4461-9134-c02026970bff.1\";s:11:\"preisgruppe\";s:1:\"1\";s:11:\"billing_net\";s:1:\"1\";}s:9:\"sDispatch\";a:2:{s:4:\"name\";s:16:\"Standard Versand\";s:11:\"description\";s:0:\"\";}}',	0),
(36,	19,	'sORDERSTATEMAIL19',	'{config name=mail}',	'{config name=shopName}',	'Verzögerung der Bestellung bei {config name=shopName}',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo {$sUser.billing_firstname} {$sUser.billing_lastname},\n\nder Zahlungsstatus für Ihre Bestellung {$sOrder.ordernumber} vom {$sOrder.ordertime|date_format:\"%d.%m.%Y\"} hat sich geändert!\nDie Bestellung hat jetzt den Zahlungsstatus: {$sOrder.cleared_description}.\n\nDen aktuellen Status Ihrer Bestellung können Sie auch jederzeit auf unserer Webseite im  Bereich \"Mein Konto\" - \"Meine Bestellungen\" abrufen. Sollten Sie allerdings den Kauf ohne Registrierung, also ohne Anlage eines Kundenkontos, gewählt haben, steht Ihnen diese Möglichkeit nicht zur Verfügung.\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n        Hallo {$sUser.billing_firstname} {$sUser.billing_lastname},<br/>\n        <br/>\n        der Zahlungsstatus für Ihre Bestellung {$sOrder.ordernumber} vom {$sOrder.ordertime|date_format:\"%d.%m.%Y\"} hat sich geändert!<br/>\n        <strong>Die Bestellung hat jetzt den Zahlungsstatus: {$sOrder.cleared_description}.</strong><br/>\n        <br/>\n        Den aktuellen Status Ihrer Bestellung können Sie auch jederzeit auf unserer Webseite im  Bereich \"Mein Konto\" - \"Meine Bestellungen\" abrufen. Sollten Sie allerdings den Kauf ohne Registrierung, also ohne Anlage eines Kundenkontos, gewählt haben, steht Ihnen diese Möglichkeit nicht zur Verfügung.\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	3,	'a:4:{s:6:\"sOrder\";a:40:{s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:12:\"order_number\";s:5:\"20003\";s:6:\"userID\";s:1:\"3\";s:10:\"customerID\";s:1:\"3\";s:14:\"invoice_amount\";s:5:\"31.85\";s:18:\"invoice_amount_net\";s:5:\"26.77\";s:16:\"invoice_shipping\";s:3:\"3.9\";s:20:\"invoice_shipping_net\";s:4:\"3.28\";s:9:\"ordertime\";s:19:\"2017-08-07 14:09:26\";s:6:\"status\";s:1:\"8\";s:8:\"statusID\";s:1:\"8\";s:7:\"cleared\";s:2:\"19\";s:9:\"clearedID\";s:2:\"19\";s:9:\"paymentID\";s:1:\"5\";s:13:\"transactionID\";s:0:\"\";s:7:\"comment\";s:0:\"\";s:15:\"customercomment\";s:0:\"\";s:3:\"net\";s:1:\"0\";s:5:\"netto\";s:1:\"0\";s:9:\"partnerID\";s:0:\"\";s:11:\"temporaryID\";s:0:\"\";s:7:\"referer\";s:0:\"\";s:11:\"cleareddate\";N;s:12:\"cleared_date\";N;s:12:\"trackingcode\";s:0:\"\";s:8:\"language\";s:1:\"1\";s:8:\"currency\";s:3:\"EUR\";s:14:\"currencyFactor\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:10:\"dispatchID\";s:1:\"9\";s:10:\"currencyID\";s:1:\"1\";s:12:\"cleared_name\";s:7:\"delayed\";s:19:\"cleared_description\";s:10:\"Verzoegert\";s:11:\"status_name\";s:22:\"clarification_required\";s:18:\"status_description\";s:18:\"Klärung notwendig\";s:19:\"payment_description\";s:8:\"Vorkasse\";s:20:\"dispatch_description\";s:16:\"Standard Versand\";s:20:\"currency_description\";s:4:\"Euro\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}s:13:\"sOrderDetails\";a:2:{i:0;a:20:{s:14:\"orderdetailsID\";s:3:\"208\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:3:\"152\";s:18:\"articleordernumber\";s:9:\"SW10152.1\";s:5:\"price\";s:5:\"29.95\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:5:\"29.95\";s:4:\"name\";s:31:\"WINDSTOPPER MÜTZE WARM Schwarz\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"0\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"1\";s:3:\"tax\";s:5:\"19.00\";s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"0\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";s:0:\"\";s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}i:1;a:20:{s:14:\"orderdetailsID\";s:3:\"209\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:1:\"0\";s:18:\"articleordernumber\";s:16:\"SHIPPINGDISCOUNT\";s:5:\"price\";s:2:\"-2\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:2:\"-2\";s:4:\"name\";s:15:\"Warenkorbrabatt\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"4\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"0\";s:3:\"tax\";N;s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"0\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}}s:5:\"sUser\";a:82:{s:15:\"billing_company\";s:0:\"\";s:18:\"billing_department\";s:0:\"\";s:18:\"billing_salutation\";s:2:\"mr\";s:14:\"customernumber\";s:5:\"20005\";s:17:\"billing_firstname\";s:3:\"Max\";s:16:\"billing_lastname\";s:10:\"Mustermann\";s:14:\"billing_street\";s:15:\"Musterstraße 1\";s:32:\"billing_additional_address_line1\";s:0:\"\";s:32:\"billing_additional_address_line2\";s:0:\"\";s:15:\"billing_zipcode\";s:5:\"12345\";s:12:\"billing_city\";s:11:\"Musterstadt\";s:5:\"phone\";s:0:\"\";s:13:\"billing_phone\";s:0:\"\";s:17:\"billing_countryID\";s:1:\"2\";s:15:\"billing_stateID\";N;s:15:\"billing_country\";s:11:\"Deutschland\";s:18:\"billing_countryiso\";s:2:\"DE\";s:19:\"billing_countryarea\";s:11:\"deutschland\";s:17:\"billing_countryen\";s:7:\"GERMANY\";s:5:\"ustid\";s:0:\"\";s:13:\"billing_text1\";N;s:13:\"billing_text2\";N;s:13:\"billing_text3\";N;s:13:\"billing_text4\";N;s:13:\"billing_text5\";N;s:13:\"billing_text6\";N;s:7:\"orderID\";s:2:\"59\";s:16:\"shipping_company\";s:0:\"\";s:19:\"shipping_department\";s:0:\"\";s:19:\"shipping_salutation\";s:2:\"mr\";s:18:\"shipping_firstname\";s:3:\"Max\";s:17:\"shipping_lastname\";s:10:\"Mustermann\";s:15:\"shipping_street\";s:15:\"Musterstraße 1\";s:33:\"shipping_additional_address_line1\";s:0:\"\";s:33:\"shipping_additional_address_line2\";s:0:\"\";s:16:\"shipping_zipcode\";s:5:\"12345\";s:13:\"shipping_city\";s:11:\"Musterstadt\";s:16:\"shipping_stateID\";N;s:18:\"shipping_countryID\";s:1:\"2\";s:16:\"shipping_country\";s:11:\"Deutschland\";s:19:\"shipping_countryiso\";s:2:\"DE\";s:20:\"shipping_countryarea\";s:11:\"deutschland\";s:18:\"shipping_countryen\";s:7:\"GERMANY\";s:14:\"shipping_text1\";N;s:14:\"shipping_text2\";N;s:14:\"shipping_text3\";N;s:14:\"shipping_text4\";N;s:14:\"shipping_text5\";N;s:14:\"shipping_text6\";N;s:2:\"id\";s:1:\"3\";s:8:\"password\";s:60:\"$2y$10$AzjwzOob83DJ2LG6yxXcBeghK9ciBB1zsK3UeBZADZCl10pTQN62W\";s:7:\"encoder\";s:6:\"bcrypt\";s:5:\"email\";s:14:\"xy@example.org\";s:6:\"active\";s:1:\"1\";s:11:\"accountmode\";s:1:\"0\";s:15:\"confirmationkey\";s:0:\"\";s:9:\"paymentID\";s:1:\"5\";s:10:\"firstlogin\";s:10:\"2017-08-07\";s:9:\"lastlogin\";s:19:\"2017-08-07 14:09:26\";s:9:\"sessionID\";s:26:\"hkkhfl82i1jejfvd2f0ucr6om4\";s:10:\"newsletter\";s:1:\"0\";s:10:\"validation\";s:0:\"\";s:9:\"affiliate\";s:1:\"0\";s:13:\"customergroup\";s:2:\"EK\";s:13:\"paymentpreset\";s:1:\"0\";s:8:\"language\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:7:\"referer\";s:0:\"\";s:12:\"pricegroupID\";N;s:15:\"internalcomment\";s:0:\"\";s:12:\"failedlogins\";s:1:\"0\";s:11:\"lockeduntil\";N;s:26:\"default_billing_address_id\";s:1:\"5\";s:27:\"default_shipping_address_id\";s:1:\"5\";s:5:\"title\";N;s:10:\"salutation\";s:2:\"mr\";s:9:\"firstname\";s:3:\"Max\";s:8:\"lastname\";s:10:\"Mustermann\";s:8:\"birthday\";N;s:11:\"login_token\";s:38:\"1239c089-6b2f-4461-9134-c02026970bff.1\";s:11:\"preisgruppe\";s:1:\"1\";s:11:\"billing_net\";s:1:\"1\";}s:9:\"sDispatch\";a:2:{s:4:\"name\";s:16:\"Standard Versand\";s:11:\"description\";s:0:\"\";}}',	0),
(37,	20,	'sORDERSTATEMAIL20',	'{config name=mail}',	'{config name=shopName}',	'Wiedergutschrift der Bestellung bei {config name=shopName}',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo {$sUser.billing_firstname} {$sUser.billing_lastname},\n\nder Zahlungsstatus für Ihre Bestellung {$sOrder.ordernumber} vom {$sOrder.ordertime|date_format:\"%d.%m.%Y\"} hat sich geändert!\nDie Bestellung hat jetzt den Zahlungsstatus: {$sOrder.cleared_description}.\n\nDen aktuellen Status Ihrer Bestellung können Sie auch jederzeit auf unserer Webseite im  Bereich \"Mein Konto\" - \"Meine Bestellungen\" abrufen. Sollten Sie allerdings den Kauf ohne Registrierung, also ohne Anlage eines Kundenkontos, gewählt haben, steht Ihnen diese Möglichkeit nicht zur Verfügung.\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n        Hallo {$sUser.billing_firstname} {$sUser.billing_lastname},<br/>\n        <br/>\n        der Zahlungsstatus für Ihre Bestellung {$sOrder.ordernumber} vom {$sOrder.ordertime|date_format:\"%d.%m.%Y\"} hat sich geändert!<br/>\n        <strong>Die Bestellung hat jetzt den Zahlungsstatus: {$sOrder.cleared_description}.</strong><br/>\n        <br/>\n        Den aktuellen Status Ihrer Bestellung können Sie auch jederzeit auf unserer Webseite im  Bereich \"Mein Konto\" - \"Meine Bestellungen\" abrufen. Sollten Sie allerdings den Kauf ohne Registrierung, also ohne Anlage eines Kundenkontos, gewählt haben, steht Ihnen diese Möglichkeit nicht zur Verfügung.\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	3,	'a:4:{s:6:\"sOrder\";a:40:{s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:12:\"order_number\";s:5:\"20003\";s:6:\"userID\";s:1:\"3\";s:10:\"customerID\";s:1:\"3\";s:14:\"invoice_amount\";s:5:\"31.85\";s:18:\"invoice_amount_net\";s:5:\"26.77\";s:16:\"invoice_shipping\";s:3:\"3.9\";s:20:\"invoice_shipping_net\";s:4:\"3.28\";s:9:\"ordertime\";s:19:\"2017-08-07 14:09:26\";s:6:\"status\";s:1:\"8\";s:8:\"statusID\";s:1:\"8\";s:7:\"cleared\";s:2:\"20\";s:9:\"clearedID\";s:2:\"20\";s:9:\"paymentID\";s:1:\"5\";s:13:\"transactionID\";s:0:\"\";s:7:\"comment\";s:0:\"\";s:15:\"customercomment\";s:0:\"\";s:3:\"net\";s:1:\"0\";s:5:\"netto\";s:1:\"0\";s:9:\"partnerID\";s:0:\"\";s:11:\"temporaryID\";s:0:\"\";s:7:\"referer\";s:0:\"\";s:11:\"cleareddate\";N;s:12:\"cleared_date\";N;s:12:\"trackingcode\";s:0:\"\";s:8:\"language\";s:1:\"1\";s:8:\"currency\";s:3:\"EUR\";s:14:\"currencyFactor\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:10:\"dispatchID\";s:1:\"9\";s:10:\"currencyID\";s:1:\"1\";s:12:\"cleared_name\";s:12:\"re_crediting\";s:19:\"cleared_description\";s:16:\"Wiedergutschrift\";s:11:\"status_name\";s:22:\"clarification_required\";s:18:\"status_description\";s:18:\"Klärung notwendig\";s:19:\"payment_description\";s:8:\"Vorkasse\";s:20:\"dispatch_description\";s:16:\"Standard Versand\";s:20:\"currency_description\";s:4:\"Euro\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}s:13:\"sOrderDetails\";a:2:{i:0;a:20:{s:14:\"orderdetailsID\";s:3:\"208\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:3:\"152\";s:18:\"articleordernumber\";s:9:\"SW10152.1\";s:5:\"price\";s:5:\"29.95\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:5:\"29.95\";s:4:\"name\";s:31:\"WINDSTOPPER MÜTZE WARM Schwarz\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"0\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"1\";s:3:\"tax\";s:5:\"19.00\";s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"0\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";s:0:\"\";s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}i:1;a:20:{s:14:\"orderdetailsID\";s:3:\"209\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:1:\"0\";s:18:\"articleordernumber\";s:16:\"SHIPPINGDISCOUNT\";s:5:\"price\";s:2:\"-2\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:2:\"-2\";s:4:\"name\";s:15:\"Warenkorbrabatt\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"4\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"0\";s:3:\"tax\";N;s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"0\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}}s:5:\"sUser\";a:82:{s:15:\"billing_company\";s:0:\"\";s:18:\"billing_department\";s:0:\"\";s:18:\"billing_salutation\";s:2:\"mr\";s:14:\"customernumber\";s:5:\"20005\";s:17:\"billing_firstname\";s:3:\"Max\";s:16:\"billing_lastname\";s:10:\"Mustermann\";s:14:\"billing_street\";s:15:\"Musterstraße 1\";s:32:\"billing_additional_address_line1\";s:0:\"\";s:32:\"billing_additional_address_line2\";s:0:\"\";s:15:\"billing_zipcode\";s:5:\"12345\";s:12:\"billing_city\";s:11:\"Musterstadt\";s:5:\"phone\";s:0:\"\";s:13:\"billing_phone\";s:0:\"\";s:17:\"billing_countryID\";s:1:\"2\";s:15:\"billing_stateID\";N;s:15:\"billing_country\";s:11:\"Deutschland\";s:18:\"billing_countryiso\";s:2:\"DE\";s:19:\"billing_countryarea\";s:11:\"deutschland\";s:17:\"billing_countryen\";s:7:\"GERMANY\";s:5:\"ustid\";s:0:\"\";s:13:\"billing_text1\";N;s:13:\"billing_text2\";N;s:13:\"billing_text3\";N;s:13:\"billing_text4\";N;s:13:\"billing_text5\";N;s:13:\"billing_text6\";N;s:7:\"orderID\";s:2:\"59\";s:16:\"shipping_company\";s:0:\"\";s:19:\"shipping_department\";s:0:\"\";s:19:\"shipping_salutation\";s:2:\"mr\";s:18:\"shipping_firstname\";s:3:\"Max\";s:17:\"shipping_lastname\";s:10:\"Mustermann\";s:15:\"shipping_street\";s:15:\"Musterstraße 1\";s:33:\"shipping_additional_address_line1\";s:0:\"\";s:33:\"shipping_additional_address_line2\";s:0:\"\";s:16:\"shipping_zipcode\";s:5:\"12345\";s:13:\"shipping_city\";s:11:\"Musterstadt\";s:16:\"shipping_stateID\";N;s:18:\"shipping_countryID\";s:1:\"2\";s:16:\"shipping_country\";s:11:\"Deutschland\";s:19:\"shipping_countryiso\";s:2:\"DE\";s:20:\"shipping_countryarea\";s:11:\"deutschland\";s:18:\"shipping_countryen\";s:7:\"GERMANY\";s:14:\"shipping_text1\";N;s:14:\"shipping_text2\";N;s:14:\"shipping_text3\";N;s:14:\"shipping_text4\";N;s:14:\"shipping_text5\";N;s:14:\"shipping_text6\";N;s:2:\"id\";s:1:\"3\";s:8:\"password\";s:60:\"$2y$10$AzjwzOob83DJ2LG6yxXcBeghK9ciBB1zsK3UeBZADZCl10pTQN62W\";s:7:\"encoder\";s:6:\"bcrypt\";s:5:\"email\";s:14:\"xy@example.org\";s:6:\"active\";s:1:\"1\";s:11:\"accountmode\";s:1:\"0\";s:15:\"confirmationkey\";s:0:\"\";s:9:\"paymentID\";s:1:\"5\";s:10:\"firstlogin\";s:10:\"2017-08-07\";s:9:\"lastlogin\";s:19:\"2017-08-07 14:09:26\";s:9:\"sessionID\";s:26:\"hkkhfl82i1jejfvd2f0ucr6om4\";s:10:\"newsletter\";s:1:\"0\";s:10:\"validation\";s:0:\"\";s:9:\"affiliate\";s:1:\"0\";s:13:\"customergroup\";s:2:\"EK\";s:13:\"paymentpreset\";s:1:\"0\";s:8:\"language\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:7:\"referer\";s:0:\"\";s:12:\"pricegroupID\";N;s:15:\"internalcomment\";s:0:\"\";s:12:\"failedlogins\";s:1:\"0\";s:11:\"lockeduntil\";N;s:26:\"default_billing_address_id\";s:1:\"5\";s:27:\"default_shipping_address_id\";s:1:\"5\";s:5:\"title\";N;s:10:\"salutation\";s:2:\"mr\";s:9:\"firstname\";s:3:\"Max\";s:8:\"lastname\";s:10:\"Mustermann\";s:8:\"birthday\";N;s:11:\"login_token\";s:38:\"1239c089-6b2f-4461-9134-c02026970bff.1\";s:11:\"preisgruppe\";s:1:\"1\";s:11:\"billing_net\";s:1:\"1\";}s:9:\"sDispatch\";a:2:{s:4:\"name\";s:16:\"Standard Versand\";s:11:\"description\";s:0:\"\";}}',	0),
(40,	NULL,	'sARTICLESTOCK',	'{config name=mail}',	'{config name=shopName}',	'Lagerbestand von {$sData.count} Artikel{if $sData.count>1}n{/if} unter Mindestbestand ',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo,\n\nfolgende Artikel haben den Mindestbestand unterschritten:\n\nBestellnummer     Artikelname    Bestand/Mindestbestand\n{foreach from=$sJob.articles item=sArticle key=key}\n{$sArticle.ordernumber}       {$sArticle.name}        {$sArticle.instock}/{$sArticle.stockmin}\n{/foreach}\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n        Hallo,<br/>\n        <br/>\n        folgende Artikel haben den Mindestbestand unterschritten:<br/>\n    </p>\n    <table width=\"80%\" border=\"0\" style=\"font-family:Arial, Helvetica, sans-serif; font-size:12px;\">\n        <tr>\n            <td bgcolor=\"#F7F7F2\" style=\"border-bottom:1px solid #cccccc;\"><strong>Bestellnummer</strong></td>\n            <td bgcolor=\"#F7F7F2\" style=\"border-bottom:1px solid #cccccc;\"><strong>Artikelname</strong></td>\n            <td bgcolor=\"#F7F7F2\" style=\"border-bottom:1px solid #cccccc;\"><strong>Bestand/Mindestbestand</strong></td>\n        </tr>\n    \n        {foreach from=$sJob.articles item=sArticle key=key}\n            <tr>\n                <td>{$sArticle.ordernumber}</td>\n                <td>{$sArticle.name}</td>\n                <td>{$sArticle.instock}/{$sArticle.stockmin}</td>\n            </tr>\n        {/foreach}\n    </table>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>\n',	1,	'',	2,	'a:2:{s:5:\"sData\";a:2:{s:5:\"count\";i:1;s:7:\"numbers\";a:1:{i:2;s:10:\"SW10002841\";}}s:4:\"sJob\";a:1:{s:8:\"articles\";a:1:{s:7:\"SW10200\";a:48:{s:11:\"ordernumber\";s:7:\"SW10200\";s:2:\"id\";s:3:\"441\";s:9:\"articleID\";s:3:\"201\";s:6:\"unitID\";N;s:4:\"name\";s:26:\"Hervorgehobene Darstellung\";s:11:\"description\";s:139:\"Über diese Option lassen sich Artikel in der Storefront besonders kennzeichnen. Standardmäßig werden diese Artikel als \"Tipp\" angezeigt.\";s:16:\"description_long\";s:172:\"<p><span>&Uuml;ber diese Option lassen sich Artikel in der Storefront besonders kennzeichnen. Standardm&auml;&szlig;ig werden diese Artikel als \"Tipp\" angezeigt.</span></p>\";s:12:\"shippingtime\";N;s:5:\"added\";s:10:\"2012-07-16\";s:9:\"topseller\";s:1:\"1\";s:8:\"keywords\";s:0:\"\";s:5:\"taxID\";s:1:\"1\";s:10:\"supplierID\";s:2:\"14\";s:7:\"changed\";s:19:\"2012-08-30 16:17:44\";s:16:\"articledetailsID\";s:3:\"441\";s:14:\"suppliernumber\";s:0:\"\";s:4:\"kind\";s:1:\"1\";s:14:\"additionaltext\";s:0:\"\";s:11:\"impressions\";s:1:\"0\";s:5:\"sales\";s:1:\"0\";s:6:\"active\";s:1:\"1\";s:7:\"instock\";s:1:\"0\";s:8:\"stockmin\";s:2:\"96\";s:6:\"weight\";s:5:\"0.000\";s:8:\"position\";s:1:\"0\";s:5:\"attr1\";s:0:\"\";s:5:\"attr2\";s:0:\"\";s:5:\"attr3\";s:0:\"\";s:5:\"attr4\";s:0:\"\";s:5:\"attr5\";s:0:\"\";s:5:\"attr6\";s:0:\"\";s:5:\"attr7\";s:0:\"\";s:5:\"attr8\";s:0:\"\";s:5:\"attr9\";s:0:\"\";s:6:\"attr10\";s:0:\"\";s:6:\"attr11\";s:0:\"\";s:6:\"attr12\";s:0:\"\";s:6:\"attr13\";s:0:\"\";s:6:\"attr14\";s:0:\"\";s:6:\"attr15\";s:0:\"\";s:6:\"attr16\";s:0:\"\";s:6:\"attr17\";N;s:6:\"attr18\";s:0:\"\";s:6:\"attr19\";s:0:\"\";s:6:\"attr20\";s:0:\"\";s:8:\"supplier\";s:7:\"Example\";s:4:\"unit\";N;s:3:\"tax\";s:5:\"19.00\";}}}}',	0),
(41,	NULL,	'sNEWSLETTERCONFIRMATION',	'{config name=mail}',	'{config name=shopName}',	'Vielen Dank für Ihre Newsletter-Anmeldung',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo,\n\nvielen Dank für Ihre Newsletter-Anmeldung bei {config name=shopName}.\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n        Hallo,<br/>\n        <br/>\n        vielen Dank für Ihre Newsletter-Anmeldung bei {config name=shopName}.\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	2,	'a:9:{s:27:\"sUser.subscribeToNewsletter\";s:1:\"1\";s:16:\"sUser.newsletter\";s:0:\"\";s:16:\"sUser.salutation\";s:4:\"Herr\";s:15:\"sUser.firstname\";s:3:\"Max\";s:14:\"sUser.lastname\";s:10:\"Mustermann\";s:12:\"sUser.street\";s:0:\"\";s:13:\"sUser.zipcode\";s:0:\"\";s:10:\"sUser.city\";s:0:\"\";s:15:\"sUser.Speichern\";s:0:\"\";}',	0),
(42,	NULL,	'sOPTINNEWSLETTER',	'{config name=mail}',	'{config name=shopName}',	'Bitte bestätigen Sie Ihre Newsletter-Anmeldung',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo,\n\nvielen Dank für Ihre Anmeldung zu unserem regelmäßig erscheinenden Newsletter.\nBitte bestätigen Sie die Anmeldung über den nachfolgenden Link:\n\n{$sConfirmLink}\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n        Hallo,<br/>\n        <br/>\n        vielen Dank für Ihre Anmeldung zu unserem regelmäßig erscheinenden Newsletter.<br/>\n        Bitte bestätigen Sie die Anmeldung über den nachfolgenden Link:<br/>\n        <br/>\n        <a href=\"{$sConfirmLink}\">Bestätigen</a>\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	2,	'a:10:{s:12:\"sConfirmLink\";s:24:\"http://shopware.example/\";s:27:\"sUser.subscribeToNewsletter\";s:1:\"1\";s:16:\"sUser.newsletter\";s:0:\"\";s:16:\"sUser.salutation\";s:0:\"\";s:15:\"sUser.firstname\";s:0:\"\";s:14:\"sUser.lastname\";s:0:\"\";s:12:\"sUser.street\";s:0:\"\";s:13:\"sUser.zipcode\";s:0:\"\";s:10:\"sUser.city\";s:0:\"\";s:15:\"sUser.Speichern\";s:0:\"\";}',	0),
(43,	NULL,	'sOPTINVOTE',	'{config name=mail}',	'{config name=shopName}',	'Bitte bestätigen Sie Ihre Artikel-Bewertung',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo,\n\nvielen Dank für die Bewertung des Artikels {$sArticle.articleName}.\nBitte bestätigen Sie die Bewertung über den nachfolgenden Link:\n\n{$sConfirmLink}\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n        Hallo,<br/>\n        <br/>\n        vielen Dank für die Bewertung des Artikels {$sArticle.articleName}.<br/>\n        Bitte bestätigen Sie die Bewertung über nach den nachfolgenden Link:<br/>\n        <br/>\n        <a href=\"{$sConfirmLink}\">Artikelbewertung bestätigen</a>\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	2,	'a:2:{s:12:\"sConfirmLink\";s:133:\"http://shopware.example/craft-tradition/men/business-bags/165/die-zeit-5?action=rating&sConfirmation=6avE5xLF22DTp8gNPaZ8KRUfJhflnvU9\";s:8:\"sArticle\";a:1:{s:11:\"articleName\";s:24:\"DIE ZEIT 5 Cowhide mokka\";}}',	0),
(44,	NULL,	'sARTICLEAVAILABLE',	'{config name=mail}',	'{config name=shopName}',	'Ihr Artikel ist wieder verfügbar',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo,\n\nIhr Artikel mit der Bestellnummer {$sOrdernumber} ist jetzt wieder verfügbar.\n\n{$sArticleLink}\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n   {include file=\"string:{config name=emailheaderhtml}\"}\n   <br/><br/>\n    <p>\n        Hallo,<br/>\n        <br/>\n        Ihr Artikel mit der Bestellnummer {$sOrdernumber} ist jetzt wieder verfügbar.<br/>\n        <br/>\n        <a href=\"{$sArticleLink}\">{$sOrdernumber}</a>\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	2,	'a:5:{s:11:\"sNotifyData\";a:5:{s:2:\"id\";s:1:\"1\";s:11:\"ordernumber\";s:7:\"SW10239\";s:4:\"mail\";s:12:\"test@test.de\";s:8:\"language\";s:1:\"1\";s:9:\"attribute\";a:2:{s:2:\"id\";s:1:\"1\";s:14:\"notificationID\";s:1:\"1\";}}s:12:\"sArticleLink\";s:91:\"http://shopware.dev.localhost/genusswelten/koestlichkeiten/272/spachtelmasse?number=SW10239\";s:12:\"sOrdernumber\";s:7:\"SW10239\";s:5:\"sData\";N;s:7:\"product\";a:94:{s:9:\"articleID\";i:272;s:16:\"articleDetailsID\";i:827;s:11:\"ordernumber\";s:7:\"SW10239\";s:9:\"highlight\";b:0;s:11:\"description\";s:0:\"\";s:16:\"description_long\";s:406:\"<p>qui dux somniculosus lascivio vel res compendiose Oriens propitius, alo ita pax galactinus emo. Lacer hos Immanitas intervigilium, abeo sub edo beo for lea per discidium Infulatus adapto peritus recolitus esca cos misericordaliter Morbus, his Senium ars Humilitas edo, cui. Sis sacrilegus Fatigo almus vae excedo, aut vegetabiliter Erogo villa periclitatus, for in per no sors capulus se Quies, mox.</p>\";s:3:\"esd\";b:0;s:11:\"articleName\";s:13:\"Spachtelmasse\";s:5:\"taxID\";i:4;s:3:\"tax\";i:7;s:7:\"instock\";i:5555;s:11:\"isAvailable\";b:1;s:19:\"hasAvailableVariant\";b:1;s:6:\"weight\";i:0;s:12:\"shippingtime\";N;s:16:\"pricegroupActive\";b:0;s:12:\"pricegroupID\";N;s:6:\"length\";i:0;s:6:\"height\";i:0;s:5:\"width\";i:0;s:9:\"laststock\";b:0;s:14:\"additionaltext\";s:0:\"\";s:5:\"datum\";s:10:\"2012-08-31\";s:6:\"update\";s:10:\"2018-11-06\";s:5:\"sales\";i:0;s:13:\"filtergroupID\";N;s:17:\"priceStartingFrom\";N;s:18:\"pseudopricePercent\";N;s:15:\"sVariantArticle\";N;s:13:\"sConfigurator\";b:0;s:9:\"metaTitle\";s:0:\"\";s:12:\"shippingfree\";b:0;s:14:\"suppliernumber\";s:0:\"\";s:12:\"notification\";b:1;s:3:\"ean\";s:0:\"\";s:8:\"keywords\";s:0:\"\";s:12:\"sReleasedate\";s:0:\"\";s:8:\"template\";s:0:\"\";s:10:\"attributes\";a:2:{s:4:\"core\";a:23:{s:2:\"id\";s:3:\"865\";s:9:\"articleID\";s:3:\"272\";s:16:\"articledetailsID\";s:3:\"827\";s:5:\"attr1\";s:0:\"\";s:5:\"attr2\";s:0:\"\";s:5:\"attr3\";s:0:\"\";s:5:\"attr4\";s:0:\"\";s:5:\"attr5\";s:0:\"\";s:5:\"attr6\";s:0:\"\";s:5:\"attr7\";s:0:\"\";s:5:\"attr8\";s:0:\"\";s:5:\"attr9\";s:0:\"\";s:6:\"attr10\";s:0:\"\";s:6:\"attr11\";s:0:\"\";s:6:\"attr12\";s:0:\"\";s:6:\"attr13\";s:0:\"\";s:6:\"attr14\";s:0:\"\";s:6:\"attr15\";s:0:\"\";s:6:\"attr16\";s:0:\"\";s:6:\"attr17\";N;s:6:\"attr18\";s:0:\"\";s:6:\"attr19\";s:0:\"\";s:6:\"attr20\";s:0:\"\";}s:9:\"marketing\";a:4:{s:5:\"isNew\";b:0;s:11:\"isTopSeller\";b:0;s:10:\"comingSoon\";b:0;s:7:\"storage\";a:0:{}}}s:17:\"allowBuyInListing\";b:1;s:5:\"attr1\";s:0:\"\";s:5:\"attr2\";s:0:\"\";s:5:\"attr3\";s:0:\"\";s:5:\"attr4\";s:0:\"\";s:5:\"attr5\";s:0:\"\";s:5:\"attr6\";s:0:\"\";s:5:\"attr7\";s:0:\"\";s:5:\"attr8\";s:0:\"\";s:5:\"attr9\";s:0:\"\";s:6:\"attr10\";s:0:\"\";s:6:\"attr11\";s:0:\"\";s:6:\"attr12\";s:0:\"\";s:6:\"attr13\";s:0:\"\";s:6:\"attr14\";s:0:\"\";s:6:\"attr15\";s:0:\"\";s:6:\"attr16\";s:0:\"\";s:6:\"attr17\";N;s:6:\"attr18\";s:0:\"\";s:6:\"attr19\";s:0:\"\";s:6:\"attr20\";s:0:\"\";s:12:\"supplierName\";s:15:\"The Deli Garage\";s:11:\"supplierImg\";s:61:\"http://shopware.localhost/media/image/70/ff/d6/deligarage.png\";s:10:\"supplierID\";i:4;s:19:\"supplierDescription\";s:0:\"\";s:19:\"supplier_attributes\";a:0:{}s:10:\"newArticle\";b:0;s:9:\"sUpcoming\";b:0;s:9:\"topseller\";b:0;s:7:\"valFrom\";i:1;s:5:\"valTo\";N;s:4:\"from\";i:1;s:2:\"to\";N;s:5:\"price\";s:5:\"17,08\";s:11:\"pseudoprice\";s:1:\"0\";s:14:\"referenceprice\";s:1:\"0\";s:15:\"has_pseudoprice\";b:0;s:13:\"price_numeric\";d:17.08;s:19:\"pseudoprice_numeric\";i:0;s:16:\"price_attributes\";a:0:{}s:10:\"pricegroup\";s:2:\"EK\";s:11:\"minpurchase\";i:1;s:11:\"maxpurchase\";s:3:\"100\";s:13:\"purchasesteps\";i:1;s:12:\"purchaseunit\";N;s:13:\"referenceunit\";N;s:8:\"packunit\";s:0:\"\";s:6:\"unitID\";N;s:5:\"sUnit\";a:2:{s:4:\"unit\";N;s:11:\"description\";N;}s:15:\"unit_attributes\";a:0:{}s:5:\"image\";a:12:{s:2:\"id\";i:769;s:8:\"position\";N;s:6:\"source\";s:64:\"http://shopware.localhost/media/image/91/ee/35/spachtelmasse.jpg\";s:11:\"description\";s:0:\"\";s:9:\"extension\";s:3:\"jpg\";s:4:\"main\";b:1;s:8:\"parentId\";N;s:5:\"width\";i:380;s:6:\"height\";i:276;s:10:\"thumbnails\";a:3:{i:0;a:6:{s:6:\"source\";s:72:\"http://shopware.localhost/media/image/0e/8e/f0/spachtelmasse_200x200.jpg\";s:12:\"retinaSource\";s:75:\"http://shopware.localhost/media/image/c5/fe/f6/spachtelmasse_200x200@2x.jpg\";s:9:\"sourceSet\";s:152:\"http://shopware.localhost/media/image/0e/8e/f0/spachtelmasse_200x200.jpg, http://shopware.localhost/media/image/c5/fe/f6/spachtelmasse_200x200@2x.jpg 2x\";s:8:\"maxWidth\";s:3:\"200\";s:9:\"maxHeight\";s:3:\"200\";s:10:\"attributes\";a:0:{}}i:1;a:6:{s:6:\"source\";s:72:\"http://shopware.localhost/media/image/96/0c/99/spachtelmasse_600x600.jpg\";s:12:\"retinaSource\";s:75:\"http://shopware.localhost/media/image/05/70/1e/spachtelmasse_600x600@2x.jpg\";s:9:\"sourceSet\";s:152:\"http://shopware.localhost/media/image/96/0c/99/spachtelmasse_600x600.jpg, http://shopware.localhost/media/image/05/70/1e/spachtelmasse_600x600@2x.jpg 2x\";s:8:\"maxWidth\";s:3:\"600\";s:9:\"maxHeight\";s:3:\"600\";s:10:\"attributes\";a:0:{}}i:2;a:6:{s:6:\"source\";s:74:\"http://shopware.localhost/media/image/28/g0/7e/spachtelmasse_1280x1280.jpg\";s:12:\"retinaSource\";s:77:\"http://shopware.localhost/media/image/6e/5f/c6/spachtelmasse_1280x1280@2x.jpg\";s:9:\"sourceSet\";s:156:\"http://shopware.localhost/media/image/28/g0/7e/spachtelmasse_1280x1280.jpg, http://shopware.localhost/media/image/6e/5f/c6/spachtelmasse_1280x1280@2x.jpg 2x\";s:8:\"maxWidth\";s:4:\"1280\";s:9:\"maxHeight\";s:4:\"1280\";s:10:\"attributes\";a:0:{}}}s:10:\"attributes\";a:0:{}s:9:\"attribute\";a:0:{}}s:6:\"prices\";a:1:{i:0;a:22:{s:7:\"valFrom\";i:1;s:5:\"valTo\";N;s:4:\"from\";i:1;s:2:\"to\";N;s:5:\"price\";s:5:\"17,08\";s:11:\"pseudoprice\";s:1:\"0\";s:14:\"referenceprice\";s:1:\"0\";s:18:\"pseudopricePercent\";N;s:15:\"has_pseudoprice\";b:0;s:13:\"price_numeric\";d:17.08;s:19:\"pseudoprice_numeric\";i:0;s:16:\"price_attributes\";a:0:{}s:10:\"pricegroup\";s:2:\"EK\";s:11:\"minpurchase\";i:1;s:11:\"maxpurchase\";s:3:\"100\";s:13:\"purchasesteps\";i:1;s:12:\"purchaseunit\";N;s:13:\"referenceunit\";N;s:8:\"packunit\";s:0:\"\";s:6:\"unitID\";N;s:5:\"sUnit\";a:2:{s:4:\"unit\";N;s:11:\"description\";N;}s:15:\"unit_attributes\";a:0:{}}}s:10:\"linkBasket\";s:42:\"shopware.php?sViewport=basket&sAdd=SW10239\";s:11:\"linkDetails\";s:42:\"shopware.php?sViewport=detail&sArticle=272\";s:11:\"linkVariant\";s:57:\"shopware.php?sViewport=detail&sArticle=272&number=SW10239\";}}',	0),
(45,	NULL,	'sACCEPTNOTIFICATION',	'{config name=mail}',	'{config name=shopName}',	'Bitte bestätigen Sie Ihre E-Mail-Benachrichtigung',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo,\n\nvielen Dank, dass Sie sich für die automatische E-Mail Benachrichtigung für den Artikel {$sArticleName} eingetragen haben.\nBitte bestätigen Sie die Benachrichtigung über den nachfolgenden Link:\n\n{$sConfirmLink}\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n        Hallo,<br/>\n        <br/>\n        vielen Dank, dass Sie sich für die automatische E-Mail Benachrichtigung für den Artikel {$sArticleName} eingetragen haben.<br/>\n        Bitte bestätigen Sie die Benachrichtigung über den nachfolgenden Link:<br/>\n        <br/>\n        <a href=\"{$sConfirmLink}\">Bestätigen</a>\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	2,	'a:2:{s:12:\"sConfirmLink\";s:177:\"http://shopware.example/craft-tradition/men/business-bags/165/die-zeit-5?action=notifyConfirm&sNotificationConfirmation=j48FnwtKhMycfizOyYe0CtB0UKzgoeYG&sNotify=1&number=SW10165\";s:12:\"sArticleName\";s:24:\"DIE ZEIT 5 Cowhide mokka\";}',	0),
(51,	NULL,	'sORDERSEPAAUTHORIZATION',	'{config name=mail}',	'{config name=shopName}',	'SEPA Lastschriftmandat',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo,\n\nim Anhang finden Sie ein Lastschriftmandat zu Ihrer Bestellung {$paymentInstance.orderNumber}. Bitte senden Sie uns das komplett ausgefüllte Dokument per Fax oder Email zurück.\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n    	Hallo {$paymentInstance.firstName} {$paymentInstance.lastName},<br/>\n    	<br/>\n    	im Anhang finden Sie ein Lastschriftmandat zu Ihrer Bestellung {$paymentInstance.orderNumber}. Bitte senden Sie uns das komplett ausgefüllte Dokument per Fax oder Email zurück.\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	2,	'a:1:{s:15:\"paymentInstance\";a:3:{s:9:\"firstName\";s:3:\"Max\";s:8:\"lastName\";s:10:\"Mustermann\";s:11:\"orderNumber\";s:5:\"20003\";}}',	0),
(52,	NULL,	'sCONFIRMPASSWORDCHANGE',	'{config name=mail}',	'{config name=shopName}',	'Passwort vergessen - Passwort zurücksetzen',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo {$user.firstname} {$user.lastname},\n\nim Shop {$sShop} wurde eine Anfrage gestellt, um Ihr Passwort zurück zu setzen. Bitte bestätigen Sie den unten stehenden Link, um ein neues Passwort zu definieren.\n\n{$sUrlReset}\n\nDieser Link ist nur für die nächsten 2 Stunden gültig. Danach muss das Zurücksetzen des Passwortes erneut beantragt werden. Falls Sie Ihr Passwort nicht zurücksetzen möchten, ignorieren Sie diese E-Mail - es wird dann keine Änderung vorgenommen.\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n        Hallo {$user.firstname} {$user.lastname},<br/>\n        <br/>\n        im Shop {$sShop} wurde eine Anfrage gestellt, um Ihr Passwort zurück zu setzen.\n        Bitte bestätigen Sie den unten stehenden Link, um ein neues Passwort zu definieren.<br/>\n        <br/>\n        <a href=\"{$sUrlReset}\">Passwort zurücksetzen</a><br/>\n        <br/>\n        Dieser Link ist nur für die nächsten 2 Stunden gültig. Danach muss das Zurücksetzen des Passwortes erneut beantragt werden.\n        Falls Sie Ihr Passwort nicht zurücksetzen möchten, ignorieren Sie diese E-Mail - es wird dann keine Änderung vorgenommen.\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	2,	'a:4:{s:9:\"sUrlReset\";s:83:\"http://shopware.example/account/resetPassword/hash/pdiR4nNSvvTYHQGxC0K2PxLk5QtQilXm\";s:4:\"sUrl\";s:0:\"\";s:4:\"sKey\";s:0:\"\";s:4:\"user\";a:21:{s:11:\"accountmode\";s:1:\"0\";s:6:\"active\";s:1:\"1\";s:9:\"affiliate\";s:1:\"0\";s:8:\"birthday\";N;s:15:\"confirmationkey\";s:0:\"\";s:13:\"customergroup\";s:2:\"EK\";s:14:\"customernumber\";s:5:\"20001\";s:5:\"email\";s:16:\"test@example.com\";s:12:\"failedlogins\";s:1:\"0\";s:10:\"firstlogin\";s:10:\"2011-11-23\";s:9:\"lastlogin\";s:19:\"2012-01-04 14:12:05\";s:8:\"language\";s:1:\"1\";s:15:\"internalcomment\";s:0:\"\";s:11:\"lockeduntil\";N;s:9:\"subshopID\";s:1:\"1\";s:5:\"title\";s:0:\"\";s:10:\"salutation\";s:2:\"mr\";s:9:\"firstname\";s:3:\"Max\";s:8:\"lastname\";s:10:\"Mustermann\";s:10:\"newsletter\";s:1:\"0\";s:10:\"attributes\";b:0;}}',	0),
(53,	1,	'sORDERSTATEMAIL1',	'{config name=mail}',	'{config name=shopName}',	'Bestellung bei {config name=shopName} ist in Bearbeitung',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo {$sUser.billing_firstname} {$sUser.billing_lastname},\n\nder Bestellstatus für Ihre Bestellung {$sOrder.ordernumber} vom {$sOrder.ordertime|date_format:\"%d.%m.%Y\"} hat sich geändert!\nDie Bestellung hat jetzt den Bestellstatus: {$sOrder.status_description}.\n\nDen aktuellen Status Ihrer Bestellung können Sie auch jederzeit auf unserer Webseite im  Bereich \"Mein Konto\" - \"Meine Bestellungen\" abrufen. Sollten Sie allerdings den Kauf ohne Registrierung, also ohne Anlage eines Kundenkontos, gewählt haben, steht Ihnen diese Möglichkeit nicht zur Verfügung.\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n        Hallo {$sUser.billing_firstname} {$sUser.billing_lastname},<br/>\n        <br/>\n        der Bestellstatus für Ihre Bestellung {$sOrder.ordernumber} vom {$sOrder.ordertime|date_format:\"%d.%m.%Y\"} hat sich geändert!<br/>\n        <strong>Die Bestellung hat jetzt den Bestellstatus: {$sOrder.status_description}.</strong><br/>\n        <br/>\n        Den aktuellen Status Ihrer Bestellung können Sie auch jederzeit auf unserer Webseite im  Bereich \"Mein Konto\" - \"Meine Bestellungen\" abrufen. Sollten Sie allerdings den Kauf ohne Registrierung, also ohne Anlage eines Kundenkontos, gewählt haben, steht Ihnen diese Möglichkeit nicht zur Verfügung.\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	3,	'a:4:{s:6:\"sOrder\";a:40:{s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:12:\"order_number\";s:5:\"20003\";s:6:\"userID\";s:1:\"3\";s:10:\"customerID\";s:1:\"3\";s:14:\"invoice_amount\";s:5:\"31.85\";s:18:\"invoice_amount_net\";s:5:\"26.77\";s:16:\"invoice_shipping\";s:3:\"3.9\";s:20:\"invoice_shipping_net\";s:4:\"3.28\";s:9:\"ordertime\";s:19:\"2017-08-07 14:09:26\";s:6:\"status\";s:1:\"1\";s:8:\"statusID\";s:1:\"1\";s:7:\"cleared\";s:2:\"17\";s:9:\"clearedID\";s:2:\"17\";s:9:\"paymentID\";s:1:\"5\";s:13:\"transactionID\";s:0:\"\";s:7:\"comment\";s:0:\"\";s:15:\"customercomment\";s:0:\"\";s:3:\"net\";s:1:\"0\";s:5:\"netto\";s:1:\"0\";s:9:\"partnerID\";s:0:\"\";s:11:\"temporaryID\";s:0:\"\";s:7:\"referer\";s:0:\"\";s:11:\"cleareddate\";N;s:12:\"cleared_date\";N;s:12:\"trackingcode\";s:0:\"\";s:8:\"language\";s:1:\"1\";s:8:\"currency\";s:3:\"EUR\";s:14:\"currencyFactor\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:10:\"dispatchID\";s:1:\"9\";s:10:\"currencyID\";s:1:\"1\";s:12:\"cleared_name\";s:4:\"open\";s:19:\"cleared_description\";s:5:\"Offen\";s:11:\"status_name\";s:10:\"in_process\";s:18:\"status_description\";s:23:\"In Bearbeitung (Wartet)\";s:19:\"payment_description\";s:8:\"Vorkasse\";s:20:\"dispatch_description\";s:16:\"Standard Versand\";s:20:\"currency_description\";s:4:\"Euro\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}s:13:\"sOrderDetails\";a:2:{i:0;a:20:{s:14:\"orderdetailsID\";s:3:\"208\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:3:\"152\";s:18:\"articleordernumber\";s:9:\"SW10152.1\";s:5:\"price\";s:5:\"29.95\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:5:\"29.95\";s:4:\"name\";s:31:\"WINDSTOPPER MÜTZE WARM Schwarz\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"0\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"1\";s:3:\"tax\";s:5:\"19.00\";s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"0\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";s:0:\"\";s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}i:1;a:20:{s:14:\"orderdetailsID\";s:3:\"209\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:1:\"0\";s:18:\"articleordernumber\";s:16:\"SHIPPINGDISCOUNT\";s:5:\"price\";s:2:\"-2\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:2:\"-2\";s:4:\"name\";s:15:\"Warenkorbrabatt\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"4\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"0\";s:3:\"tax\";N;s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"0\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}}s:5:\"sUser\";a:82:{s:15:\"billing_company\";s:0:\"\";s:18:\"billing_department\";s:0:\"\";s:18:\"billing_salutation\";s:2:\"mr\";s:14:\"customernumber\";s:5:\"20005\";s:17:\"billing_firstname\";s:3:\"Max\";s:16:\"billing_lastname\";s:10:\"Mustermann\";s:14:\"billing_street\";s:15:\"Musterstraße 1\";s:32:\"billing_additional_address_line1\";s:0:\"\";s:32:\"billing_additional_address_line2\";s:0:\"\";s:15:\"billing_zipcode\";s:5:\"12345\";s:12:\"billing_city\";s:11:\"Musterstadt\";s:5:\"phone\";s:0:\"\";s:13:\"billing_phone\";s:0:\"\";s:17:\"billing_countryID\";s:1:\"2\";s:15:\"billing_stateID\";N;s:15:\"billing_country\";s:11:\"Deutschland\";s:18:\"billing_countryiso\";s:2:\"DE\";s:19:\"billing_countryarea\";s:11:\"deutschland\";s:17:\"billing_countryen\";s:7:\"GERMANY\";s:5:\"ustid\";s:0:\"\";s:13:\"billing_text1\";N;s:13:\"billing_text2\";N;s:13:\"billing_text3\";N;s:13:\"billing_text4\";N;s:13:\"billing_text5\";N;s:13:\"billing_text6\";N;s:7:\"orderID\";s:2:\"59\";s:16:\"shipping_company\";s:0:\"\";s:19:\"shipping_department\";s:0:\"\";s:19:\"shipping_salutation\";s:2:\"mr\";s:18:\"shipping_firstname\";s:3:\"Max\";s:17:\"shipping_lastname\";s:10:\"Mustermann\";s:15:\"shipping_street\";s:15:\"Musterstraße 1\";s:33:\"shipping_additional_address_line1\";s:0:\"\";s:33:\"shipping_additional_address_line2\";s:0:\"\";s:16:\"shipping_zipcode\";s:5:\"12345\";s:13:\"shipping_city\";s:11:\"Musterstadt\";s:16:\"shipping_stateID\";N;s:18:\"shipping_countryID\";s:1:\"2\";s:16:\"shipping_country\";s:11:\"Deutschland\";s:19:\"shipping_countryiso\";s:2:\"DE\";s:20:\"shipping_countryarea\";s:11:\"deutschland\";s:18:\"shipping_countryen\";s:7:\"GERMANY\";s:14:\"shipping_text1\";N;s:14:\"shipping_text2\";N;s:14:\"shipping_text3\";N;s:14:\"shipping_text4\";N;s:14:\"shipping_text5\";N;s:14:\"shipping_text6\";N;s:2:\"id\";s:1:\"3\";s:8:\"password\";s:60:\"$2y$10$AzjwzOob83DJ2LG6yxXcBeghK9ciBB1zsK3UeBZADZCl10pTQN62W\";s:7:\"encoder\";s:6:\"bcrypt\";s:5:\"email\";s:14:\"xy@example.org\";s:6:\"active\";s:1:\"1\";s:11:\"accountmode\";s:1:\"0\";s:15:\"confirmationkey\";s:0:\"\";s:9:\"paymentID\";s:1:\"5\";s:10:\"firstlogin\";s:10:\"2017-08-07\";s:9:\"lastlogin\";s:19:\"2017-08-07 14:09:26\";s:9:\"sessionID\";s:26:\"hkkhfl82i1jejfvd2f0ucr6om4\";s:10:\"newsletter\";s:1:\"0\";s:10:\"validation\";s:0:\"\";s:9:\"affiliate\";s:1:\"0\";s:13:\"customergroup\";s:2:\"EK\";s:13:\"paymentpreset\";s:1:\"0\";s:8:\"language\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:7:\"referer\";s:0:\"\";s:12:\"pricegroupID\";N;s:15:\"internalcomment\";s:0:\"\";s:12:\"failedlogins\";s:1:\"0\";s:11:\"lockeduntil\";N;s:26:\"default_billing_address_id\";s:1:\"5\";s:27:\"default_shipping_address_id\";s:1:\"5\";s:5:\"title\";N;s:10:\"salutation\";s:2:\"mr\";s:9:\"firstname\";s:3:\"Max\";s:8:\"lastname\";s:10:\"Mustermann\";s:8:\"birthday\";N;s:11:\"login_token\";s:38:\"1239c089-6b2f-4461-9134-c02026970bff.1\";s:11:\"preisgruppe\";s:1:\"1\";s:11:\"billing_net\";s:1:\"1\";}s:9:\"sDispatch\";a:2:{s:4:\"name\";s:16:\"Standard Versand\";s:11:\"description\";s:0:\"\";}}',	0),
(54,	2,	'sORDERSTATEMAIL2',	'{config name=mail}',	'{config name=shopName}',	'Bestellung bei {config name=shopName} komplett abgeschlossen',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo {$sUser.billing_firstname} {$sUser.billing_lastname},\n\nder Bestellstatus für Ihre Bestellung {$sOrder.ordernumber} vom {$sOrder.ordertime|date_format:\"%d.%m.%Y\"} hat sich geändert!\nDie Bestellung hat jetzt den Bestellstatus: {$sOrder.status_description}.\n\nDen aktuellen Status Ihrer Bestellung können Sie auch jederzeit auf unserer Webseite im  Bereich \"Mein Konto\" - \"Meine Bestellungen\" abrufen. Sollten Sie allerdings den Kauf ohne Registrierung, also ohne Anlage eines Kundenkontos, gewählt haben, steht Ihnen diese Möglichkeit nicht zur Verfügung.\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n        Hallo {$sUser.billing_firstname} {$sUser.billing_lastname},<br/>\n        <br/>\n        der Bestellstatus für Ihre Bestellung {$sOrder.ordernumber} vom {$sOrder.ordertime|date_format:\"%d.%m.%Y\"} hat sich geändert!<br/>\n        <strong>Die Bestellung hat jetzt den Bestellstatus: {$sOrder.status_description}.</strong><br/>\n        <br/>\n        Den aktuellen Status Ihrer Bestellung können Sie auch jederzeit auf unserer Webseite im  Bereich \"Mein Konto\" - \"Meine Bestellungen\" abrufen. Sollten Sie allerdings den Kauf ohne Registrierung, also ohne Anlage eines Kundenkontos, gewählt haben, steht Ihnen diese Möglichkeit nicht zur Verfügung.\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	3,	'a:4:{s:6:\"sOrder\";a:40:{s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:12:\"order_number\";s:5:\"20003\";s:6:\"userID\";s:1:\"3\";s:10:\"customerID\";s:1:\"3\";s:14:\"invoice_amount\";s:5:\"31.85\";s:18:\"invoice_amount_net\";s:5:\"26.77\";s:16:\"invoice_shipping\";s:3:\"3.9\";s:20:\"invoice_shipping_net\";s:4:\"3.28\";s:9:\"ordertime\";s:19:\"2017-08-07 14:09:26\";s:6:\"status\";s:1:\"2\";s:8:\"statusID\";s:1:\"2\";s:7:\"cleared\";s:2:\"17\";s:9:\"clearedID\";s:2:\"17\";s:9:\"paymentID\";s:1:\"5\";s:13:\"transactionID\";s:0:\"\";s:7:\"comment\";s:0:\"\";s:15:\"customercomment\";s:0:\"\";s:3:\"net\";s:1:\"0\";s:5:\"netto\";s:1:\"0\";s:9:\"partnerID\";s:0:\"\";s:11:\"temporaryID\";s:0:\"\";s:7:\"referer\";s:0:\"\";s:11:\"cleareddate\";N;s:12:\"cleared_date\";N;s:12:\"trackingcode\";s:0:\"\";s:8:\"language\";s:1:\"1\";s:8:\"currency\";s:3:\"EUR\";s:14:\"currencyFactor\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:10:\"dispatchID\";s:1:\"9\";s:10:\"currencyID\";s:1:\"1\";s:12:\"cleared_name\";s:4:\"open\";s:19:\"cleared_description\";s:5:\"Offen\";s:11:\"status_name\";s:9:\"completed\";s:18:\"status_description\";s:22:\"Komplett abgeschlossen\";s:19:\"payment_description\";s:8:\"Vorkasse\";s:20:\"dispatch_description\";s:16:\"Standard Versand\";s:20:\"currency_description\";s:4:\"Euro\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}s:13:\"sOrderDetails\";a:2:{i:0;a:20:{s:14:\"orderdetailsID\";s:3:\"208\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:3:\"152\";s:18:\"articleordernumber\";s:9:\"SW10152.1\";s:5:\"price\";s:5:\"29.95\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:5:\"29.95\";s:4:\"name\";s:31:\"WINDSTOPPER MÜTZE WARM Schwarz\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"0\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"1\";s:3:\"tax\";s:5:\"19.00\";s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"0\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";s:0:\"\";s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}i:1;a:20:{s:14:\"orderdetailsID\";s:3:\"209\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:1:\"0\";s:18:\"articleordernumber\";s:16:\"SHIPPINGDISCOUNT\";s:5:\"price\";s:2:\"-2\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:2:\"-2\";s:4:\"name\";s:15:\"Warenkorbrabatt\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"4\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"0\";s:3:\"tax\";N;s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"0\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}}s:5:\"sUser\";a:82:{s:15:\"billing_company\";s:0:\"\";s:18:\"billing_department\";s:0:\"\";s:18:\"billing_salutation\";s:2:\"mr\";s:14:\"customernumber\";s:5:\"20005\";s:17:\"billing_firstname\";s:3:\"Max\";s:16:\"billing_lastname\";s:10:\"Mustermann\";s:14:\"billing_street\";s:15:\"Musterstraße 1\";s:32:\"billing_additional_address_line1\";s:0:\"\";s:32:\"billing_additional_address_line2\";s:0:\"\";s:15:\"billing_zipcode\";s:5:\"12345\";s:12:\"billing_city\";s:11:\"Musterstadt\";s:5:\"phone\";s:0:\"\";s:13:\"billing_phone\";s:0:\"\";s:17:\"billing_countryID\";s:1:\"2\";s:15:\"billing_stateID\";N;s:15:\"billing_country\";s:11:\"Deutschland\";s:18:\"billing_countryiso\";s:2:\"DE\";s:19:\"billing_countryarea\";s:11:\"deutschland\";s:17:\"billing_countryen\";s:7:\"GERMANY\";s:5:\"ustid\";s:0:\"\";s:13:\"billing_text1\";N;s:13:\"billing_text2\";N;s:13:\"billing_text3\";N;s:13:\"billing_text4\";N;s:13:\"billing_text5\";N;s:13:\"billing_text6\";N;s:7:\"orderID\";s:2:\"59\";s:16:\"shipping_company\";s:0:\"\";s:19:\"shipping_department\";s:0:\"\";s:19:\"shipping_salutation\";s:2:\"mr\";s:18:\"shipping_firstname\";s:3:\"Max\";s:17:\"shipping_lastname\";s:10:\"Mustermann\";s:15:\"shipping_street\";s:15:\"Musterstraße 1\";s:33:\"shipping_additional_address_line1\";s:0:\"\";s:33:\"shipping_additional_address_line2\";s:0:\"\";s:16:\"shipping_zipcode\";s:5:\"12345\";s:13:\"shipping_city\";s:11:\"Musterstadt\";s:16:\"shipping_stateID\";N;s:18:\"shipping_countryID\";s:1:\"2\";s:16:\"shipping_country\";s:11:\"Deutschland\";s:19:\"shipping_countryiso\";s:2:\"DE\";s:20:\"shipping_countryarea\";s:11:\"deutschland\";s:18:\"shipping_countryen\";s:7:\"GERMANY\";s:14:\"shipping_text1\";N;s:14:\"shipping_text2\";N;s:14:\"shipping_text3\";N;s:14:\"shipping_text4\";N;s:14:\"shipping_text5\";N;s:14:\"shipping_text6\";N;s:2:\"id\";s:1:\"3\";s:8:\"password\";s:60:\"$2y$10$AzjwzOob83DJ2LG6yxXcBeghK9ciBB1zsK3UeBZADZCl10pTQN62W\";s:7:\"encoder\";s:6:\"bcrypt\";s:5:\"email\";s:14:\"xy@example.org\";s:6:\"active\";s:1:\"1\";s:11:\"accountmode\";s:1:\"0\";s:15:\"confirmationkey\";s:0:\"\";s:9:\"paymentID\";s:1:\"5\";s:10:\"firstlogin\";s:10:\"2017-08-07\";s:9:\"lastlogin\";s:19:\"2017-08-07 14:09:26\";s:9:\"sessionID\";s:26:\"hkkhfl82i1jejfvd2f0ucr6om4\";s:10:\"newsletter\";s:1:\"0\";s:10:\"validation\";s:0:\"\";s:9:\"affiliate\";s:1:\"0\";s:13:\"customergroup\";s:2:\"EK\";s:13:\"paymentpreset\";s:1:\"0\";s:8:\"language\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:7:\"referer\";s:0:\"\";s:12:\"pricegroupID\";N;s:15:\"internalcomment\";s:0:\"\";s:12:\"failedlogins\";s:1:\"0\";s:11:\"lockeduntil\";N;s:26:\"default_billing_address_id\";s:1:\"5\";s:27:\"default_shipping_address_id\";s:1:\"5\";s:5:\"title\";N;s:10:\"salutation\";s:2:\"mr\";s:9:\"firstname\";s:3:\"Max\";s:8:\"lastname\";s:10:\"Mustermann\";s:8:\"birthday\";N;s:11:\"login_token\";s:38:\"1239c089-6b2f-4461-9134-c02026970bff.1\";s:11:\"preisgruppe\";s:1:\"1\";s:11:\"billing_net\";s:1:\"1\";}s:9:\"sDispatch\";a:2:{s:4:\"name\";s:16:\"Standard Versand\";s:11:\"description\";s:0:\"\";}}',	0),
(55,	11,	'sORDERSTATEMAIL11',	'{config name=mail}',	'{config name=shopName}',	'Statusänderung zur Bestellung bei {config name=shopName}',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo {$sUser.billing_firstname} {$sUser.billing_lastname},\n\nder Zahlungsstatus für Ihre Bestellung {$sOrder.ordernumber} vom {$sOrder.ordertime|date_format:\"%d.%m.%Y\"} hat sich geändert!\nDie Bestellung hat jetzt den Zahlungsstatus: {$sOrder.cleared_description}.\n\nDen aktuellen Status Ihrer Bestellung können Sie auch jederzeit auf unserer Webseite im  Bereich \"Mein Konto\" - \"Meine Bestellungen\" abrufen. Sollten Sie allerdings den Kauf ohne Registrierung, also ohne Anlage eines Kundenkontos, gewählt haben, steht Ihnen diese Möglichkeit nicht zur Verfügung.\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n        Hallo {$sUser.billing_firstname} {$sUser.billing_lastname},<br/>\n        <br/>\n        der Zahlungsstatus für Ihre Bestellung {$sOrder.ordernumber} vom {$sOrder.ordertime|date_format:\"%d.%m.%Y\"} hat sich geändert!<br/>\n        <strong>Die Bestellung hat jetzt den Zahlungsstatus: {$sOrder.cleared_description}.</strong><br/>\n        <br/>\n        Den aktuellen Status Ihrer Bestellung können Sie auch jederzeit auf unserer Webseite im  Bereich \"Mein Konto\" - \"Meine Bestellungen\" abrufen. Sollten Sie allerdings den Kauf ohne Registrierung, also ohne Anlage eines Kundenkontos, gewählt haben, steht Ihnen diese Möglichkeit nicht zur Verfügung.\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	3,	'a:4:{s:6:\"sOrder\";a:40:{s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:12:\"order_number\";s:5:\"20003\";s:6:\"userID\";s:1:\"3\";s:10:\"customerID\";s:1:\"3\";s:14:\"invoice_amount\";s:5:\"31.85\";s:18:\"invoice_amount_net\";s:5:\"26.77\";s:16:\"invoice_shipping\";s:3:\"3.9\";s:20:\"invoice_shipping_net\";s:4:\"3.28\";s:9:\"ordertime\";s:19:\"2017-08-07 14:09:26\";s:6:\"status\";s:1:\"8\";s:8:\"statusID\";s:1:\"8\";s:7:\"cleared\";s:2:\"11\";s:9:\"clearedID\";s:2:\"11\";s:9:\"paymentID\";s:1:\"5\";s:13:\"transactionID\";s:0:\"\";s:7:\"comment\";s:0:\"\";s:15:\"customercomment\";s:0:\"\";s:3:\"net\";s:1:\"0\";s:5:\"netto\";s:1:\"0\";s:9:\"partnerID\";s:0:\"\";s:11:\"temporaryID\";s:0:\"\";s:7:\"referer\";s:0:\"\";s:11:\"cleareddate\";N;s:12:\"cleared_date\";N;s:12:\"trackingcode\";s:0:\"\";s:8:\"language\";s:1:\"1\";s:8:\"currency\";s:3:\"EUR\";s:14:\"currencyFactor\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:10:\"dispatchID\";s:1:\"9\";s:10:\"currencyID\";s:1:\"1\";s:12:\"cleared_name\";s:14:\"partially_paid\";s:19:\"cleared_description\";s:17:\"Teilweise bezahlt\";s:11:\"status_name\";s:22:\"clarification_required\";s:18:\"status_description\";s:18:\"Klärung notwendig\";s:19:\"payment_description\";s:8:\"Vorkasse\";s:20:\"dispatch_description\";s:16:\"Standard Versand\";s:20:\"currency_description\";s:4:\"Euro\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}s:13:\"sOrderDetails\";a:2:{i:0;a:20:{s:14:\"orderdetailsID\";s:3:\"208\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:3:\"152\";s:18:\"articleordernumber\";s:9:\"SW10152.1\";s:5:\"price\";s:5:\"29.95\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:5:\"29.95\";s:4:\"name\";s:31:\"WINDSTOPPER MÜTZE WARM Schwarz\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"0\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"1\";s:3:\"tax\";s:5:\"19.00\";s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"0\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";s:0:\"\";s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}i:1;a:20:{s:14:\"orderdetailsID\";s:3:\"209\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:1:\"0\";s:18:\"articleordernumber\";s:16:\"SHIPPINGDISCOUNT\";s:5:\"price\";s:2:\"-2\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:2:\"-2\";s:4:\"name\";s:15:\"Warenkorbrabatt\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"4\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"0\";s:3:\"tax\";N;s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"0\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}}s:5:\"sUser\";a:82:{s:15:\"billing_company\";s:0:\"\";s:18:\"billing_department\";s:0:\"\";s:18:\"billing_salutation\";s:2:\"mr\";s:14:\"customernumber\";s:5:\"20005\";s:17:\"billing_firstname\";s:3:\"Max\";s:16:\"billing_lastname\";s:10:\"Mustermann\";s:14:\"billing_street\";s:15:\"Musterstraße 1\";s:32:\"billing_additional_address_line1\";s:0:\"\";s:32:\"billing_additional_address_line2\";s:0:\"\";s:15:\"billing_zipcode\";s:5:\"12345\";s:12:\"billing_city\";s:11:\"Musterstadt\";s:5:\"phone\";s:0:\"\";s:13:\"billing_phone\";s:0:\"\";s:17:\"billing_countryID\";s:1:\"2\";s:15:\"billing_stateID\";N;s:15:\"billing_country\";s:11:\"Deutschland\";s:18:\"billing_countryiso\";s:2:\"DE\";s:19:\"billing_countryarea\";s:11:\"deutschland\";s:17:\"billing_countryen\";s:7:\"GERMANY\";s:5:\"ustid\";s:0:\"\";s:13:\"billing_text1\";N;s:13:\"billing_text2\";N;s:13:\"billing_text3\";N;s:13:\"billing_text4\";N;s:13:\"billing_text5\";N;s:13:\"billing_text6\";N;s:7:\"orderID\";s:2:\"59\";s:16:\"shipping_company\";s:0:\"\";s:19:\"shipping_department\";s:0:\"\";s:19:\"shipping_salutation\";s:2:\"mr\";s:18:\"shipping_firstname\";s:3:\"Max\";s:17:\"shipping_lastname\";s:10:\"Mustermann\";s:15:\"shipping_street\";s:15:\"Musterstraße 1\";s:33:\"shipping_additional_address_line1\";s:0:\"\";s:33:\"shipping_additional_address_line2\";s:0:\"\";s:16:\"shipping_zipcode\";s:5:\"12345\";s:13:\"shipping_city\";s:11:\"Musterstadt\";s:16:\"shipping_stateID\";N;s:18:\"shipping_countryID\";s:1:\"2\";s:16:\"shipping_country\";s:11:\"Deutschland\";s:19:\"shipping_countryiso\";s:2:\"DE\";s:20:\"shipping_countryarea\";s:11:\"deutschland\";s:18:\"shipping_countryen\";s:7:\"GERMANY\";s:14:\"shipping_text1\";N;s:14:\"shipping_text2\";N;s:14:\"shipping_text3\";N;s:14:\"shipping_text4\";N;s:14:\"shipping_text5\";N;s:14:\"shipping_text6\";N;s:2:\"id\";s:1:\"3\";s:8:\"password\";s:60:\"$2y$10$AzjwzOob83DJ2LG6yxXcBeghK9ciBB1zsK3UeBZADZCl10pTQN62W\";s:7:\"encoder\";s:6:\"bcrypt\";s:5:\"email\";s:14:\"xy@example.org\";s:6:\"active\";s:1:\"1\";s:11:\"accountmode\";s:1:\"0\";s:15:\"confirmationkey\";s:0:\"\";s:9:\"paymentID\";s:1:\"5\";s:10:\"firstlogin\";s:10:\"2017-08-07\";s:9:\"lastlogin\";s:19:\"2017-08-07 14:09:26\";s:9:\"sessionID\";s:26:\"hkkhfl82i1jejfvd2f0ucr6om4\";s:10:\"newsletter\";s:1:\"0\";s:10:\"validation\";s:0:\"\";s:9:\"affiliate\";s:1:\"0\";s:13:\"customergroup\";s:2:\"EK\";s:13:\"paymentpreset\";s:1:\"0\";s:8:\"language\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:7:\"referer\";s:0:\"\";s:12:\"pricegroupID\";N;s:15:\"internalcomment\";s:0:\"\";s:12:\"failedlogins\";s:1:\"0\";s:11:\"lockeduntil\";N;s:26:\"default_billing_address_id\";s:1:\"5\";s:27:\"default_shipping_address_id\";s:1:\"5\";s:5:\"title\";N;s:10:\"salutation\";s:2:\"mr\";s:9:\"firstname\";s:3:\"Max\";s:8:\"lastname\";s:10:\"Mustermann\";s:8:\"birthday\";N;s:11:\"login_token\";s:38:\"1239c089-6b2f-4461-9134-c02026970bff.1\";s:11:\"preisgruppe\";s:1:\"1\";s:11:\"billing_net\";s:1:\"1\";}s:9:\"sDispatch\";a:2:{s:4:\"name\";s:16:\"Standard Versand\";s:11:\"description\";s:0:\"\";}}',	0),
(56,	5,	'sORDERSTATEMAIL5',	'{config name=mail}',	'{config name=shopName}',	'Bestellung bei {config name=shopName} ist bereit zur Lieferung',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo {$sUser.billing_firstname} {$sUser.billing_lastname},\n\nder Bestellstatus für Ihre Bestellung {$sOrder.ordernumber} vom {$sOrder.ordertime|date_format:\"%d.%m.%Y\"} hat sich geändert!\nDie Bestellung hat jetzt den Bestellstatus: {$sOrder.status_description}.\n\nDen aktuellen Status Ihrer Bestellung können Sie auch jederzeit auf unserer Webseite im  Bereich \"Mein Konto\" - \"Meine Bestellungen\" abrufen. Sollten Sie allerdings den Kauf ohne Registrierung, also ohne Anlage eines Kundenkontos, gewählt haben, steht Ihnen diese Möglichkeit nicht zur Verfügung.\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n        Hallo {$sUser.billing_firstname} {$sUser.billing_lastname},<br/>\n        <br/>\n        der Bestellstatus für Ihre Bestellung {$sOrder.ordernumber} vom {$sOrder.ordertime|date_format:\"%d.%m.%Y\"} hat sich geändert!<br/>\n        <strong>Die Bestellung hat jetzt den Bestellstatus: {$sOrder.status_description}.</strong><br/>\n        <br/>\n        Den aktuellen Status Ihrer Bestellung können Sie auch jederzeit auf unserer Webseite im  Bereich \"Mein Konto\" - \"Meine Bestellungen\" abrufen. Sollten Sie allerdings den Kauf ohne Registrierung, also ohne Anlage eines Kundenkontos, gewählt haben, steht Ihnen diese Möglichkeit nicht zur Verfügung.\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	3,	'a:4:{s:6:\"sOrder\";a:40:{s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:12:\"order_number\";s:5:\"20003\";s:6:\"userID\";s:1:\"3\";s:10:\"customerID\";s:1:\"3\";s:14:\"invoice_amount\";s:5:\"31.85\";s:18:\"invoice_amount_net\";s:5:\"26.77\";s:16:\"invoice_shipping\";s:3:\"3.9\";s:20:\"invoice_shipping_net\";s:4:\"3.28\";s:9:\"ordertime\";s:19:\"2017-08-07 14:09:26\";s:6:\"status\";s:1:\"5\";s:8:\"statusID\";s:1:\"5\";s:7:\"cleared\";s:2:\"17\";s:9:\"clearedID\";s:2:\"17\";s:9:\"paymentID\";s:1:\"5\";s:13:\"transactionID\";s:0:\"\";s:7:\"comment\";s:0:\"\";s:15:\"customercomment\";s:0:\"\";s:3:\"net\";s:1:\"0\";s:5:\"netto\";s:1:\"0\";s:9:\"partnerID\";s:0:\"\";s:11:\"temporaryID\";s:0:\"\";s:7:\"referer\";s:0:\"\";s:11:\"cleareddate\";N;s:12:\"cleared_date\";N;s:12:\"trackingcode\";s:0:\"\";s:8:\"language\";s:1:\"1\";s:8:\"currency\";s:3:\"EUR\";s:14:\"currencyFactor\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:10:\"dispatchID\";s:1:\"9\";s:10:\"currencyID\";s:1:\"1\";s:12:\"cleared_name\";s:4:\"open\";s:19:\"cleared_description\";s:5:\"Offen\";s:11:\"status_name\";s:18:\"ready_for_delivery\";s:18:\"status_description\";s:20:\"Zur Lieferung bereit\";s:19:\"payment_description\";s:8:\"Vorkasse\";s:20:\"dispatch_description\";s:16:\"Standard Versand\";s:20:\"currency_description\";s:4:\"Euro\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}s:13:\"sOrderDetails\";a:2:{i:0;a:20:{s:14:\"orderdetailsID\";s:3:\"208\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:3:\"152\";s:18:\"articleordernumber\";s:9:\"SW10152.1\";s:5:\"price\";s:5:\"29.95\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:5:\"29.95\";s:4:\"name\";s:31:\"WINDSTOPPER MÜTZE WARM Schwarz\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"0\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"1\";s:3:\"tax\";s:5:\"19.00\";s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"0\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";s:0:\"\";s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}i:1;a:20:{s:14:\"orderdetailsID\";s:3:\"209\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:1:\"0\";s:18:\"articleordernumber\";s:16:\"SHIPPINGDISCOUNT\";s:5:\"price\";s:2:\"-2\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:2:\"-2\";s:4:\"name\";s:15:\"Warenkorbrabatt\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"4\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"0\";s:3:\"tax\";N;s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"0\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}}s:5:\"sUser\";a:82:{s:15:\"billing_company\";s:0:\"\";s:18:\"billing_department\";s:0:\"\";s:18:\"billing_salutation\";s:2:\"mr\";s:14:\"customernumber\";s:5:\"20005\";s:17:\"billing_firstname\";s:3:\"Max\";s:16:\"billing_lastname\";s:10:\"Mustermann\";s:14:\"billing_street\";s:15:\"Musterstraße 1\";s:32:\"billing_additional_address_line1\";s:0:\"\";s:32:\"billing_additional_address_line2\";s:0:\"\";s:15:\"billing_zipcode\";s:5:\"12345\";s:12:\"billing_city\";s:11:\"Musterstadt\";s:5:\"phone\";s:0:\"\";s:13:\"billing_phone\";s:0:\"\";s:17:\"billing_countryID\";s:1:\"2\";s:15:\"billing_stateID\";N;s:15:\"billing_country\";s:11:\"Deutschland\";s:18:\"billing_countryiso\";s:2:\"DE\";s:19:\"billing_countryarea\";s:11:\"deutschland\";s:17:\"billing_countryen\";s:7:\"GERMANY\";s:5:\"ustid\";s:0:\"\";s:13:\"billing_text1\";N;s:13:\"billing_text2\";N;s:13:\"billing_text3\";N;s:13:\"billing_text4\";N;s:13:\"billing_text5\";N;s:13:\"billing_text6\";N;s:7:\"orderID\";s:2:\"59\";s:16:\"shipping_company\";s:0:\"\";s:19:\"shipping_department\";s:0:\"\";s:19:\"shipping_salutation\";s:2:\"mr\";s:18:\"shipping_firstname\";s:3:\"Max\";s:17:\"shipping_lastname\";s:10:\"Mustermann\";s:15:\"shipping_street\";s:15:\"Musterstraße 1\";s:33:\"shipping_additional_address_line1\";s:0:\"\";s:33:\"shipping_additional_address_line2\";s:0:\"\";s:16:\"shipping_zipcode\";s:5:\"12345\";s:13:\"shipping_city\";s:11:\"Musterstadt\";s:16:\"shipping_stateID\";N;s:18:\"shipping_countryID\";s:1:\"2\";s:16:\"shipping_country\";s:11:\"Deutschland\";s:19:\"shipping_countryiso\";s:2:\"DE\";s:20:\"shipping_countryarea\";s:11:\"deutschland\";s:18:\"shipping_countryen\";s:7:\"GERMANY\";s:14:\"shipping_text1\";N;s:14:\"shipping_text2\";N;s:14:\"shipping_text3\";N;s:14:\"shipping_text4\";N;s:14:\"shipping_text5\";N;s:14:\"shipping_text6\";N;s:2:\"id\";s:1:\"3\";s:8:\"password\";s:60:\"$2y$10$AzjwzOob83DJ2LG6yxXcBeghK9ciBB1zsK3UeBZADZCl10pTQN62W\";s:7:\"encoder\";s:6:\"bcrypt\";s:5:\"email\";s:14:\"xy@example.org\";s:6:\"active\";s:1:\"1\";s:11:\"accountmode\";s:1:\"0\";s:15:\"confirmationkey\";s:0:\"\";s:9:\"paymentID\";s:1:\"5\";s:10:\"firstlogin\";s:10:\"2017-08-07\";s:9:\"lastlogin\";s:19:\"2017-08-07 14:09:26\";s:9:\"sessionID\";s:26:\"hkkhfl82i1jejfvd2f0ucr6om4\";s:10:\"newsletter\";s:1:\"0\";s:10:\"validation\";s:0:\"\";s:9:\"affiliate\";s:1:\"0\";s:13:\"customergroup\";s:2:\"EK\";s:13:\"paymentpreset\";s:1:\"0\";s:8:\"language\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:7:\"referer\";s:0:\"\";s:12:\"pricegroupID\";N;s:15:\"internalcomment\";s:0:\"\";s:12:\"failedlogins\";s:1:\"0\";s:11:\"lockeduntil\";N;s:26:\"default_billing_address_id\";s:1:\"5\";s:27:\"default_shipping_address_id\";s:1:\"5\";s:5:\"title\";N;s:10:\"salutation\";s:2:\"mr\";s:9:\"firstname\";s:3:\"Max\";s:8:\"lastname\";s:10:\"Mustermann\";s:8:\"birthday\";N;s:11:\"login_token\";s:38:\"1239c089-6b2f-4461-9134-c02026970bff.1\";s:11:\"preisgruppe\";s:1:\"1\";s:11:\"billing_net\";s:1:\"1\";}s:9:\"sDispatch\";a:2:{s:4:\"name\";s:16:\"Standard Versand\";s:11:\"description\";s:0:\"\";}}',	0),
(57,	3,	'sORDERSTATEMAIL3',	'{config name=mail}',	'{config name=shopName}',	'Statusänderung zur Bestellung bei {config name=shopName}',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo {$sUser.billing_firstname} {$sUser.billing_lastname},\n\nder Bestellstatus für Ihre Bestellung {$sOrder.ordernumber} vom {$sOrder.ordertime|date_format:\"%d.%m.%Y\"} hat sich geändert!\nDie Bestellung hat jetzt den Bestellstatus: {$sOrder.status_description}.\n\n\nInformationen zu Ihrer Bestellung:\n==================================\n{foreach item=details key=position from=$sOrderDetails}\n{$position+1|fill:3}      {$details.articleordernumber}     {$details.name|fill:30}     {$details.quantity} x {$details.price|string_format:\"%.2f\"} {$sOrder.currency}\n{/foreach}\n\nVersandkosten: {$sOrder.invoice_shipping|string_format:\"%.2f\"} {$sOrder.currency}\nNetto-Gesamt: {$sOrder.invoice_amount_net|string_format:\"%.2f\"} {$sOrder.currency}\nGesamtbetrag inkl. MwSt.: {$sOrder.invoice_amount|string_format:\"%.2f\"} {$sOrder.currency}\n\nDen aktuellen Status Ihrer Bestellung können Sie auch jederzeit auf unserer Webseite im  Bereich \"Mein Konto\" - \"Meine Bestellungen\" abrufen. Sollten Sie allerdings den Kauf ohne Registrierung, also ohne Anlage eines Kundenkontos, gewählt haben, steht Ihnen diese Möglichkeit nicht zur Verfügung.\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n        Hallo {$sUser.billing_firstname} {$sUser.billing_lastname},<br/>\n        <br/>\n        der Bestellstatus für Ihre Bestellung {$sOrder.ordernumber} vom {$sOrder.ordertime|date_format:\"%d.%m.%Y\"} hat sich geändert!<br/>\n        <strong>Die Bestellung hat jetzt den Bestellstatus: {$sOrder.status_description}.</strong><br/>\n        <br/>\n        <strong>Informationen zu Ihrer Bestellung:</strong></p><br/>\n        <table width=\"80%\" border=\"0\" style=\"font-family:Arial, Helvetica, sans-serif; font-size:12px;\">\n            <tr>\n                <td bgcolor=\"#F7F7F2\" style=\"border-bottom:1px solid #cccccc;\"><strong>Artikel</strong></td>\n                <td bgcolor=\"#F7F7F2\" style=\"border-bottom:1px solid #cccccc;\"><strong>Pos.</strong></td>\n                <td bgcolor=\"#F7F7F2\" style=\"border-bottom:1px solid #cccccc;\"><strong>Art-Nr.</strong></td>\n                <td bgcolor=\"#F7F7F2\" style=\"border-bottom:1px solid #cccccc;\"><strong>Menge</strong></td>\n                <td bgcolor=\"#F7F7F2\" style=\"border-bottom:1px solid #cccccc;\"><strong>Preis</strong></td>\n            </tr>\n            {foreach item=details key=position from=$sOrderDetails}\n            <tr>\n                <td>{$details.name|wordwrap:80|indent:4}</td>\n                <td>{$position+1|fill:4} </td>\n                <td>{$details.ordernumber|fill:20}</td>\n                <td>{$details.quantity|fill:6}</td>\n                <td>{$details.price|padding:8} {$sOrder.currency}</td>\n            </tr>\n            {/foreach}\n        </table>\n    <p>    \n        <br/>\n        Versandkosten: {$sOrder.invoice_shipping|string_format:\"%.2f\"} {$sOrder.currency}<br/>\n        Netto-Gesamt: {$sOrder.invoice_amount_net|string_format:\"%.2f\"} {$sOrder.currency}<br/>\n        Gesamtbetrag inkl. MwSt.: {$sOrder.invoice_amount|string_format:\"%.2f\"} {$sOrder.currency}<br/>\n    	<br/>\n        Den aktuellen Status Ihrer Bestellung können Sie auch jederzeit auf unserer Webseite im  Bereich \"Mein Konto\" - \"Meine Bestellungen\" abrufen. Sollten Sie allerdings den Kauf ohne Registrierung, also ohne Anlage eines Kundenkontos, gewählt haben, steht Ihnen diese Möglichkeit nicht zur Verfügung.\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	3,	'a:4:{s:6:\"sOrder\";a:40:{s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:12:\"order_number\";s:5:\"20003\";s:6:\"userID\";s:1:\"3\";s:10:\"customerID\";s:1:\"3\";s:14:\"invoice_amount\";s:5:\"31.85\";s:18:\"invoice_amount_net\";s:5:\"26.77\";s:16:\"invoice_shipping\";s:3:\"3.9\";s:20:\"invoice_shipping_net\";s:4:\"3.28\";s:9:\"ordertime\";s:19:\"2017-08-07 14:09:26\";s:6:\"status\";s:1:\"3\";s:8:\"statusID\";s:1:\"3\";s:7:\"cleared\";s:2:\"17\";s:9:\"clearedID\";s:2:\"17\";s:9:\"paymentID\";s:1:\"5\";s:13:\"transactionID\";s:0:\"\";s:7:\"comment\";s:0:\"\";s:15:\"customercomment\";s:0:\"\";s:3:\"net\";s:1:\"0\";s:5:\"netto\";s:1:\"0\";s:9:\"partnerID\";s:0:\"\";s:11:\"temporaryID\";s:0:\"\";s:7:\"referer\";s:0:\"\";s:11:\"cleareddate\";N;s:12:\"cleared_date\";N;s:12:\"trackingcode\";s:0:\"\";s:8:\"language\";s:1:\"1\";s:8:\"currency\";s:3:\"EUR\";s:14:\"currencyFactor\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:10:\"dispatchID\";s:1:\"9\";s:10:\"currencyID\";s:1:\"1\";s:12:\"cleared_name\";s:4:\"open\";s:19:\"cleared_description\";s:5:\"Offen\";s:11:\"status_name\";s:19:\"partially_completed\";s:18:\"status_description\";s:23:\"Teilweise abgeschlossen\";s:19:\"payment_description\";s:8:\"Vorkasse\";s:20:\"dispatch_description\";s:16:\"Standard Versand\";s:20:\"currency_description\";s:4:\"Euro\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}s:13:\"sOrderDetails\";a:2:{i:0;a:20:{s:14:\"orderdetailsID\";s:3:\"208\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:3:\"152\";s:18:\"articleordernumber\";s:9:\"SW10152.1\";s:5:\"price\";s:5:\"29.95\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:5:\"29.95\";s:4:\"name\";s:31:\"WINDSTOPPER MÜTZE WARM Schwarz\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"0\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"1\";s:3:\"tax\";s:5:\"19.00\";s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"0\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";s:0:\"\";s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}i:1;a:20:{s:14:\"orderdetailsID\";s:3:\"209\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:1:\"0\";s:18:\"articleordernumber\";s:16:\"SHIPPINGDISCOUNT\";s:5:\"price\";s:2:\"-2\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:2:\"-2\";s:4:\"name\";s:15:\"Warenkorbrabatt\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"4\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"0\";s:3:\"tax\";N;s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"0\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}}s:5:\"sUser\";a:82:{s:15:\"billing_company\";s:0:\"\";s:18:\"billing_department\";s:0:\"\";s:18:\"billing_salutation\";s:2:\"mr\";s:14:\"customernumber\";s:5:\"20005\";s:17:\"billing_firstname\";s:3:\"Max\";s:16:\"billing_lastname\";s:10:\"Mustermann\";s:14:\"billing_street\";s:15:\"Musterstraße 1\";s:32:\"billing_additional_address_line1\";s:0:\"\";s:32:\"billing_additional_address_line2\";s:0:\"\";s:15:\"billing_zipcode\";s:5:\"12345\";s:12:\"billing_city\";s:11:\"Musterstadt\";s:5:\"phone\";s:0:\"\";s:13:\"billing_phone\";s:0:\"\";s:17:\"billing_countryID\";s:1:\"2\";s:15:\"billing_stateID\";N;s:15:\"billing_country\";s:11:\"Deutschland\";s:18:\"billing_countryiso\";s:2:\"DE\";s:19:\"billing_countryarea\";s:11:\"deutschland\";s:17:\"billing_countryen\";s:7:\"GERMANY\";s:5:\"ustid\";s:0:\"\";s:13:\"billing_text1\";N;s:13:\"billing_text2\";N;s:13:\"billing_text3\";N;s:13:\"billing_text4\";N;s:13:\"billing_text5\";N;s:13:\"billing_text6\";N;s:7:\"orderID\";s:2:\"59\";s:16:\"shipping_company\";s:0:\"\";s:19:\"shipping_department\";s:0:\"\";s:19:\"shipping_salutation\";s:2:\"mr\";s:18:\"shipping_firstname\";s:3:\"Max\";s:17:\"shipping_lastname\";s:10:\"Mustermann\";s:15:\"shipping_street\";s:15:\"Musterstraße 1\";s:33:\"shipping_additional_address_line1\";s:0:\"\";s:33:\"shipping_additional_address_line2\";s:0:\"\";s:16:\"shipping_zipcode\";s:5:\"12345\";s:13:\"shipping_city\";s:11:\"Musterstadt\";s:16:\"shipping_stateID\";N;s:18:\"shipping_countryID\";s:1:\"2\";s:16:\"shipping_country\";s:11:\"Deutschland\";s:19:\"shipping_countryiso\";s:2:\"DE\";s:20:\"shipping_countryarea\";s:11:\"deutschland\";s:18:\"shipping_countryen\";s:7:\"GERMANY\";s:14:\"shipping_text1\";N;s:14:\"shipping_text2\";N;s:14:\"shipping_text3\";N;s:14:\"shipping_text4\";N;s:14:\"shipping_text5\";N;s:14:\"shipping_text6\";N;s:2:\"id\";s:1:\"3\";s:8:\"password\";s:60:\"$2y$10$AzjwzOob83DJ2LG6yxXcBeghK9ciBB1zsK3UeBZADZCl10pTQN62W\";s:7:\"encoder\";s:6:\"bcrypt\";s:5:\"email\";s:14:\"xy@example.org\";s:6:\"active\";s:1:\"1\";s:11:\"accountmode\";s:1:\"0\";s:15:\"confirmationkey\";s:0:\"\";s:9:\"paymentID\";s:1:\"5\";s:10:\"firstlogin\";s:10:\"2017-08-07\";s:9:\"lastlogin\";s:19:\"2017-08-07 14:09:26\";s:9:\"sessionID\";s:26:\"hkkhfl82i1jejfvd2f0ucr6om4\";s:10:\"newsletter\";s:1:\"0\";s:10:\"validation\";s:0:\"\";s:9:\"affiliate\";s:1:\"0\";s:13:\"customergroup\";s:2:\"EK\";s:13:\"paymentpreset\";s:1:\"0\";s:8:\"language\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:7:\"referer\";s:0:\"\";s:12:\"pricegroupID\";N;s:15:\"internalcomment\";s:0:\"\";s:12:\"failedlogins\";s:1:\"0\";s:11:\"lockeduntil\";N;s:26:\"default_billing_address_id\";s:1:\"5\";s:27:\"default_shipping_address_id\";s:1:\"5\";s:5:\"title\";N;s:10:\"salutation\";s:2:\"mr\";s:9:\"firstname\";s:3:\"Max\";s:8:\"lastname\";s:10:\"Mustermann\";s:8:\"birthday\";N;s:11:\"login_token\";s:38:\"1239c089-6b2f-4461-9134-c02026970bff.1\";s:11:\"preisgruppe\";s:1:\"1\";s:11:\"billing_net\";s:1:\"1\";}s:9:\"sDispatch\";a:2:{s:4:\"name\";s:16:\"Standard Versand\";s:11:\"description\";s:0:\"\";}}',	0),
(58,	8,	'sORDERSTATEMAIL8',	'{config name=mail}',	'{config name=shopName}',	'Statusänderung zur Bestellung bei {config name=shopName}',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo {$sUser.billing_firstname} {$sUser.billing_lastname},\n\nder Bestellstatus für Ihre Bestellung {$sOrder.ordernumber} vom {$sOrder.ordertime|date_format:\"%d.%m.%Y\"} hat sich geändert!\nDie Bestellung hat jetzt den Bestellstatus: {$sOrder.status_description}.\n\nDen aktuellen Status Ihrer Bestellung können Sie auch jederzeit auf unserer Webseite im  Bereich \"Mein Konto\" - \"Meine Bestellungen\" abrufen. Sollten Sie allerdings den Kauf ohne Registrierung, also ohne Anlage eines Kundenkontos, gewählt haben, steht Ihnen diese Möglichkeit nicht zur Verfügung.\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n        Hallo {$sUser.billing_firstname} {$sUser.billing_lastname},<br/>\n        <br/>\n        der Bestellstatus für Ihre Bestellung {$sOrder.ordernumber} vom {$sOrder.ordertime|date_format:\"%d.%m.%Y\"} hat sich geändert!<br/>\n        <strong>Die Bestellung hat jetzt den Bestellstatus: {$sOrder.status_description}.</strong><br/>\n        <br/>\n        Den aktuellen Status Ihrer Bestellung können Sie auch jederzeit auf unserer Webseite im  Bereich \"Mein Konto\" - \"Meine Bestellungen\" abrufen. Sollten Sie allerdings den Kauf ohne Registrierung, also ohne Anlage eines Kundenkontos, gewählt haben, steht Ihnen diese Möglichkeit nicht zur Verfügung.\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	3,	'a:4:{s:6:\"sOrder\";a:40:{s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:12:\"order_number\";s:5:\"20003\";s:6:\"userID\";s:1:\"3\";s:10:\"customerID\";s:1:\"3\";s:14:\"invoice_amount\";s:5:\"31.85\";s:18:\"invoice_amount_net\";s:5:\"26.77\";s:16:\"invoice_shipping\";s:3:\"3.9\";s:20:\"invoice_shipping_net\";s:4:\"3.28\";s:9:\"ordertime\";s:19:\"2017-08-07 14:09:26\";s:6:\"status\";s:1:\"8\";s:8:\"statusID\";s:1:\"8\";s:7:\"cleared\";s:2:\"17\";s:9:\"clearedID\";s:2:\"17\";s:9:\"paymentID\";s:1:\"5\";s:13:\"transactionID\";s:0:\"\";s:7:\"comment\";s:0:\"\";s:15:\"customercomment\";s:0:\"\";s:3:\"net\";s:1:\"0\";s:5:\"netto\";s:1:\"0\";s:9:\"partnerID\";s:0:\"\";s:11:\"temporaryID\";s:0:\"\";s:7:\"referer\";s:0:\"\";s:11:\"cleareddate\";N;s:12:\"cleared_date\";N;s:12:\"trackingcode\";s:0:\"\";s:8:\"language\";s:1:\"1\";s:8:\"currency\";s:3:\"EUR\";s:14:\"currencyFactor\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:10:\"dispatchID\";s:1:\"9\";s:10:\"currencyID\";s:1:\"1\";s:12:\"cleared_name\";s:4:\"open\";s:19:\"cleared_description\";s:5:\"Offen\";s:11:\"status_name\";s:22:\"clarification_required\";s:18:\"status_description\";s:18:\"Klärung notwendig\";s:19:\"payment_description\";s:8:\"Vorkasse\";s:20:\"dispatch_description\";s:16:\"Standard Versand\";s:20:\"currency_description\";s:4:\"Euro\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}s:13:\"sOrderDetails\";a:2:{i:0;a:20:{s:14:\"orderdetailsID\";s:3:\"208\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:3:\"152\";s:18:\"articleordernumber\";s:9:\"SW10152.1\";s:5:\"price\";s:5:\"29.95\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:5:\"29.95\";s:4:\"name\";s:31:\"WINDSTOPPER MÜTZE WARM Schwarz\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"0\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"1\";s:3:\"tax\";s:5:\"19.00\";s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"0\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";s:0:\"\";s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}i:1;a:20:{s:14:\"orderdetailsID\";s:3:\"209\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:1:\"0\";s:18:\"articleordernumber\";s:16:\"SHIPPINGDISCOUNT\";s:5:\"price\";s:2:\"-2\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:2:\"-2\";s:4:\"name\";s:15:\"Warenkorbrabatt\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"4\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"0\";s:3:\"tax\";N;s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"0\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}}s:5:\"sUser\";a:82:{s:15:\"billing_company\";s:0:\"\";s:18:\"billing_department\";s:0:\"\";s:18:\"billing_salutation\";s:2:\"mr\";s:14:\"customernumber\";s:5:\"20005\";s:17:\"billing_firstname\";s:3:\"Max\";s:16:\"billing_lastname\";s:10:\"Mustermann\";s:14:\"billing_street\";s:15:\"Musterstraße 1\";s:32:\"billing_additional_address_line1\";s:0:\"\";s:32:\"billing_additional_address_line2\";s:0:\"\";s:15:\"billing_zipcode\";s:5:\"12345\";s:12:\"billing_city\";s:11:\"Musterstadt\";s:5:\"phone\";s:0:\"\";s:13:\"billing_phone\";s:0:\"\";s:17:\"billing_countryID\";s:1:\"2\";s:15:\"billing_stateID\";N;s:15:\"billing_country\";s:11:\"Deutschland\";s:18:\"billing_countryiso\";s:2:\"DE\";s:19:\"billing_countryarea\";s:11:\"deutschland\";s:17:\"billing_countryen\";s:7:\"GERMANY\";s:5:\"ustid\";s:0:\"\";s:13:\"billing_text1\";N;s:13:\"billing_text2\";N;s:13:\"billing_text3\";N;s:13:\"billing_text4\";N;s:13:\"billing_text5\";N;s:13:\"billing_text6\";N;s:7:\"orderID\";s:2:\"59\";s:16:\"shipping_company\";s:0:\"\";s:19:\"shipping_department\";s:0:\"\";s:19:\"shipping_salutation\";s:2:\"mr\";s:18:\"shipping_firstname\";s:3:\"Max\";s:17:\"shipping_lastname\";s:10:\"Mustermann\";s:15:\"shipping_street\";s:15:\"Musterstraße 1\";s:33:\"shipping_additional_address_line1\";s:0:\"\";s:33:\"shipping_additional_address_line2\";s:0:\"\";s:16:\"shipping_zipcode\";s:5:\"12345\";s:13:\"shipping_city\";s:11:\"Musterstadt\";s:16:\"shipping_stateID\";N;s:18:\"shipping_countryID\";s:1:\"2\";s:16:\"shipping_country\";s:11:\"Deutschland\";s:19:\"shipping_countryiso\";s:2:\"DE\";s:20:\"shipping_countryarea\";s:11:\"deutschland\";s:18:\"shipping_countryen\";s:7:\"GERMANY\";s:14:\"shipping_text1\";N;s:14:\"shipping_text2\";N;s:14:\"shipping_text3\";N;s:14:\"shipping_text4\";N;s:14:\"shipping_text5\";N;s:14:\"shipping_text6\";N;s:2:\"id\";s:1:\"3\";s:8:\"password\";s:60:\"$2y$10$AzjwzOob83DJ2LG6yxXcBeghK9ciBB1zsK3UeBZADZCl10pTQN62W\";s:7:\"encoder\";s:6:\"bcrypt\";s:5:\"email\";s:14:\"xy@example.org\";s:6:\"active\";s:1:\"1\";s:11:\"accountmode\";s:1:\"0\";s:15:\"confirmationkey\";s:0:\"\";s:9:\"paymentID\";s:1:\"5\";s:10:\"firstlogin\";s:10:\"2017-08-07\";s:9:\"lastlogin\";s:19:\"2017-08-07 14:09:26\";s:9:\"sessionID\";s:26:\"hkkhfl82i1jejfvd2f0ucr6om4\";s:10:\"newsletter\";s:1:\"0\";s:10:\"validation\";s:0:\"\";s:9:\"affiliate\";s:1:\"0\";s:13:\"customergroup\";s:2:\"EK\";s:13:\"paymentpreset\";s:1:\"0\";s:8:\"language\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:7:\"referer\";s:0:\"\";s:12:\"pricegroupID\";N;s:15:\"internalcomment\";s:0:\"\";s:12:\"failedlogins\";s:1:\"0\";s:11:\"lockeduntil\";N;s:26:\"default_billing_address_id\";s:1:\"5\";s:27:\"default_shipping_address_id\";s:1:\"5\";s:5:\"title\";N;s:10:\"salutation\";s:2:\"mr\";s:9:\"firstname\";s:3:\"Max\";s:8:\"lastname\";s:10:\"Mustermann\";s:8:\"birthday\";N;s:11:\"login_token\";s:38:\"1239c089-6b2f-4461-9134-c02026970bff.1\";s:11:\"preisgruppe\";s:1:\"1\";s:11:\"billing_net\";s:1:\"1\";}s:9:\"sDispatch\";a:2:{s:4:\"name\";s:16:\"Standard Versand\";s:11:\"description\";s:0:\"\";}}',	0),
(59,	4,	'sORDERSTATEMAIL4',	'{config name=mail}',	'{config name=shopName}',	'Stornierung der Bestellung bei {config name=shopName}',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo {$sUser.billing_firstname} {$sUser.billing_lastname},\n\nder Bestellstatus für Ihre Bestellung {$sOrder.ordernumber} vom {$sOrder.ordertime|date_format:\"%d.%m.%Y\"} hat sich geändert!\nDie Bestellung hat jetzt den Bestellstatus: {$sOrder.status_description}.\n\nDen aktuellen Status Ihrer Bestellung können Sie auch jederzeit auf unserer Webseite im  Bereich \"Mein Konto\" - \"Meine Bestellungen\" abrufen. Sollten Sie allerdings den Kauf ohne Registrierung, also ohne Anlage eines Kundenkontos, gewählt haben, steht Ihnen diese Möglichkeit nicht zur Verfügung.\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n        Hallo {$sUser.billing_firstname} {$sUser.billing_lastname},<br/>\n        <br/>\n        der Bestellstatus für Ihre Bestellung {$sOrder.ordernumber} vom {$sOrder.ordertime|date_format:\"%d.%m.%Y\"} hat sich geändert!<br/>\n        <strong>Die Bestellung hat jetzt den Bestellstatus: {$sOrder.status_description}.</strong><br/>\n        <br/>\n        Den aktuellen Status Ihrer Bestellung können Sie auch jederzeit auf unserer Webseite im  Bereich \"Mein Konto\" - \"Meine Bestellungen\" abrufen. Sollten Sie allerdings den Kauf ohne Registrierung, also ohne Anlage eines Kundenkontos, gewählt haben, steht Ihnen diese Möglichkeit nicht zur Verfügung.\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	3,	'a:4:{s:6:\"sOrder\";a:40:{s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:12:\"order_number\";s:5:\"20003\";s:6:\"userID\";s:1:\"3\";s:10:\"customerID\";s:1:\"3\";s:14:\"invoice_amount\";s:5:\"31.85\";s:18:\"invoice_amount_net\";s:5:\"26.77\";s:16:\"invoice_shipping\";s:3:\"3.9\";s:20:\"invoice_shipping_net\";s:4:\"3.28\";s:9:\"ordertime\";s:19:\"2017-08-07 14:09:26\";s:6:\"status\";s:1:\"4\";s:8:\"statusID\";s:1:\"4\";s:7:\"cleared\";s:2:\"17\";s:9:\"clearedID\";s:2:\"17\";s:9:\"paymentID\";s:1:\"5\";s:13:\"transactionID\";s:0:\"\";s:7:\"comment\";s:0:\"\";s:15:\"customercomment\";s:0:\"\";s:3:\"net\";s:1:\"0\";s:5:\"netto\";s:1:\"0\";s:9:\"partnerID\";s:0:\"\";s:11:\"temporaryID\";s:0:\"\";s:7:\"referer\";s:0:\"\";s:11:\"cleareddate\";N;s:12:\"cleared_date\";N;s:12:\"trackingcode\";s:0:\"\";s:8:\"language\";s:1:\"1\";s:8:\"currency\";s:3:\"EUR\";s:14:\"currencyFactor\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:10:\"dispatchID\";s:1:\"9\";s:10:\"currencyID\";s:1:\"1\";s:12:\"cleared_name\";s:4:\"open\";s:19:\"cleared_description\";s:5:\"Offen\";s:11:\"status_name\";s:18:\"cancelled_rejected\";s:18:\"status_description\";s:21:\"Storniert / Abgelehnt\";s:19:\"payment_description\";s:8:\"Vorkasse\";s:20:\"dispatch_description\";s:16:\"Standard Versand\";s:20:\"currency_description\";s:4:\"Euro\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}s:13:\"sOrderDetails\";a:2:{i:0;a:20:{s:14:\"orderdetailsID\";s:3:\"208\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:3:\"152\";s:18:\"articleordernumber\";s:9:\"SW10152.1\";s:5:\"price\";s:5:\"29.95\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:5:\"29.95\";s:4:\"name\";s:31:\"WINDSTOPPER MÜTZE WARM Schwarz\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"0\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"1\";s:3:\"tax\";s:5:\"19.00\";s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"0\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";s:0:\"\";s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}i:1;a:20:{s:14:\"orderdetailsID\";s:3:\"209\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:1:\"0\";s:18:\"articleordernumber\";s:16:\"SHIPPINGDISCOUNT\";s:5:\"price\";s:2:\"-2\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:2:\"-2\";s:4:\"name\";s:15:\"Warenkorbrabatt\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"4\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"0\";s:3:\"tax\";N;s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"0\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}}s:5:\"sUser\";a:82:{s:15:\"billing_company\";s:0:\"\";s:18:\"billing_department\";s:0:\"\";s:18:\"billing_salutation\";s:2:\"mr\";s:14:\"customernumber\";s:5:\"20005\";s:17:\"billing_firstname\";s:3:\"Max\";s:16:\"billing_lastname\";s:10:\"Mustermann\";s:14:\"billing_street\";s:15:\"Musterstraße 1\";s:32:\"billing_additional_address_line1\";s:0:\"\";s:32:\"billing_additional_address_line2\";s:0:\"\";s:15:\"billing_zipcode\";s:5:\"12345\";s:12:\"billing_city\";s:11:\"Musterstadt\";s:5:\"phone\";s:0:\"\";s:13:\"billing_phone\";s:0:\"\";s:17:\"billing_countryID\";s:1:\"2\";s:15:\"billing_stateID\";N;s:15:\"billing_country\";s:11:\"Deutschland\";s:18:\"billing_countryiso\";s:2:\"DE\";s:19:\"billing_countryarea\";s:11:\"deutschland\";s:17:\"billing_countryen\";s:7:\"GERMANY\";s:5:\"ustid\";s:0:\"\";s:13:\"billing_text1\";N;s:13:\"billing_text2\";N;s:13:\"billing_text3\";N;s:13:\"billing_text4\";N;s:13:\"billing_text5\";N;s:13:\"billing_text6\";N;s:7:\"orderID\";s:2:\"59\";s:16:\"shipping_company\";s:0:\"\";s:19:\"shipping_department\";s:0:\"\";s:19:\"shipping_salutation\";s:2:\"mr\";s:18:\"shipping_firstname\";s:3:\"Max\";s:17:\"shipping_lastname\";s:10:\"Mustermann\";s:15:\"shipping_street\";s:15:\"Musterstraße 1\";s:33:\"shipping_additional_address_line1\";s:0:\"\";s:33:\"shipping_additional_address_line2\";s:0:\"\";s:16:\"shipping_zipcode\";s:5:\"12345\";s:13:\"shipping_city\";s:11:\"Musterstadt\";s:16:\"shipping_stateID\";N;s:18:\"shipping_countryID\";s:1:\"2\";s:16:\"shipping_country\";s:11:\"Deutschland\";s:19:\"shipping_countryiso\";s:2:\"DE\";s:20:\"shipping_countryarea\";s:11:\"deutschland\";s:18:\"shipping_countryen\";s:7:\"GERMANY\";s:14:\"shipping_text1\";N;s:14:\"shipping_text2\";N;s:14:\"shipping_text3\";N;s:14:\"shipping_text4\";N;s:14:\"shipping_text5\";N;s:14:\"shipping_text6\";N;s:2:\"id\";s:1:\"3\";s:8:\"password\";s:60:\"$2y$10$AzjwzOob83DJ2LG6yxXcBeghK9ciBB1zsK3UeBZADZCl10pTQN62W\";s:7:\"encoder\";s:6:\"bcrypt\";s:5:\"email\";s:14:\"xy@example.org\";s:6:\"active\";s:1:\"1\";s:11:\"accountmode\";s:1:\"0\";s:15:\"confirmationkey\";s:0:\"\";s:9:\"paymentID\";s:1:\"5\";s:10:\"firstlogin\";s:10:\"2017-08-07\";s:9:\"lastlogin\";s:19:\"2017-08-07 14:09:26\";s:9:\"sessionID\";s:26:\"hkkhfl82i1jejfvd2f0ucr6om4\";s:10:\"newsletter\";s:1:\"0\";s:10:\"validation\";s:0:\"\";s:9:\"affiliate\";s:1:\"0\";s:13:\"customergroup\";s:2:\"EK\";s:13:\"paymentpreset\";s:1:\"0\";s:8:\"language\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:7:\"referer\";s:0:\"\";s:12:\"pricegroupID\";N;s:15:\"internalcomment\";s:0:\"\";s:12:\"failedlogins\";s:1:\"0\";s:11:\"lockeduntil\";N;s:26:\"default_billing_address_id\";s:1:\"5\";s:27:\"default_shipping_address_id\";s:1:\"5\";s:5:\"title\";N;s:10:\"salutation\";s:2:\"mr\";s:9:\"firstname\";s:3:\"Max\";s:8:\"lastname\";s:10:\"Mustermann\";s:8:\"birthday\";N;s:11:\"login_token\";s:38:\"1239c089-6b2f-4461-9134-c02026970bff.1\";s:11:\"preisgruppe\";s:1:\"1\";s:11:\"billing_net\";s:1:\"1\";}s:9:\"sDispatch\";a:2:{s:4:\"name\";s:16:\"Standard Versand\";s:11:\"description\";s:0:\"\";}}',	0),
(60,	6,	'sORDERSTATEMAIL6',	'{config name=mail}',	'{config name=shopName}',	'Bestellung bei {config name=shopName} wurde teilweise ausgeliefert',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo {$sUser.billing_firstname} {$sUser.billing_lastname},\n\nder Bestellstatus für Ihre Bestellung {$sOrder.ordernumber} vom {$sOrder.ordertime|date_format:\"%d.%m.%Y\"} hat sich geändert!\nDie Bestellung hat jetzt den Bestellstatus: {$sOrder.status_description}.\n\nDen aktuellen Status Ihrer Bestellung können Sie auch jederzeit auf unserer Webseite im  Bereich \"Mein Konto\" - \"Meine Bestellungen\" abrufen. Sollten Sie allerdings den Kauf ohne Registrierung, also ohne Anlage eines Kundenkontos, gewählt haben, steht Ihnen diese Möglichkeit nicht zur Verfügung.\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n        Hallo {$sUser.billing_firstname} {$sUser.billing_lastname},<br/>\n        <br/>\n        der Bestellstatus für Ihre Bestellung {$sOrder.ordernumber} vom {$sOrder.ordertime|date_format:\"%d.%m.%Y\"} hat sich geändert!<br/>\n        <strong>Die Bestellung hat jetzt den Bestellstatus: {$sOrder.status_description}.</strong><br/>\n        <br/>\n        Den aktuellen Status Ihrer Bestellung können Sie auch jederzeit auf unserer Webseite im  Bereich \"Mein Konto\" - \"Meine Bestellungen\" abrufen. Sollten Sie allerdings den Kauf ohne Registrierung, also ohne Anlage eines Kundenkontos, gewählt haben, steht Ihnen diese Möglichkeit nicht zur Verfügung.\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	3,	'a:4:{s:6:\"sOrder\";a:40:{s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:12:\"order_number\";s:5:\"20003\";s:6:\"userID\";s:1:\"3\";s:10:\"customerID\";s:1:\"3\";s:14:\"invoice_amount\";s:5:\"31.85\";s:18:\"invoice_amount_net\";s:5:\"26.77\";s:16:\"invoice_shipping\";s:3:\"3.9\";s:20:\"invoice_shipping_net\";s:4:\"3.28\";s:9:\"ordertime\";s:19:\"2017-08-07 14:09:26\";s:6:\"status\";s:1:\"6\";s:8:\"statusID\";s:1:\"6\";s:7:\"cleared\";s:2:\"17\";s:9:\"clearedID\";s:2:\"17\";s:9:\"paymentID\";s:1:\"5\";s:13:\"transactionID\";s:0:\"\";s:7:\"comment\";s:0:\"\";s:15:\"customercomment\";s:0:\"\";s:3:\"net\";s:1:\"0\";s:5:\"netto\";s:1:\"0\";s:9:\"partnerID\";s:0:\"\";s:11:\"temporaryID\";s:0:\"\";s:7:\"referer\";s:0:\"\";s:11:\"cleareddate\";N;s:12:\"cleared_date\";N;s:12:\"trackingcode\";s:0:\"\";s:8:\"language\";s:1:\"1\";s:8:\"currency\";s:3:\"EUR\";s:14:\"currencyFactor\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:10:\"dispatchID\";s:1:\"9\";s:10:\"currencyID\";s:1:\"1\";s:12:\"cleared_name\";s:4:\"open\";s:19:\"cleared_description\";s:5:\"Offen\";s:11:\"status_name\";s:19:\"partially_delivered\";s:18:\"status_description\";s:22:\"Teilweise ausgeliefert\";s:19:\"payment_description\";s:8:\"Vorkasse\";s:20:\"dispatch_description\";s:16:\"Standard Versand\";s:20:\"currency_description\";s:4:\"Euro\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}s:13:\"sOrderDetails\";a:2:{i:0;a:20:{s:14:\"orderdetailsID\";s:3:\"208\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:3:\"152\";s:18:\"articleordernumber\";s:9:\"SW10152.1\";s:5:\"price\";s:5:\"29.95\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:5:\"29.95\";s:4:\"name\";s:31:\"WINDSTOPPER MÜTZE WARM Schwarz\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"0\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"1\";s:3:\"tax\";s:5:\"19.00\";s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"0\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";s:0:\"\";s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}i:1;a:20:{s:14:\"orderdetailsID\";s:3:\"209\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:1:\"0\";s:18:\"articleordernumber\";s:16:\"SHIPPINGDISCOUNT\";s:5:\"price\";s:2:\"-2\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:2:\"-2\";s:4:\"name\";s:15:\"Warenkorbrabatt\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"4\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"0\";s:3:\"tax\";N;s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"0\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}}s:5:\"sUser\";a:82:{s:15:\"billing_company\";s:0:\"\";s:18:\"billing_department\";s:0:\"\";s:18:\"billing_salutation\";s:2:\"mr\";s:14:\"customernumber\";s:5:\"20005\";s:17:\"billing_firstname\";s:3:\"Max\";s:16:\"billing_lastname\";s:10:\"Mustermann\";s:14:\"billing_street\";s:15:\"Musterstraße 1\";s:32:\"billing_additional_address_line1\";s:0:\"\";s:32:\"billing_additional_address_line2\";s:0:\"\";s:15:\"billing_zipcode\";s:5:\"12345\";s:12:\"billing_city\";s:11:\"Musterstadt\";s:5:\"phone\";s:0:\"\";s:13:\"billing_phone\";s:0:\"\";s:17:\"billing_countryID\";s:1:\"2\";s:15:\"billing_stateID\";N;s:15:\"billing_country\";s:11:\"Deutschland\";s:18:\"billing_countryiso\";s:2:\"DE\";s:19:\"billing_countryarea\";s:11:\"deutschland\";s:17:\"billing_countryen\";s:7:\"GERMANY\";s:5:\"ustid\";s:0:\"\";s:13:\"billing_text1\";N;s:13:\"billing_text2\";N;s:13:\"billing_text3\";N;s:13:\"billing_text4\";N;s:13:\"billing_text5\";N;s:13:\"billing_text6\";N;s:7:\"orderID\";s:2:\"59\";s:16:\"shipping_company\";s:0:\"\";s:19:\"shipping_department\";s:0:\"\";s:19:\"shipping_salutation\";s:2:\"mr\";s:18:\"shipping_firstname\";s:3:\"Max\";s:17:\"shipping_lastname\";s:10:\"Mustermann\";s:15:\"shipping_street\";s:15:\"Musterstraße 1\";s:33:\"shipping_additional_address_line1\";s:0:\"\";s:33:\"shipping_additional_address_line2\";s:0:\"\";s:16:\"shipping_zipcode\";s:5:\"12345\";s:13:\"shipping_city\";s:11:\"Musterstadt\";s:16:\"shipping_stateID\";N;s:18:\"shipping_countryID\";s:1:\"2\";s:16:\"shipping_country\";s:11:\"Deutschland\";s:19:\"shipping_countryiso\";s:2:\"DE\";s:20:\"shipping_countryarea\";s:11:\"deutschland\";s:18:\"shipping_countryen\";s:7:\"GERMANY\";s:14:\"shipping_text1\";N;s:14:\"shipping_text2\";N;s:14:\"shipping_text3\";N;s:14:\"shipping_text4\";N;s:14:\"shipping_text5\";N;s:14:\"shipping_text6\";N;s:2:\"id\";s:1:\"3\";s:8:\"password\";s:60:\"$2y$10$AzjwzOob83DJ2LG6yxXcBeghK9ciBB1zsK3UeBZADZCl10pTQN62W\";s:7:\"encoder\";s:6:\"bcrypt\";s:5:\"email\";s:14:\"xy@example.org\";s:6:\"active\";s:1:\"1\";s:11:\"accountmode\";s:1:\"0\";s:15:\"confirmationkey\";s:0:\"\";s:9:\"paymentID\";s:1:\"5\";s:10:\"firstlogin\";s:10:\"2017-08-07\";s:9:\"lastlogin\";s:19:\"2017-08-07 14:09:26\";s:9:\"sessionID\";s:26:\"hkkhfl82i1jejfvd2f0ucr6om4\";s:10:\"newsletter\";s:1:\"0\";s:10:\"validation\";s:0:\"\";s:9:\"affiliate\";s:1:\"0\";s:13:\"customergroup\";s:2:\"EK\";s:13:\"paymentpreset\";s:1:\"0\";s:8:\"language\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:7:\"referer\";s:0:\"\";s:12:\"pricegroupID\";N;s:15:\"internalcomment\";s:0:\"\";s:12:\"failedlogins\";s:1:\"0\";s:11:\"lockeduntil\";N;s:26:\"default_billing_address_id\";s:1:\"5\";s:27:\"default_shipping_address_id\";s:1:\"5\";s:5:\"title\";N;s:10:\"salutation\";s:2:\"mr\";s:9:\"firstname\";s:3:\"Max\";s:8:\"lastname\";s:10:\"Mustermann\";s:8:\"birthday\";N;s:11:\"login_token\";s:38:\"1239c089-6b2f-4461-9134-c02026970bff.1\";s:11:\"preisgruppe\";s:1:\"1\";s:11:\"billing_net\";s:1:\"1\";}s:9:\"sDispatch\";a:2:{s:4:\"name\";s:16:\"Standard Versand\";s:11:\"description\";s:0:\"\";}}',	0),
(61,	7,	'sORDERSTATEMAIL7',	'{config name=mail}',	'{config name=shopName}',	'Bestellung bei {config name=shopName} wurde ausgeliefert',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo {$sUser.billing_firstname} {$sUser.billing_lastname},\n\nder Bestellstatus für Ihre Bestellung {$sOrder.ordernumber} vom {$sOrder.ordertime|date_format:\"%d.%m.%Y\"} hat sich geändert!\nDie Bestellung hat jetzt den Bestellstatus: {$sOrder.status_description}.\n\nDen aktuellen Status Ihrer Bestellung können Sie auch jederzeit auf unserer Webseite im  Bereich \"Mein Konto\" - \"Meine Bestellungen\" abrufen. Sollten Sie allerdings den Kauf ohne Registrierung, also ohne Anlage eines Kundenkontos, gewählt haben, steht Ihnen diese Möglichkeit nicht zur Verfügung.\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n        Hallo {$sUser.billing_firstname} {$sUser.billing_lastname},<br/>\n        <br/>\n        der Bestellstatus für Ihre Bestellung {$sOrder.ordernumber} vom {$sOrder.ordertime|date_format:\"%d.%m.%Y\"} hat sich geändert!<br/>\n        <strong>Die Bestellung hat jetzt den Bestellstatus: {$sOrder.status_description}.</strong><br/>\n        <br/>\n        Den aktuellen Status Ihrer Bestellung können Sie auch jederzeit auf unserer Webseite im  Bereich \"Mein Konto\" - \"Meine Bestellungen\" abrufen. Sollten Sie allerdings den Kauf ohne Registrierung, also ohne Anlage eines Kundenkontos, gewählt haben, steht Ihnen diese Möglichkeit nicht zur Verfügung.\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	3,	'a:4:{s:6:\"sOrder\";a:40:{s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:12:\"order_number\";s:5:\"20003\";s:6:\"userID\";s:1:\"3\";s:10:\"customerID\";s:1:\"3\";s:14:\"invoice_amount\";s:5:\"31.85\";s:18:\"invoice_amount_net\";s:5:\"26.77\";s:16:\"invoice_shipping\";s:3:\"3.9\";s:20:\"invoice_shipping_net\";s:4:\"3.28\";s:9:\"ordertime\";s:19:\"2017-08-07 14:09:26\";s:6:\"status\";s:1:\"7\";s:8:\"statusID\";s:1:\"7\";s:7:\"cleared\";s:2:\"17\";s:9:\"clearedID\";s:2:\"17\";s:9:\"paymentID\";s:1:\"5\";s:13:\"transactionID\";s:0:\"\";s:7:\"comment\";s:0:\"\";s:15:\"customercomment\";s:0:\"\";s:3:\"net\";s:1:\"0\";s:5:\"netto\";s:1:\"0\";s:9:\"partnerID\";s:0:\"\";s:11:\"temporaryID\";s:0:\"\";s:7:\"referer\";s:0:\"\";s:11:\"cleareddate\";N;s:12:\"cleared_date\";N;s:12:\"trackingcode\";s:0:\"\";s:8:\"language\";s:1:\"1\";s:8:\"currency\";s:3:\"EUR\";s:14:\"currencyFactor\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:10:\"dispatchID\";s:1:\"9\";s:10:\"currencyID\";s:1:\"1\";s:12:\"cleared_name\";s:4:\"open\";s:19:\"cleared_description\";s:5:\"Offen\";s:11:\"status_name\";s:20:\"completely_delivered\";s:18:\"status_description\";s:21:\"Komplett ausgeliefert\";s:19:\"payment_description\";s:8:\"Vorkasse\";s:20:\"dispatch_description\";s:16:\"Standard Versand\";s:20:\"currency_description\";s:4:\"Euro\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}s:13:\"sOrderDetails\";a:2:{i:0;a:20:{s:14:\"orderdetailsID\";s:3:\"208\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:3:\"152\";s:18:\"articleordernumber\";s:9:\"SW10152.1\";s:5:\"price\";s:5:\"29.95\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:5:\"29.95\";s:4:\"name\";s:31:\"WINDSTOPPER MÜTZE WARM Schwarz\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"0\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"1\";s:3:\"tax\";s:5:\"19.00\";s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"0\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";s:0:\"\";s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}i:1;a:20:{s:14:\"orderdetailsID\";s:3:\"209\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:1:\"0\";s:18:\"articleordernumber\";s:16:\"SHIPPINGDISCOUNT\";s:5:\"price\";s:2:\"-2\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:2:\"-2\";s:4:\"name\";s:15:\"Warenkorbrabatt\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"4\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"0\";s:3:\"tax\";N;s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"0\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";N;s:10:\"attribute2\";N;s:10:\"attribute3\";N;s:10:\"attribute4\";N;s:10:\"attribute5\";N;s:10:\"attribute6\";N;}}}s:5:\"sUser\";a:82:{s:15:\"billing_company\";s:0:\"\";s:18:\"billing_department\";s:0:\"\";s:18:\"billing_salutation\";s:2:\"mr\";s:14:\"customernumber\";s:5:\"20005\";s:17:\"billing_firstname\";s:3:\"Max\";s:16:\"billing_lastname\";s:10:\"Mustermann\";s:14:\"billing_street\";s:15:\"Musterstraße 1\";s:32:\"billing_additional_address_line1\";s:0:\"\";s:32:\"billing_additional_address_line2\";s:0:\"\";s:15:\"billing_zipcode\";s:5:\"12345\";s:12:\"billing_city\";s:11:\"Musterstadt\";s:5:\"phone\";s:0:\"\";s:13:\"billing_phone\";s:0:\"\";s:17:\"billing_countryID\";s:1:\"2\";s:15:\"billing_stateID\";N;s:15:\"billing_country\";s:11:\"Deutschland\";s:18:\"billing_countryiso\";s:2:\"DE\";s:19:\"billing_countryarea\";s:11:\"deutschland\";s:17:\"billing_countryen\";s:7:\"GERMANY\";s:5:\"ustid\";s:0:\"\";s:13:\"billing_text1\";N;s:13:\"billing_text2\";N;s:13:\"billing_text3\";N;s:13:\"billing_text4\";N;s:13:\"billing_text5\";N;s:13:\"billing_text6\";N;s:7:\"orderID\";s:2:\"59\";s:16:\"shipping_company\";s:0:\"\";s:19:\"shipping_department\";s:0:\"\";s:19:\"shipping_salutation\";s:2:\"mr\";s:18:\"shipping_firstname\";s:3:\"Max\";s:17:\"shipping_lastname\";s:10:\"Mustermann\";s:15:\"shipping_street\";s:15:\"Musterstraße 1\";s:33:\"shipping_additional_address_line1\";s:0:\"\";s:33:\"shipping_additional_address_line2\";s:0:\"\";s:16:\"shipping_zipcode\";s:5:\"12345\";s:13:\"shipping_city\";s:11:\"Musterstadt\";s:16:\"shipping_stateID\";N;s:18:\"shipping_countryID\";s:1:\"2\";s:16:\"shipping_country\";s:11:\"Deutschland\";s:19:\"shipping_countryiso\";s:2:\"DE\";s:20:\"shipping_countryarea\";s:11:\"deutschland\";s:18:\"shipping_countryen\";s:7:\"GERMANY\";s:14:\"shipping_text1\";N;s:14:\"shipping_text2\";N;s:14:\"shipping_text3\";N;s:14:\"shipping_text4\";N;s:14:\"shipping_text5\";N;s:14:\"shipping_text6\";N;s:2:\"id\";s:1:\"3\";s:8:\"password\";s:60:\"$2y$10$AzjwzOob83DJ2LG6yxXcBeghK9ciBB1zsK3UeBZADZCl10pTQN62W\";s:7:\"encoder\";s:6:\"bcrypt\";s:5:\"email\";s:14:\"xy@example.org\";s:6:\"active\";s:1:\"1\";s:11:\"accountmode\";s:1:\"0\";s:15:\"confirmationkey\";s:0:\"\";s:9:\"paymentID\";s:1:\"5\";s:10:\"firstlogin\";s:10:\"2017-08-07\";s:9:\"lastlogin\";s:19:\"2017-08-07 14:09:26\";s:9:\"sessionID\";s:26:\"hkkhfl82i1jejfvd2f0ucr6om4\";s:10:\"newsletter\";s:1:\"0\";s:10:\"validation\";s:0:\"\";s:9:\"affiliate\";s:1:\"0\";s:13:\"customergroup\";s:2:\"EK\";s:13:\"paymentpreset\";s:1:\"0\";s:8:\"language\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:7:\"referer\";s:0:\"\";s:12:\"pricegroupID\";N;s:15:\"internalcomment\";s:0:\"\";s:12:\"failedlogins\";s:1:\"0\";s:11:\"lockeduntil\";N;s:26:\"default_billing_address_id\";s:1:\"5\";s:27:\"default_shipping_address_id\";s:1:\"5\";s:5:\"title\";N;s:10:\"salutation\";s:2:\"mr\";s:9:\"firstname\";s:3:\"Max\";s:8:\"lastname\";s:10:\"Mustermann\";s:8:\"birthday\";N;s:11:\"login_token\";s:38:\"1239c089-6b2f-4461-9134-c02026970bff.1\";s:11:\"preisgruppe\";s:1:\"1\";s:11:\"billing_net\";s:1:\"1\";}s:9:\"sDispatch\";a:2:{s:4:\"name\";s:16:\"Standard Versand\";s:11:\"description\";s:0:\"\";}}',	0),
(62,	NULL,	'sBIRTHDAY',	'{config name=mail}',	'{config name=shopName}',	'Herzlichen Glückwunsch zum Geburtstag von {config name=shopName}',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo {$sUser.firstname} {$sUser.lastname},\n \nAlles Gute zum Geburtstag. Zu Ihrem persönlichen Jubiläum haben wir uns etwas Besonderes ausgedacht, wir senden Ihnen hiermit einen Geburtstagscode über {if $sVoucher.value}{$sVoucher.value|currency|unescape:\"htmlall\"}{else}{$sVoucher.percental} %{/if}, den Sie bei Ihrer nächsten Bestellung in unserem Online-Shop: {$sShopURL} ganz einfach einlösen können.\n \nIhr persönlicher Geburtstags-Code lautet: {$sVoucher.code}\n{if $sVoucher.valid_from && $sVoucher.valid_to}Dieser Code ist gültig vom {$sVoucher.valid_from|date_format:\"%d.%m.%Y\"} bis zum {$sVoucher.valid_to|date_format:\"%d.%m.%Y\"}.{/if}\n{if $sVoucher.valid_from && !$sVoucher.valid_to}Dieser Code ist gültig ab dem {$sVoucher.valid_from|date_format:\"%d.%m.%Y\"}.{/if}\n{if !$sVoucher.valid_from && $sVoucher.valid_to}Dieser Code ist gültig bis zum {$sVoucher.valid_to|date_format:\"%d.%m.%Y\"}.{/if}\n\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n	<p>Hallo {$sUser.salutation|salutation} {$sUser.lastname},</p>\n 	<p><strong>Alles Gute zum Geburtstag</strong>. Zu Ihrem persönlichen Jubiläum haben wir uns etwas Besonderes ausgedacht, wir senden Ihnen hiermit einen Geburtstagscode über {if $sVoucher.value}{$sVoucher.value|currency|unescape:\"htmlall\"}{else}{$sVoucher.percental} %{/if}, den Sie bei Ihrer nächsten Bestellung in unserem <a href=\"{$sShopURL}\" title=\"{$sShop}\">Online-Shop</a> ganz einfach einlösen können.</p>\n 	<p><strong>Ihr persönlicher Geburtstags-Code lautet: <span style=\"text-decoration:underline;\">{$sVoucher.code}</span></strong><br/>\n 	{if $sVoucher.valid_from && $sVoucher.valid_to}Dieser Code ist gültig vom {$sVoucher.valid_from|date_format:\"%d.%m.%Y\"} bis zum {$sVoucher.valid_to|date_format:\"%d.%m.%Y\"}.{/if}\n 	{if $sVoucher.valid_from && !$sVoucher.valid_to}Dieser Code ist gültig ab dem {$sVoucher.valid_from|date_format:\"%d.%m.%Y\"}.{/if}\n 	{if !$sVoucher.valid_from && $sVoucher.valid_to}Dieser Code ist gültig bis zum {$sVoucher.valid_to|date_format:\"%d.%m.%Y\"}.{/if}\n</p>\n \n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	2,	'a:3:{s:5:\"sUser\";a:28:{s:6:\"userID\";s:1:\"1\";s:7:\"company\";s:11:\"Muster GmbH\";s:10:\"department\";N;s:10:\"salutation\";s:2:\"mr\";s:14:\"customernumber\";s:5:\"20001\";s:9:\"firstname\";s:3:\"Max\";s:8:\"lastname\";s:10:\"Mustermann\";s:6:\"street\";s:13:\"Musterstr. 55\";s:7:\"zipcode\";s:5:\"55555\";s:4:\"city\";s:12:\"Musterhausen\";s:5:\"phone\";s:14:\"05555 / 555555\";s:9:\"countryID\";s:1:\"2\";s:5:\"ustid\";N;s:5:\"text1\";N;s:5:\"text2\";N;s:5:\"text3\";N;s:5:\"text4\";N;s:5:\"text5\";N;s:5:\"text6\";N;s:5:\"email\";s:16:\"test@example.com\";s:9:\"paymentID\";s:1:\"5\";s:10:\"firstlogin\";s:10:\"2011-11-23\";s:9:\"lastlogin\";s:19:\"2012-01-04 14:12:05\";s:10:\"newsletter\";s:1:\"0\";s:9:\"affiliate\";s:1:\"0\";s:13:\"customergroup\";s:2:\"EK\";s:8:\"language\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";}s:8:\"sVoucher\";a:6:{s:13:\"vouchercodeID\";s:3:\"201\";s:4:\"code\";s:8:\"0B818118\";s:5:\"value\";s:1:\"5\";s:9:\"percental\";s:1:\"0\";s:8:\"valid_to\";s:10:\"2017-12-31\";s:10:\"valid_from\";s:10:\"2017-10-22\";}s:5:\"sData\";N;}',	0),
(63,	NULL,	'sARTICLECOMMENT',	'{config name=mail}',	'{config name=shopName}',	'Artikel bewerten',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo {$sUser.billing_firstname} {$sUser.billing_lastname},\n\nSie haben bei uns vor einigen Tagen Artikel gekauft. Wir würden uns freuen, wenn Sie diese Artikel bewerten würden.\nSo helfen Sie uns, unseren Service weiter zu steigern und Sie können auf diesem Weg anderen Interessenten direkt Ihre Meinung mitteilen.\n\nHier finden Sie die Links zum Bewerten der von Ihnen gekauften Produkte.\n\nBestellnummer     Artikelname     Bewertungslink\n{foreach from=$sArticles item=sArticle key=key}\n{if !$sArticle.modus}\n{$sArticle.articleordernumber}      {$sArticle.name}      {$sArticle.link_rating_tab}\n{/if}\n{/foreach}\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    Hallo {$sUser.billing_firstname} {$sUser.billing_lastname},<br/>\n    <br/>\n    Sie haben bei uns vor einigen Tagen Artikel gekauft. Wir würden uns freuen, wenn Sie diese Artikel bewerten würden.<br/>\n    So helfen Sie uns, unseren Service weiter zu steigern und Sie können auf diesem Weg anderen Interessenten direkt Ihre Meinung mitteilen.<br/>\n    <br/>\n    Hier finden Sie die Links zum Bewerten der von Ihnen gekauften Produkte.<br/>\n    <br/>\n    <table width=\"80%\" border=\"0\" style=\"font-family:Arial, Helvetica, sans-serif; font-size:12px;\">\n        <tr>\n          <td bgcolor=\"#F7F7F2\" style=\"border-bottom:1px solid #cccccc;\">Artikel</td>\n          <td bgcolor=\"#F7F7F2\" style=\"border-bottom:1px solid #cccccc;\">Bestellnummer</td>\n          <td bgcolor=\"#F7F7F2\" style=\"border-bottom:1px solid #cccccc;\">Artikelname</td>\n          <td bgcolor=\"#F7F7F2\" style=\"border-bottom:1px solid #cccccc;\">Bewertungslink</td>\n        </tr>\n        {foreach from=$sArticles item=sArticle key=key}\n        {if !$sArticle.modus}\n            <tr>\n                <td style=\"border-bottom:1px solid #cccccc;\">\n                  {if $sArticle.image_small && $sArticle.modus == 0}\n                    <img style=\"height: 57px;\" height=\"57\" src=\"{$sArticle.image_small}\" alt=\"{$sArticle.articlename}\" />\n                  {else}\n                  {/if}\n                </td>\n                <td style=\"border-bottom:1px solid #cccccc;\">{$sArticle.articleordernumber}</td>\n                <td style=\"border-bottom:1px solid #cccccc;\">{$sArticle.name}</td>\n                <td style=\"border-bottom:1px solid #cccccc;\">\n                    <a href=\"{$sArticle.link_rating_tab}\">Link</a>\n                </td>\n            </tr>\n        {/if}\n        {/foreach}\n    </table>\n    <br/><br/>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	2,	'a:4:{s:7:\"sConfig\";a:0:{}s:6:\"sOrder\";a:38:{s:2:\"id\";s:2:\"59\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:12:\"order_number\";s:5:\"20003\";s:6:\"userID\";s:1:\"1\";s:10:\"customerID\";s:1:\"1\";s:14:\"invoice_amount\";s:6:\"271.85\";s:18:\"invoice_amount_net\";s:6:\"228.45\";s:16:\"invoice_shipping\";s:3:\"3.9\";s:20:\"invoice_shipping_net\";s:4:\"3.28\";s:9:\"ordertime\";s:19:\"2017-10-09 11:41:41\";s:6:\"status\";s:1:\"2\";s:8:\"statusID\";s:1:\"2\";s:7:\"cleared\";s:2:\"12\";s:9:\"clearedID\";s:2:\"12\";s:9:\"paymentID\";s:1:\"5\";s:13:\"transactionID\";s:0:\"\";s:7:\"comment\";s:0:\"\";s:15:\"customercomment\";s:0:\"\";s:3:\"net\";s:1:\"0\";s:5:\"netto\";s:1:\"0\";s:9:\"partnerID\";s:0:\"\";s:11:\"temporaryID\";s:0:\"\";s:7:\"referer\";s:0:\"\";s:11:\"cleareddate\";s:19:\"2017-10-09 00:00:00\";s:12:\"cleared_date\";s:19:\"2017-10-09 00:00:00\";s:12:\"trackingcode\";s:0:\"\";s:8:\"language\";s:1:\"1\";s:8:\"currency\";s:3:\"EUR\";s:14:\"currencyFactor\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:10:\"dispatchID\";s:1:\"9\";s:10:\"currencyID\";s:1:\"1\";s:19:\"cleared_description\";s:16:\"Komplett bezahlt\";s:18:\"status_description\";s:22:\"Komplett abgeschlossen\";s:19:\"payment_description\";s:8:\"Vorkasse\";s:20:\"dispatch_description\";s:16:\"Standard Versand\";s:20:\"currency_description\";s:4:\"Euro\";}s:5:\"sUser\";a:76:{s:7:\"orderID\";s:2:\"59\";s:15:\"billing_company\";s:11:\"Muster GmbH\";s:18:\"billing_department\";s:0:\"\";s:18:\"billing_salutation\";s:2:\"mr\";s:14:\"customernumber\";s:5:\"20001\";s:17:\"billing_firstname\";s:3:\"Max\";s:16:\"billing_lastname\";s:10:\"Mustermann\";s:14:\"billing_street\";s:13:\"Musterstr. 55\";s:15:\"billing_zipcode\";s:5:\"55555\";s:12:\"billing_city\";s:12:\"Musterhausen\";s:5:\"phone\";s:14:\"05555 / 555555\";s:13:\"billing_phone\";s:14:\"05555 / 555555\";s:17:\"billing_countryID\";s:1:\"2\";s:15:\"billing_country\";s:11:\"Deutschland\";s:18:\"billing_countryiso\";s:2:\"DE\";s:19:\"billing_countryarea\";s:11:\"deutschland\";s:17:\"billing_countryen\";s:7:\"GERMANY\";s:5:\"ustid\";s:0:\"\";s:13:\"billing_text1\";N;s:13:\"billing_text2\";N;s:13:\"billing_text3\";N;s:13:\"billing_text4\";N;s:13:\"billing_text5\";N;s:13:\"billing_text6\";N;s:16:\"shipping_company\";s:11:\"shopware AG\";s:19:\"shipping_department\";s:0:\"\";s:19:\"shipping_salutation\";s:2:\"mr\";s:18:\"shipping_firstname\";s:3:\"Max\";s:17:\"shipping_lastname\";s:10:\"Mustermann\";s:15:\"shipping_street\";s:20:\"Mustermannstraße 92\";s:16:\"shipping_zipcode\";s:5:\"48624\";s:13:\"shipping_city\";s:12:\"Schöppingen\";s:18:\"shipping_countryID\";s:1:\"2\";s:16:\"shipping_country\";s:11:\"Deutschland\";s:19:\"shipping_countryiso\";s:2:\"DE\";s:20:\"shipping_countryarea\";s:11:\"deutschland\";s:18:\"shipping_countryen\";s:7:\"GERMANY\";s:14:\"shipping_text1\";N;s:14:\"shipping_text2\";N;s:14:\"shipping_text3\";N;s:14:\"shipping_text4\";N;s:14:\"shipping_text5\";N;s:14:\"shipping_text6\";N;s:2:\"id\";s:1:\"1\";s:8:\"password\";s:0:\"\";s:7:\"encoder\";s:6:\"bcrypt\";s:5:\"email\";s:16:\"test@example.com\";s:6:\"active\";s:1:\"1\";s:11:\"accountmode\";s:1:\"0\";s:15:\"confirmationkey\";s:0:\"\";s:9:\"paymentID\";s:1:\"5\";s:10:\"firstlogin\";s:10:\"2011-11-23\";s:9:\"lastlogin\";s:19:\"2017-10-09 11:41:41\";s:9:\"sessionID\";s:26:\"sh860bhb7plloqm4teo8s99tq0\";s:10:\"newsletter\";s:1:\"0\";s:10:\"validation\";s:1:\"0\";s:9:\"affiliate\";s:1:\"0\";s:13:\"customergroup\";s:2:\"EK\";s:13:\"paymentpreset\";s:1:\"0\";s:8:\"language\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:7:\"referer\";s:0:\"\";s:12:\"pricegroupID\";N;s:15:\"internalcomment\";s:0:\"\";s:12:\"failedlogins\";s:1:\"0\";s:11:\"lockeduntil\";N;s:26:\"default_billing_address_id\";s:1:\"1\";s:27:\"default_shipping_address_id\";s:1:\"3\";s:5:\"title\";s:0:\"\";s:10:\"salutation\";s:2:\"mr\";s:9:\"firstname\";s:3:\"Max\";s:8:\"lastname\";s:10:\"Mustermann\";s:8:\"birthday\";N;s:11:\"login_token\";s:38:\"0626e2f8-db4a-41b3-b103-e9cece25f51a.1\";s:11:\"preisgruppe\";s:1:\"1\";s:11:\"billing_net\";s:1:\"1\";}s:9:\"sArticles\";a:1:{i:212;a:25:{s:14:\"orderdetailsID\";s:3:\"212\";s:7:\"orderID\";s:2:\"59\";s:11:\"ordernumber\";s:5:\"20003\";s:9:\"articleID\";s:3:\"134\";s:18:\"articleordernumber\";s:7:\"SW10153\";s:5:\"price\";s:5:\"49.99\";s:8:\"quantity\";s:1:\"1\";s:7:\"invoice\";s:5:\"49.99\";s:4:\"name\";s:11:\"ELASTIC CAP\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"0\";s:10:\"esdarticle\";s:1:\"0\";s:5:\"taxID\";s:1:\"1\";s:3:\"tax\";s:5:\"19.00\";s:3:\"esd\";s:1:\"0\";s:9:\"subshopID\";s:1:\"1\";s:8:\"language\";s:1:\"1\";s:4:\"link\";s:42:\"http://shopware.example/elastic-muetze-153\";s:15:\"link_rating_tab\";s:57:\"http://shopware.example/elastic-muetze-153?jumpTab=rating\";s:11:\"image_large\";s:68:\"https://www.shopwaredemo.de/media/image/e3/46/f9/SW10153_200x200.jpg\";s:11:\"image_small\";s:68:\"https://www.shopwaredemo.de/media/image/e3/46/f9/SW10153_200x200.jpg\";s:14:\"image_original\";s:68:\"https://www.shopwaredemo.de/media/image/e3/46/f9/SW10153_200x200.jpg\";}}}',	0),
(64,	NULL,	'sORDERDOCUMENTS',	'{config name=mail}',	'{config name=shopName}',	'Dokumente zur Bestellung {$orderNumber}',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo {$sUser.firstname} {$sUser.lastname},\n\nvielen Dank für Ihre Bestellung bei {config name=shopName}. Im Anhang finden Sie Dokumente zu Ihrer Bestellung als PDF.\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n        Hallo {$sUser.salutation|salutation} {$sUser.lastname},<br/>\n        <br/>\n        vielen Dank für Ihre Bestellung bei {config name=shopName}. Im Anhang finden Sie Dokumente zu Ihrer Bestellung als PDF.\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	4,	'a:4:{s:6:\"sOrder\";a:40:{s:7:\"orderID\";s:2:\"57\";s:11:\"ordernumber\";s:5:\"20002\";s:12:\"order_number\";s:5:\"20002\";s:6:\"userID\";s:1:\"1\";s:10:\"customerID\";s:1:\"1\";s:14:\"invoice_amount\";s:6:\"201.86\";s:18:\"invoice_amount_net\";s:6:\"169.63\";s:16:\"invoice_shipping\";s:1:\"0\";s:20:\"invoice_shipping_net\";s:1:\"0\";s:9:\"ordertime\";s:19:\"2012-08-31 08:51:46\";s:6:\"status\";s:1:\"7\";s:8:\"statusID\";s:1:\"7\";s:7:\"cleared\";s:2:\"12\";s:9:\"clearedID\";s:2:\"12\";s:9:\"paymentID\";s:1:\"4\";s:13:\"transactionID\";s:0:\"\";s:7:\"comment\";s:0:\"\";s:15:\"customercomment\";s:0:\"\";s:3:\"net\";s:1:\"0\";s:5:\"netto\";s:1:\"0\";s:9:\"partnerID\";s:0:\"\";s:11:\"temporaryID\";s:0:\"\";s:7:\"referer\";s:0:\"\";s:11:\"cleareddate\";N;s:12:\"cleared_date\";N;s:12:\"trackingcode\";s:0:\"\";s:8:\"language\";s:1:\"1\";s:8:\"currency\";s:3:\"EUR\";s:14:\"currencyFactor\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:10:\"dispatchID\";s:1:\"9\";s:10:\"currencyID\";s:1:\"1\";s:12:\"cleared_name\";s:15:\"completely_paid\";s:19:\"cleared_description\";s:0:\"\";s:11:\"status_name\";s:20:\"completely_delivered\";s:18:\"status_description\";s:0:\"\";s:19:\"payment_description\";s:0:\"\";s:20:\"dispatch_description\";s:0:\"\";s:20:\"currency_description\";s:4:\"Euro\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";s:0:\"\";s:10:\"attribute2\";s:0:\"\";s:10:\"attribute3\";s:0:\"\";s:10:\"attribute4\";s:0:\"\";s:10:\"attribute5\";s:0:\"\";s:10:\"attribute6\";s:0:\"\";}}s:13:\"sOrderDetails\";a:1:{i:0;a:20:{s:14:\"orderdetailsID\";s:3:\"204\";s:7:\"orderID\";s:2:\"57\";s:11:\"ordernumber\";s:5:\"20002\";s:9:\"articleID\";s:3:\"197\";s:18:\"articleordernumber\";s:7:\"SW10196\";s:5:\"price\";s:5:\"34.99\";s:8:\"quantity\";s:1:\"2\";s:7:\"invoice\";s:5:\"69.98\";s:4:\"name\";s:7:\"Artikel\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"0\";s:10:\"esdarticle\";s:1:\"1\";s:5:\"taxID\";s:1:\"1\";s:3:\"tax\";s:5:\"19.00\";s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"1\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";s:0:\"\";s:10:\"attribute2\";s:0:\"\";s:10:\"attribute3\";s:0:\"\";s:10:\"attribute4\";s:0:\"\";s:10:\"attribute5\";s:0:\"\";s:10:\"attribute6\";s:0:\"\";}}}s:5:\"sUser\";a:82:{s:15:\"billing_company\";s:11:\"shopware AG\";s:18:\"billing_department\";s:0:\"\";s:18:\"billing_salutation\";s:2:\"mr\";s:14:\"customernumber\";s:5:\"20001\";s:17:\"billing_firstname\";s:3:\"Max\";s:16:\"billing_lastname\";s:10:\"Mustermann\";s:14:\"billing_street\";s:20:\"Mustermannstraße 92\";s:32:\"billing_additional_address_line1\";N;s:32:\"billing_additional_address_line2\";N;s:15:\"billing_zipcode\";s:5:\"48624\";s:12:\"billing_city\";s:12:\"Schöppingen\";s:5:\"phone\";s:0:\"\";s:13:\"billing_phone\";s:0:\"\";s:17:\"billing_countryID\";s:1:\"2\";s:15:\"billing_stateID\";s:1:\"3\";s:15:\"billing_country\";s:11:\"Deutschland\";s:18:\"billing_countryiso\";s:2:\"DE\";s:19:\"billing_countryarea\";s:11:\"deutschland\";s:17:\"billing_countryen\";s:7:\"GERMANY\";s:5:\"ustid\";s:0:\"\";s:13:\"billing_text1\";N;s:13:\"billing_text2\";N;s:13:\"billing_text3\";N;s:13:\"billing_text4\";N;s:13:\"billing_text5\";N;s:13:\"billing_text6\";N;s:7:\"orderID\";s:2:\"57\";s:16:\"shipping_company\";s:11:\"shopware AG\";s:19:\"shipping_department\";s:0:\"\";s:19:\"shipping_salutation\";s:2:\"mr\";s:18:\"shipping_firstname\";s:3:\"Max\";s:17:\"shipping_lastname\";s:10:\"Mustermann\";s:15:\"shipping_street\";s:20:\"Mustermannstraße 92\";s:33:\"shipping_additional_address_line1\";N;s:33:\"shipping_additional_address_line2\";N;s:16:\"shipping_zipcode\";s:5:\"48624\";s:13:\"shipping_city\";s:12:\"Schöppingen\";s:16:\"shipping_stateID\";s:1:\"3\";s:18:\"shipping_countryID\";s:1:\"2\";s:16:\"shipping_country\";s:11:\"Deutschland\";s:19:\"shipping_countryiso\";s:2:\"DE\";s:20:\"shipping_countryarea\";s:11:\"deutschland\";s:18:\"shipping_countryen\";s:7:\"GERMANY\";s:14:\"shipping_text1\";N;s:14:\"shipping_text2\";N;s:14:\"shipping_text3\";N;s:14:\"shipping_text4\";N;s:14:\"shipping_text5\";N;s:14:\"shipping_text6\";N;s:2:\"id\";s:1:\"1\";s:8:\"password\";s:0:\"\";s:7:\"encoder\";s:3:\"md5\";s:5:\"email\";s:16:\"test@example.com\";s:6:\"active\";s:1:\"1\";s:11:\"accountmode\";s:1:\"0\";s:15:\"confirmationkey\";s:0:\"\";s:9:\"paymentID\";s:1:\"5\";s:10:\"firstlogin\";s:10:\"2011-11-23\";s:9:\"lastlogin\";s:19:\"2012-01-04 14:12:05\";s:9:\"sessionID\";s:26:\"uiorqd755gaar8dn89ukp178c7\";s:10:\"newsletter\";s:1:\"0\";s:10:\"validation\";s:1:\"0\";s:9:\"affiliate\";s:1:\"0\";s:13:\"customergroup\";s:2:\"EK\";s:13:\"paymentpreset\";s:1:\"0\";s:8:\"language\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:7:\"referer\";s:0:\"\";s:12:\"pricegroupID\";N;s:15:\"internalcomment\";s:0:\"\";s:12:\"failedlogins\";s:1:\"0\";s:11:\"lockeduntil\";N;s:26:\"default_billing_address_id\";s:1:\"1\";s:27:\"default_shipping_address_id\";s:1:\"3\";s:5:\"title\";s:0:\"\";s:10:\"salutation\";s:2:\"mr\";s:9:\"firstname\";s:3:\"Max\";s:8:\"lastname\";s:10:\"Mustermann\";s:8:\"birthday\";N;s:11:\"login_token\";N;s:11:\"preisgruppe\";s:1:\"1\";s:11:\"billing_net\";s:1:\"1\";}s:9:\"sDispatch\";a:2:{s:4:\"name\";s:16:\"Standard Versand\";s:11:\"description\";s:0:\"\";}}',	0),
(65,	NULL,	'sOPTINREGISTER',	'{config name=mail}',	'{config name=shopName}',	'Bitte bestätigen Sie Ihre Anmeldung bei {config name=shopName}',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo {$firstname} {$lastname},\n\nvielen Dank für Ihre Anmeldung bei {$sShop}.\nBitte bestätigen Sie die Registrierung über den nachfolgenden Link:\n\n{$sConfirmLink}\n\nDurch diese Bestätigung erklären Sie sich ebenso damit einverstanden, dass wir Ihnen im Rahmen der Vertragserfüllung weitere E-Mails senden dürfen.\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n        Hallo {$firstname} {$lastname},<br/>\n        <br/>\n        vielen Dank für Ihre Anmeldung bei {$sShop}.<br/>\n        Bitte bestätigen Sie die Registrierung über den nachfolgenden Link:<br/>\n        <br/>\n        <a href=\"{$sConfirmLink}\">Anmeldung abschließen</a><br/>\n        <br/>\n        Durch diese Bestätigung erklären Sie sich ebenso damit einverstanden, dass wir Ihnen im Rahmen der Vertragserfüllung weitere E-Mails senden dürfen.<br/>\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	2,	'a:14:{s:5:\"sMAIL\";s:14:\"xy@example.org\";s:7:\"sConfig\";a:0:{}s:6:\"street\";s:15:\"Musterstraße 1\";s:7:\"zipcode\";s:5:\"12345\";s:4:\"city\";s:11:\"Musterstadt\";s:7:\"country\";s:1:\"2\";s:5:\"state\";N;s:13:\"customer_type\";s:7:\"private\";s:10:\"salutation\";s:4:\"Herr\";s:9:\"firstname\";s:3:\"Max\";s:8:\"lastname\";s:10:\"Mustermann\";s:11:\"accountmode\";s:1:\"0\";s:5:\"email\";s:14:\"xy@example.org\";s:10:\"additional\";a:1:{s:13:\"customer_type\";s:7:\"private\";}}',	0),
(66,	NULL,	'sOPTINBLOGCOMMENT',	'{config name=mail}',	'{config name=shopName}',	'Bitte bestätigen Sie Ihre Blogartikel-Bewertung',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo,\n\nvielen Dank für die Bewertung des Blogartikels „{$sArticle.title}“.\nBitte bestätigen Sie die Bewertung über den nachfolgenden Link:\n\n{$sConfirmLink}\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n        Hallo,<br/>\n        <br/>\n        vielen Dank für die Bewertung des Blogartikels „{$sArticle.title}“.<br/>\n        Bitte bestätigen Sie die Bewertung über den nachfolgenden Link:<br/>\n        <br/>\n        <a href=\"{$sConfirmLink}\">Blogartikel-Bewertung abschließen</a><br/>\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	2,	NULL,	0),
(67,	NULL,	'sOPTINREGISTERACCOUNTLESS',	'{config name=mail}',	'{config name=shopName}',	'Bitte bestätigen Sie Ihre E-Mail-Adresse für Ihre Bestellung bei {config name=shopName}',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo {$firstname} {$lastname},\n\nBitte bestätigen Sie Ihre E-Mail-Adresse über den nachfolgenden Link:\n\n{$sConfirmLink}\n\nNach der Bestätigung werden Sie in den Bestellabschluss geleitet, dort können Sie Ihre Bestellung nochmals überprüfen und abschließen.\nDurch diese Bestätigung erklären Sie sich ebenso damit einverstanden, dass wir Ihnen im Rahmen der Vertragserfüllung weitere E-Mails senden dürfen.\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n        Hallo {$firstname} {$lastname},<br/>\n        <br/>\n        Bitte bestätigen Sie Ihre E-Mail-Adresse über den nachfolgenden Link:<br/>\n        <br/>\n        <a href=\"{$sConfirmLink}\">Bestellung fortsetzen</a><br/>\n        <br/>\n        Nach der Bestätigung werden Sie in den Bestellabschluss geleitet, dort können Sie Ihre Bestellung nochmals überprüfen und abschließen.<br/>\n        Durch diese Bestätigung erklären Sie sich ebenso damit einverstanden, dass wir Ihnen im Rahmen der Vertragserfüllung weitere E-Mails senden dürfen.<br/>\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	2,	'a:14:{s:5:\"sMAIL\";s:14:\"xy@example.org\";s:7:\"sConfig\";a:0:{}s:6:\"street\";s:15:\"Musterstraße 1\";s:7:\"zipcode\";s:5:\"12345\";s:4:\"city\";s:11:\"Musterstadt\";s:7:\"country\";s:1:\"2\";s:5:\"state\";N;s:13:\"customer_type\";s:7:\"private\";s:10:\"salutation\";s:4:\"Herr\";s:9:\"firstname\";s:3:\"Max\";s:8:\"lastname\";s:10:\"Mustermann\";s:11:\"accountmode\";s:1:\"0\";s:5:\"email\";s:14:\"xy@example.org\";s:10:\"additional\";a:1:{s:13:\"customer_type\";s:7:\"private\";}}',	0),
(68,	NULL,	'document_invoice',	'{config name=mail}',	'{config name=shopName}',	'Rechnung zur Bestellung {$orderNumber}',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo {$sUser.firstname} {$sUser.lastname},\n\nvielen Dank für Ihre Bestellung bei {config name=shopName}. Im Anhang finden Sie die Rechnung zu Ihrer Bestellung als PDF.\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n        Hallo {$sUser.salutation|salutation} {$sUser.lastname},<br/>\n        <br/>\n        vielen Dank für Ihre Bestellung bei {config name=shopName}. Im Anhang finden Sie die Rechnung zu Ihrer Bestellung als PDF.\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	4,	'a:4:{s:6:\"sOrder\";a:40:{s:7:\"orderID\";s:2:\"57\";s:11:\"ordernumber\";s:5:\"20002\";s:12:\"order_number\";s:5:\"20002\";s:6:\"userID\";s:1:\"1\";s:10:\"customerID\";s:1:\"1\";s:14:\"invoice_amount\";s:6:\"201.86\";s:18:\"invoice_amount_net\";s:6:\"169.63\";s:16:\"invoice_shipping\";s:1:\"0\";s:20:\"invoice_shipping_net\";s:1:\"0\";s:9:\"ordertime\";s:19:\"2012-08-31 08:51:46\";s:6:\"status\";s:1:\"7\";s:8:\"statusID\";s:1:\"7\";s:7:\"cleared\";s:2:\"12\";s:9:\"clearedID\";s:2:\"12\";s:9:\"paymentID\";s:1:\"4\";s:13:\"transactionID\";s:0:\"\";s:7:\"comment\";s:0:\"\";s:15:\"customercomment\";s:0:\"\";s:3:\"net\";s:1:\"0\";s:5:\"netto\";s:1:\"0\";s:9:\"partnerID\";s:0:\"\";s:11:\"temporaryID\";s:0:\"\";s:7:\"referer\";s:0:\"\";s:11:\"cleareddate\";N;s:12:\"cleared_date\";N;s:12:\"trackingcode\";s:0:\"\";s:8:\"language\";s:1:\"1\";s:8:\"currency\";s:3:\"EUR\";s:14:\"currencyFactor\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:10:\"dispatchID\";s:1:\"9\";s:10:\"currencyID\";s:1:\"1\";s:12:\"cleared_name\";s:15:\"completely_paid\";s:19:\"cleared_description\";s:0:\"\";s:11:\"status_name\";s:20:\"completely_delivered\";s:18:\"status_description\";s:0:\"\";s:19:\"payment_description\";s:0:\"\";s:20:\"dispatch_description\";s:0:\"\";s:20:\"currency_description\";s:4:\"Euro\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";s:0:\"\";s:10:\"attribute2\";s:0:\"\";s:10:\"attribute3\";s:0:\"\";s:10:\"attribute4\";s:0:\"\";s:10:\"attribute5\";s:0:\"\";s:10:\"attribute6\";s:0:\"\";}}s:13:\"sOrderDetails\";a:1:{i:0;a:20:{s:14:\"orderdetailsID\";s:3:\"204\";s:7:\"orderID\";s:2:\"57\";s:11:\"ordernumber\";s:5:\"20002\";s:9:\"articleID\";s:3:\"197\";s:18:\"articleordernumber\";s:7:\"SW10196\";s:5:\"price\";s:5:\"34.99\";s:8:\"quantity\";s:1:\"2\";s:7:\"invoice\";s:5:\"69.98\";s:4:\"name\";s:7:\"Artikel\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"0\";s:10:\"esdarticle\";s:1:\"1\";s:5:\"taxID\";s:1:\"1\";s:3:\"tax\";s:5:\"19.00\";s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"1\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";s:0:\"\";s:10:\"attribute2\";s:0:\"\";s:10:\"attribute3\";s:0:\"\";s:10:\"attribute4\";s:0:\"\";s:10:\"attribute5\";s:0:\"\";s:10:\"attribute6\";s:0:\"\";}}}s:5:\"sUser\";a:82:{s:15:\"billing_company\";s:11:\"shopware AG\";s:18:\"billing_department\";s:0:\"\";s:18:\"billing_salutation\";s:2:\"mr\";s:14:\"customernumber\";s:5:\"20001\";s:17:\"billing_firstname\";s:3:\"Max\";s:16:\"billing_lastname\";s:10:\"Mustermann\";s:14:\"billing_street\";s:20:\"Mustermannstraße 92\";s:32:\"billing_additional_address_line1\";N;s:32:\"billing_additional_address_line2\";N;s:15:\"billing_zipcode\";s:5:\"48624\";s:12:\"billing_city\";s:12:\"Schöppingen\";s:5:\"phone\";s:0:\"\";s:13:\"billing_phone\";s:0:\"\";s:17:\"billing_countryID\";s:1:\"2\";s:15:\"billing_stateID\";s:1:\"3\";s:15:\"billing_country\";s:11:\"Deutschland\";s:18:\"billing_countryiso\";s:2:\"DE\";s:19:\"billing_countryarea\";s:11:\"deutschland\";s:17:\"billing_countryen\";s:7:\"GERMANY\";s:5:\"ustid\";s:0:\"\";s:13:\"billing_text1\";N;s:13:\"billing_text2\";N;s:13:\"billing_text3\";N;s:13:\"billing_text4\";N;s:13:\"billing_text5\";N;s:13:\"billing_text6\";N;s:7:\"orderID\";s:2:\"57\";s:16:\"shipping_company\";s:11:\"shopware AG\";s:19:\"shipping_department\";s:0:\"\";s:19:\"shipping_salutation\";s:2:\"mr\";s:18:\"shipping_firstname\";s:3:\"Max\";s:17:\"shipping_lastname\";s:10:\"Mustermann\";s:15:\"shipping_street\";s:20:\"Mustermannstraße 92\";s:33:\"shipping_additional_address_line1\";N;s:33:\"shipping_additional_address_line2\";N;s:16:\"shipping_zipcode\";s:5:\"48624\";s:13:\"shipping_city\";s:12:\"Schöppingen\";s:16:\"shipping_stateID\";s:1:\"3\";s:18:\"shipping_countryID\";s:1:\"2\";s:16:\"shipping_country\";s:11:\"Deutschland\";s:19:\"shipping_countryiso\";s:2:\"DE\";s:20:\"shipping_countryarea\";s:11:\"deutschland\";s:18:\"shipping_countryen\";s:7:\"GERMANY\";s:14:\"shipping_text1\";N;s:14:\"shipping_text2\";N;s:14:\"shipping_text3\";N;s:14:\"shipping_text4\";N;s:14:\"shipping_text5\";N;s:14:\"shipping_text6\";N;s:2:\"id\";s:1:\"1\";s:8:\"password\";s:0:\"\";s:7:\"encoder\";s:3:\"md5\";s:5:\"email\";s:16:\"test@example.com\";s:6:\"active\";s:1:\"1\";s:11:\"accountmode\";s:1:\"0\";s:15:\"confirmationkey\";s:0:\"\";s:9:\"paymentID\";s:1:\"5\";s:10:\"firstlogin\";s:10:\"2011-11-23\";s:9:\"lastlogin\";s:19:\"2012-01-04 14:12:05\";s:9:\"sessionID\";s:26:\"uiorqd755gaar8dn89ukp178c7\";s:10:\"newsletter\";s:1:\"0\";s:10:\"validation\";s:1:\"0\";s:9:\"affiliate\";s:1:\"0\";s:13:\"customergroup\";s:2:\"EK\";s:13:\"paymentpreset\";s:1:\"0\";s:8:\"language\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:7:\"referer\";s:0:\"\";s:12:\"pricegroupID\";N;s:15:\"internalcomment\";s:0:\"\";s:12:\"failedlogins\";s:1:\"0\";s:11:\"lockeduntil\";N;s:26:\"default_billing_address_id\";s:1:\"1\";s:27:\"default_shipping_address_id\";s:1:\"3\";s:5:\"title\";s:0:\"\";s:10:\"salutation\";s:2:\"mr\";s:9:\"firstname\";s:3:\"Max\";s:8:\"lastname\";s:10:\"Mustermann\";s:8:\"birthday\";N;s:11:\"login_token\";N;s:11:\"preisgruppe\";s:1:\"1\";s:11:\"billing_net\";s:1:\"1\";}s:9:\"sDispatch\";a:2:{s:4:\"name\";s:16:\"Standard Versand\";s:11:\"description\";s:0:\"\";}}',	0),
(69,	NULL,	'document_delivery_note',	'{config name=mail}',	'{config name=shopName}',	'Lieferschein zur Bestellung {$orderNumber}',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo {$sUser.firstname} {$sUser.lastname},\n\nvielen Dank für Ihre Bestellung bei {config name=shopName}. Im Anhang finden Sie den Lieferschein zu Ihrer Bestellung als PDF.\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n        Hallo {$sUser.salutation|salutation} {$sUser.lastname},<br/>\n        <br/>\n        vielen Dank für Ihre Bestellung bei {config name=shopName}. Im Anhang finden Sie den Lieferschein zu Ihrer Bestellung als PDF.\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	4,	'a:4:{s:6:\"sOrder\";a:40:{s:7:\"orderID\";s:2:\"57\";s:11:\"ordernumber\";s:5:\"20002\";s:12:\"order_number\";s:5:\"20002\";s:6:\"userID\";s:1:\"1\";s:10:\"customerID\";s:1:\"1\";s:14:\"invoice_amount\";s:6:\"201.86\";s:18:\"invoice_amount_net\";s:6:\"169.63\";s:16:\"invoice_shipping\";s:1:\"0\";s:20:\"invoice_shipping_net\";s:1:\"0\";s:9:\"ordertime\";s:19:\"2012-08-31 08:51:46\";s:6:\"status\";s:1:\"7\";s:8:\"statusID\";s:1:\"7\";s:7:\"cleared\";s:2:\"12\";s:9:\"clearedID\";s:2:\"12\";s:9:\"paymentID\";s:1:\"4\";s:13:\"transactionID\";s:0:\"\";s:7:\"comment\";s:0:\"\";s:15:\"customercomment\";s:0:\"\";s:3:\"net\";s:1:\"0\";s:5:\"netto\";s:1:\"0\";s:9:\"partnerID\";s:0:\"\";s:11:\"temporaryID\";s:0:\"\";s:7:\"referer\";s:0:\"\";s:11:\"cleareddate\";N;s:12:\"cleared_date\";N;s:12:\"trackingcode\";s:0:\"\";s:8:\"language\";s:1:\"1\";s:8:\"currency\";s:3:\"EUR\";s:14:\"currencyFactor\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:10:\"dispatchID\";s:1:\"9\";s:10:\"currencyID\";s:1:\"1\";s:12:\"cleared_name\";s:15:\"completely_paid\";s:19:\"cleared_description\";s:0:\"\";s:11:\"status_name\";s:20:\"completely_delivered\";s:18:\"status_description\";s:0:\"\";s:19:\"payment_description\";s:0:\"\";s:20:\"dispatch_description\";s:0:\"\";s:20:\"currency_description\";s:4:\"Euro\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";s:0:\"\";s:10:\"attribute2\";s:0:\"\";s:10:\"attribute3\";s:0:\"\";s:10:\"attribute4\";s:0:\"\";s:10:\"attribute5\";s:0:\"\";s:10:\"attribute6\";s:0:\"\";}}s:13:\"sOrderDetails\";a:1:{i:0;a:20:{s:14:\"orderdetailsID\";s:3:\"204\";s:7:\"orderID\";s:2:\"57\";s:11:\"ordernumber\";s:5:\"20002\";s:9:\"articleID\";s:3:\"197\";s:18:\"articleordernumber\";s:7:\"SW10196\";s:5:\"price\";s:5:\"34.99\";s:8:\"quantity\";s:1:\"2\";s:7:\"invoice\";s:5:\"69.98\";s:4:\"name\";s:7:\"Artikel\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"0\";s:10:\"esdarticle\";s:1:\"1\";s:5:\"taxID\";s:1:\"1\";s:3:\"tax\";s:5:\"19.00\";s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"1\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";s:0:\"\";s:10:\"attribute2\";s:0:\"\";s:10:\"attribute3\";s:0:\"\";s:10:\"attribute4\";s:0:\"\";s:10:\"attribute5\";s:0:\"\";s:10:\"attribute6\";s:0:\"\";}}}s:5:\"sUser\";a:82:{s:15:\"billing_company\";s:11:\"shopware AG\";s:18:\"billing_department\";s:0:\"\";s:18:\"billing_salutation\";s:2:\"mr\";s:14:\"customernumber\";s:5:\"20001\";s:17:\"billing_firstname\";s:3:\"Max\";s:16:\"billing_lastname\";s:10:\"Mustermann\";s:14:\"billing_street\";s:20:\"Mustermannstraße 92\";s:32:\"billing_additional_address_line1\";N;s:32:\"billing_additional_address_line2\";N;s:15:\"billing_zipcode\";s:5:\"48624\";s:12:\"billing_city\";s:12:\"Schöppingen\";s:5:\"phone\";s:0:\"\";s:13:\"billing_phone\";s:0:\"\";s:17:\"billing_countryID\";s:1:\"2\";s:15:\"billing_stateID\";s:1:\"3\";s:15:\"billing_country\";s:11:\"Deutschland\";s:18:\"billing_countryiso\";s:2:\"DE\";s:19:\"billing_countryarea\";s:11:\"deutschland\";s:17:\"billing_countryen\";s:7:\"GERMANY\";s:5:\"ustid\";s:0:\"\";s:13:\"billing_text1\";N;s:13:\"billing_text2\";N;s:13:\"billing_text3\";N;s:13:\"billing_text4\";N;s:13:\"billing_text5\";N;s:13:\"billing_text6\";N;s:7:\"orderID\";s:2:\"57\";s:16:\"shipping_company\";s:11:\"shopware AG\";s:19:\"shipping_department\";s:0:\"\";s:19:\"shipping_salutation\";s:2:\"mr\";s:18:\"shipping_firstname\";s:3:\"Max\";s:17:\"shipping_lastname\";s:10:\"Mustermann\";s:15:\"shipping_street\";s:20:\"Mustermannstraße 92\";s:33:\"shipping_additional_address_line1\";N;s:33:\"shipping_additional_address_line2\";N;s:16:\"shipping_zipcode\";s:5:\"48624\";s:13:\"shipping_city\";s:12:\"Schöppingen\";s:16:\"shipping_stateID\";s:1:\"3\";s:18:\"shipping_countryID\";s:1:\"2\";s:16:\"shipping_country\";s:11:\"Deutschland\";s:19:\"shipping_countryiso\";s:2:\"DE\";s:20:\"shipping_countryarea\";s:11:\"deutschland\";s:18:\"shipping_countryen\";s:7:\"GERMANY\";s:14:\"shipping_text1\";N;s:14:\"shipping_text2\";N;s:14:\"shipping_text3\";N;s:14:\"shipping_text4\";N;s:14:\"shipping_text5\";N;s:14:\"shipping_text6\";N;s:2:\"id\";s:1:\"1\";s:8:\"password\";s:0:\"\";s:7:\"encoder\";s:3:\"md5\";s:5:\"email\";s:16:\"test@example.com\";s:6:\"active\";s:1:\"1\";s:11:\"accountmode\";s:1:\"0\";s:15:\"confirmationkey\";s:0:\"\";s:9:\"paymentID\";s:1:\"5\";s:10:\"firstlogin\";s:10:\"2011-11-23\";s:9:\"lastlogin\";s:19:\"2012-01-04 14:12:05\";s:9:\"sessionID\";s:26:\"uiorqd755gaar8dn89ukp178c7\";s:10:\"newsletter\";s:1:\"0\";s:10:\"validation\";s:1:\"0\";s:9:\"affiliate\";s:1:\"0\";s:13:\"customergroup\";s:2:\"EK\";s:13:\"paymentpreset\";s:1:\"0\";s:8:\"language\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:7:\"referer\";s:0:\"\";s:12:\"pricegroupID\";N;s:15:\"internalcomment\";s:0:\"\";s:12:\"failedlogins\";s:1:\"0\";s:11:\"lockeduntil\";N;s:26:\"default_billing_address_id\";s:1:\"1\";s:27:\"default_shipping_address_id\";s:1:\"3\";s:5:\"title\";s:0:\"\";s:10:\"salutation\";s:2:\"mr\";s:9:\"firstname\";s:3:\"Max\";s:8:\"lastname\";s:10:\"Mustermann\";s:8:\"birthday\";N;s:11:\"login_token\";N;s:11:\"preisgruppe\";s:1:\"1\";s:11:\"billing_net\";s:1:\"1\";}s:9:\"sDispatch\";a:2:{s:4:\"name\";s:16:\"Standard Versand\";s:11:\"description\";s:0:\"\";}}',	0),
(70,	NULL,	'document_credit',	'{config name=mail}',	'{config name=shopName}',	'Gutschrift zur Bestellung {$orderNumber}',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo {$sUser.firstname} {$sUser.lastname},\n\nvielen Dank für Ihre Bestellung bei {config name=shopName}. Im Anhang finden Sie die Gutschrift zu Ihrer Bestellung als PDF.\n\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n        Hallo {$sUser.salutation|salutation} {$sUser.lastname},<br/>\n        <br/>\n        vielen Dank für Ihre Bestellung bei {config name=shopName}. Im Anhang finden Sie die Gutschrift zu Ihrer Bestellung als PDF.\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	4,	'a:4:{s:6:\"sOrder\";a:40:{s:7:\"orderID\";s:2:\"57\";s:11:\"ordernumber\";s:5:\"20002\";s:12:\"order_number\";s:5:\"20002\";s:6:\"userID\";s:1:\"1\";s:10:\"customerID\";s:1:\"1\";s:14:\"invoice_amount\";s:6:\"201.86\";s:18:\"invoice_amount_net\";s:6:\"169.63\";s:16:\"invoice_shipping\";s:1:\"0\";s:20:\"invoice_shipping_net\";s:1:\"0\";s:9:\"ordertime\";s:19:\"2012-08-31 08:51:46\";s:6:\"status\";s:1:\"7\";s:8:\"statusID\";s:1:\"7\";s:7:\"cleared\";s:2:\"12\";s:9:\"clearedID\";s:2:\"12\";s:9:\"paymentID\";s:1:\"4\";s:13:\"transactionID\";s:0:\"\";s:7:\"comment\";s:0:\"\";s:15:\"customercomment\";s:0:\"\";s:3:\"net\";s:1:\"0\";s:5:\"netto\";s:1:\"0\";s:9:\"partnerID\";s:0:\"\";s:11:\"temporaryID\";s:0:\"\";s:7:\"referer\";s:0:\"\";s:11:\"cleareddate\";N;s:12:\"cleared_date\";N;s:12:\"trackingcode\";s:0:\"\";s:8:\"language\";s:1:\"1\";s:8:\"currency\";s:3:\"EUR\";s:14:\"currencyFactor\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:10:\"dispatchID\";s:1:\"9\";s:10:\"currencyID\";s:1:\"1\";s:12:\"cleared_name\";s:15:\"completely_paid\";s:19:\"cleared_description\";s:0:\"\";s:11:\"status_name\";s:20:\"completely_delivered\";s:18:\"status_description\";s:0:\"\";s:19:\"payment_description\";s:0:\"\";s:20:\"dispatch_description\";s:0:\"\";s:20:\"currency_description\";s:4:\"Euro\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";s:0:\"\";s:10:\"attribute2\";s:0:\"\";s:10:\"attribute3\";s:0:\"\";s:10:\"attribute4\";s:0:\"\";s:10:\"attribute5\";s:0:\"\";s:10:\"attribute6\";s:0:\"\";}}s:13:\"sOrderDetails\";a:1:{i:0;a:20:{s:14:\"orderdetailsID\";s:3:\"204\";s:7:\"orderID\";s:2:\"57\";s:11:\"ordernumber\";s:5:\"20002\";s:9:\"articleID\";s:3:\"197\";s:18:\"articleordernumber\";s:7:\"SW10196\";s:5:\"price\";s:5:\"34.99\";s:8:\"quantity\";s:1:\"2\";s:7:\"invoice\";s:5:\"69.98\";s:4:\"name\";s:7:\"Artikel\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"0\";s:10:\"esdarticle\";s:1:\"1\";s:5:\"taxID\";s:1:\"1\";s:3:\"tax\";s:5:\"19.00\";s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"1\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";s:0:\"\";s:10:\"attribute2\";s:0:\"\";s:10:\"attribute3\";s:0:\"\";s:10:\"attribute4\";s:0:\"\";s:10:\"attribute5\";s:0:\"\";s:10:\"attribute6\";s:0:\"\";}}}s:5:\"sUser\";a:82:{s:15:\"billing_company\";s:11:\"shopware AG\";s:18:\"billing_department\";s:0:\"\";s:18:\"billing_salutation\";s:2:\"mr\";s:14:\"customernumber\";s:5:\"20001\";s:17:\"billing_firstname\";s:3:\"Max\";s:16:\"billing_lastname\";s:10:\"Mustermann\";s:14:\"billing_street\";s:20:\"Mustermannstraße 92\";s:32:\"billing_additional_address_line1\";N;s:32:\"billing_additional_address_line2\";N;s:15:\"billing_zipcode\";s:5:\"48624\";s:12:\"billing_city\";s:12:\"Schöppingen\";s:5:\"phone\";s:0:\"\";s:13:\"billing_phone\";s:0:\"\";s:17:\"billing_countryID\";s:1:\"2\";s:15:\"billing_stateID\";s:1:\"3\";s:15:\"billing_country\";s:11:\"Deutschland\";s:18:\"billing_countryiso\";s:2:\"DE\";s:19:\"billing_countryarea\";s:11:\"deutschland\";s:17:\"billing_countryen\";s:7:\"GERMANY\";s:5:\"ustid\";s:0:\"\";s:13:\"billing_text1\";N;s:13:\"billing_text2\";N;s:13:\"billing_text3\";N;s:13:\"billing_text4\";N;s:13:\"billing_text5\";N;s:13:\"billing_text6\";N;s:7:\"orderID\";s:2:\"57\";s:16:\"shipping_company\";s:11:\"shopware AG\";s:19:\"shipping_department\";s:0:\"\";s:19:\"shipping_salutation\";s:2:\"mr\";s:18:\"shipping_firstname\";s:3:\"Max\";s:17:\"shipping_lastname\";s:10:\"Mustermann\";s:15:\"shipping_street\";s:20:\"Mustermannstraße 92\";s:33:\"shipping_additional_address_line1\";N;s:33:\"shipping_additional_address_line2\";N;s:16:\"shipping_zipcode\";s:5:\"48624\";s:13:\"shipping_city\";s:12:\"Schöppingen\";s:16:\"shipping_stateID\";s:1:\"3\";s:18:\"shipping_countryID\";s:1:\"2\";s:16:\"shipping_country\";s:11:\"Deutschland\";s:19:\"shipping_countryiso\";s:2:\"DE\";s:20:\"shipping_countryarea\";s:11:\"deutschland\";s:18:\"shipping_countryen\";s:7:\"GERMANY\";s:14:\"shipping_text1\";N;s:14:\"shipping_text2\";N;s:14:\"shipping_text3\";N;s:14:\"shipping_text4\";N;s:14:\"shipping_text5\";N;s:14:\"shipping_text6\";N;s:2:\"id\";s:1:\"1\";s:8:\"password\";s:0:\"\";s:7:\"encoder\";s:3:\"md5\";s:5:\"email\";s:16:\"test@example.com\";s:6:\"active\";s:1:\"1\";s:11:\"accountmode\";s:1:\"0\";s:15:\"confirmationkey\";s:0:\"\";s:9:\"paymentID\";s:1:\"5\";s:10:\"firstlogin\";s:10:\"2011-11-23\";s:9:\"lastlogin\";s:19:\"2012-01-04 14:12:05\";s:9:\"sessionID\";s:26:\"uiorqd755gaar8dn89ukp178c7\";s:10:\"newsletter\";s:1:\"0\";s:10:\"validation\";s:1:\"0\";s:9:\"affiliate\";s:1:\"0\";s:13:\"customergroup\";s:2:\"EK\";s:13:\"paymentpreset\";s:1:\"0\";s:8:\"language\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:7:\"referer\";s:0:\"\";s:12:\"pricegroupID\";N;s:15:\"internalcomment\";s:0:\"\";s:12:\"failedlogins\";s:1:\"0\";s:11:\"lockeduntil\";N;s:26:\"default_billing_address_id\";s:1:\"1\";s:27:\"default_shipping_address_id\";s:1:\"3\";s:5:\"title\";s:0:\"\";s:10:\"salutation\";s:2:\"mr\";s:9:\"firstname\";s:3:\"Max\";s:8:\"lastname\";s:10:\"Mustermann\";s:8:\"birthday\";N;s:11:\"login_token\";N;s:11:\"preisgruppe\";s:1:\"1\";s:11:\"billing_net\";s:1:\"1\";}s:9:\"sDispatch\";a:2:{s:4:\"name\";s:16:\"Standard Versand\";s:11:\"description\";s:0:\"\";}}',	0),
(71,	NULL,	'document_cancellation',	'{config name=mail}',	'{config name=shopName}',	'Stornorechnung zur Bestellung {$orderNumber}',	'{include file=\"string:{config name=emailheaderplain}\"}\n\nHallo {$sUser.firstname} {$sUser.lastname},\n\nvielen Dank für Ihre Bestellung bei {config name=shopName}. Im Anhang finden Sie die Stornorechnung zu Ihrer Bestellung als PDF.\n{include file=\"string:{config name=emailfooterplain}\"}',	'<div style=\"font-family:arial; font-size:12px;\">\n    {include file=\"string:{config name=emailheaderhtml}\"}\n    <br/><br/>\n    <p>\n        Hallo {$sUser.salutation|salutation} {$sUser.lastname},<br/>\n        <br/>\n        vielen Dank für Ihre Bestellung bei {config name=shopName}. Im Anhang finden Sie die Stornorechnung zu Ihrer Bestellung als PDF.\n    </p>\n    {include file=\"string:{config name=emailfooterhtml}\"}\n</div>',	1,	'',	4,	'a:4:{s:6:\"sOrder\";a:40:{s:7:\"orderID\";s:2:\"57\";s:11:\"ordernumber\";s:5:\"20002\";s:12:\"order_number\";s:5:\"20002\";s:6:\"userID\";s:1:\"1\";s:10:\"customerID\";s:1:\"1\";s:14:\"invoice_amount\";s:6:\"201.86\";s:18:\"invoice_amount_net\";s:6:\"169.63\";s:16:\"invoice_shipping\";s:1:\"0\";s:20:\"invoice_shipping_net\";s:1:\"0\";s:9:\"ordertime\";s:19:\"2012-08-31 08:51:46\";s:6:\"status\";s:1:\"7\";s:8:\"statusID\";s:1:\"7\";s:7:\"cleared\";s:2:\"12\";s:9:\"clearedID\";s:2:\"12\";s:9:\"paymentID\";s:1:\"4\";s:13:\"transactionID\";s:0:\"\";s:7:\"comment\";s:0:\"\";s:15:\"customercomment\";s:0:\"\";s:3:\"net\";s:1:\"0\";s:5:\"netto\";s:1:\"0\";s:9:\"partnerID\";s:0:\"\";s:11:\"temporaryID\";s:0:\"\";s:7:\"referer\";s:0:\"\";s:11:\"cleareddate\";N;s:12:\"cleared_date\";N;s:12:\"trackingcode\";s:0:\"\";s:8:\"language\";s:1:\"1\";s:8:\"currency\";s:3:\"EUR\";s:14:\"currencyFactor\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:10:\"dispatchID\";s:1:\"9\";s:10:\"currencyID\";s:1:\"1\";s:12:\"cleared_name\";s:15:\"completely_paid\";s:19:\"cleared_description\";s:0:\"\";s:11:\"status_name\";s:20:\"completely_delivered\";s:18:\"status_description\";s:0:\"\";s:19:\"payment_description\";s:0:\"\";s:20:\"dispatch_description\";s:0:\"\";s:20:\"currency_description\";s:4:\"Euro\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";s:0:\"\";s:10:\"attribute2\";s:0:\"\";s:10:\"attribute3\";s:0:\"\";s:10:\"attribute4\";s:0:\"\";s:10:\"attribute5\";s:0:\"\";s:10:\"attribute6\";s:0:\"\";}}s:13:\"sOrderDetails\";a:1:{i:0;a:20:{s:14:\"orderdetailsID\";s:3:\"204\";s:7:\"orderID\";s:2:\"57\";s:11:\"ordernumber\";s:5:\"20002\";s:9:\"articleID\";s:3:\"197\";s:18:\"articleordernumber\";s:7:\"SW10196\";s:5:\"price\";s:5:\"34.99\";s:8:\"quantity\";s:1:\"2\";s:7:\"invoice\";s:5:\"69.98\";s:4:\"name\";s:7:\"Artikel\";s:6:\"status\";s:1:\"0\";s:7:\"shipped\";s:1:\"0\";s:12:\"shippedgroup\";s:1:\"0\";s:11:\"releasedate\";s:10:\"0000-00-00\";s:5:\"modus\";s:1:\"0\";s:10:\"esdarticle\";s:1:\"1\";s:5:\"taxID\";s:1:\"1\";s:3:\"tax\";s:5:\"19.00\";s:8:\"tax_rate\";s:2:\"19\";s:3:\"esd\";s:1:\"1\";s:10:\"attributes\";a:6:{s:10:\"attribute1\";s:0:\"\";s:10:\"attribute2\";s:0:\"\";s:10:\"attribute3\";s:0:\"\";s:10:\"attribute4\";s:0:\"\";s:10:\"attribute5\";s:0:\"\";s:10:\"attribute6\";s:0:\"\";}}}s:5:\"sUser\";a:82:{s:15:\"billing_company\";s:11:\"shopware AG\";s:18:\"billing_department\";s:0:\"\";s:18:\"billing_salutation\";s:2:\"mr\";s:14:\"customernumber\";s:5:\"20001\";s:17:\"billing_firstname\";s:3:\"Max\";s:16:\"billing_lastname\";s:10:\"Mustermann\";s:14:\"billing_street\";s:20:\"Mustermannstraße 92\";s:32:\"billing_additional_address_line1\";N;s:32:\"billing_additional_address_line2\";N;s:15:\"billing_zipcode\";s:5:\"48624\";s:12:\"billing_city\";s:12:\"Schöppingen\";s:5:\"phone\";s:0:\"\";s:13:\"billing_phone\";s:0:\"\";s:17:\"billing_countryID\";s:1:\"2\";s:15:\"billing_stateID\";s:1:\"3\";s:15:\"billing_country\";s:11:\"Deutschland\";s:18:\"billing_countryiso\";s:2:\"DE\";s:19:\"billing_countryarea\";s:11:\"deutschland\";s:17:\"billing_countryen\";s:7:\"GERMANY\";s:5:\"ustid\";s:0:\"\";s:13:\"billing_text1\";N;s:13:\"billing_text2\";N;s:13:\"billing_text3\";N;s:13:\"billing_text4\";N;s:13:\"billing_text5\";N;s:13:\"billing_text6\";N;s:7:\"orderID\";s:2:\"57\";s:16:\"shipping_company\";s:11:\"shopware AG\";s:19:\"shipping_department\";s:0:\"\";s:19:\"shipping_salutation\";s:2:\"mr\";s:18:\"shipping_firstname\";s:3:\"Max\";s:17:\"shipping_lastname\";s:10:\"Mustermann\";s:15:\"shipping_street\";s:20:\"Mustermannstraße 92\";s:33:\"shipping_additional_address_line1\";N;s:33:\"shipping_additional_address_line2\";N;s:16:\"shipping_zipcode\";s:5:\"48624\";s:13:\"shipping_city\";s:12:\"Schöppingen\";s:16:\"shipping_stateID\";s:1:\"3\";s:18:\"shipping_countryID\";s:1:\"2\";s:16:\"shipping_country\";s:11:\"Deutschland\";s:19:\"shipping_countryiso\";s:2:\"DE\";s:20:\"shipping_countryarea\";s:11:\"deutschland\";s:18:\"shipping_countryen\";s:7:\"GERMANY\";s:14:\"shipping_text1\";N;s:14:\"shipping_text2\";N;s:14:\"shipping_text3\";N;s:14:\"shipping_text4\";N;s:14:\"shipping_text5\";N;s:14:\"shipping_text6\";N;s:2:\"id\";s:1:\"1\";s:8:\"password\";s:0:\"\";s:7:\"encoder\";s:3:\"md5\";s:5:\"email\";s:16:\"test@example.com\";s:6:\"active\";s:1:\"1\";s:11:\"accountmode\";s:1:\"0\";s:15:\"confirmationkey\";s:0:\"\";s:9:\"paymentID\";s:1:\"5\";s:10:\"firstlogin\";s:10:\"2011-11-23\";s:9:\"lastlogin\";s:19:\"2012-01-04 14:12:05\";s:9:\"sessionID\";s:26:\"uiorqd755gaar8dn89ukp178c7\";s:10:\"newsletter\";s:1:\"0\";s:10:\"validation\";s:1:\"0\";s:9:\"affiliate\";s:1:\"0\";s:13:\"customergroup\";s:2:\"EK\";s:13:\"paymentpreset\";s:1:\"0\";s:8:\"language\";s:1:\"1\";s:9:\"subshopID\";s:1:\"1\";s:7:\"referer\";s:0:\"\";s:12:\"pricegroupID\";N;s:15:\"internalcomment\";s:0:\"\";s:12:\"failedlogins\";s:1:\"0\";s:11:\"lockeduntil\";N;s:26:\"default_billing_address_id\";s:1:\"1\";s:27:\"default_shipping_address_id\";s:1:\"3\";s:5:\"title\";s:0:\"\";s:10:\"salutation\";s:2:\"mr\";s:9:\"firstname\";s:3:\"Max\";s:8:\"lastname\";s:10:\"Mustermann\";s:8:\"birthday\";N;s:11:\"login_token\";N;s:11:\"preisgruppe\";s:1:\"1\";s:11:\"billing_net\";s:1:\"1\";}s:9:\"sDispatch\";a:2:{s:4:\"name\";s:16:\"Standard Versand\";s:11:\"description\";s:0:\"\";}}',	0);

DROP TABLE IF EXISTS `s_core_config_mails_attachments`;
CREATE TABLE `s_core_config_mails_attachments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mailID` int NOT NULL,
  `mediaID` int NOT NULL,
  `shopID` int DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `mailID` (`mailID`,`mediaID`,`shopID`),
  KEY `mediaID` (`mediaID`),
  KEY `shopID` (`shopID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_core_config_mails_attributes`;
CREATE TABLE `s_core_config_mails_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mailID` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mailID` (`mailID`),
  CONSTRAINT `s_core_config_mails_attributes_ibfk_1` FOREIGN KEY (`mailID`) REFERENCES `s_core_config_mails` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_core_config_values`;
CREATE TABLE `s_core_config_values` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `element_id` int unsigned NOT NULL,
  `shop_id` int unsigned DEFAULT NULL,
  `value` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `element_id_shop_id` (`element_id`,`shop_id`),
  KEY `shop_id` (`shop_id`),
  KEY `element_id` (`element_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_config_values` (`id`, `element_id`, `shop_id`, `value`) VALUES
(3,	848,	1,	's:33:\"fqn3u9a5zq9b9gwfss0h5gj9btff2aguy\";'),
(4,	966,	1,	's:32:\"tkePJAFgVB7kczNjdhStwxUUnZWyTPoD\";'),
(5,	1020,	1,	'i:1770024154;'),
(6,	673,	1,	's:13:\"swagfiveseven\";'),
(7,	674,	1,	's:20:\"testing@shopware.com\";'),
(8,	675,	1,	's:0:\"\";'),
(9,	677,	1,	's:0:\"\";'),
(10,	893,	1,	's:0:\"\";'),
(11,	999,	1,	'b:0;'),
(12,	852,	1,	's:19:\"2026-02-05 14:24:35\";'),
(15,	658,	1,	's:19:\"2026-02-05 15:24:38\";');

DROP TABLE IF EXISTS `s_core_countries`;
CREATE TABLE `s_core_countries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `countryname` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `countryiso` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `areaID` int DEFAULT NULL,
  `countryen` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `position` int DEFAULT NULL,
  `notice` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `taxfree` int DEFAULT NULL,
  `taxfree_ustid` int DEFAULT NULL,
  `taxfree_ustid_checked` int DEFAULT NULL,
  `active` int DEFAULT NULL,
  `iso3` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `display_state_in_registration` int NOT NULL,
  `force_state_in_registration` int NOT NULL,
  `allow_shipping` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `areaID` (`areaID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_countries` (`id`, `countryname`, `countryiso`, `areaID`, `countryen`, `position`, `notice`, `taxfree`, `taxfree_ustid`, `taxfree_ustid_checked`, `active`, `iso3`, `display_state_in_registration`, `force_state_in_registration`, `allow_shipping`) VALUES
(2,	'Deutschland',	'DE',	1,	'GERMANY',	1,	'',	0,	0,	0,	1,	'DEU',	0,	0,	1),
(3,	'Arabische Emirate',	'AE',	2,	'ARAB EMIRATES',	10,	'',	0,	0,	0,	0,	'ARE',	0,	0,	1),
(4,	'Australien',	'AU',	2,	'AUSTRALIA',	10,	'',	0,	0,	0,	0,	'AUS',	0,	0,	1),
(5,	'Belgien',	'BE',	3,	'BELGIUM',	10,	'',	0,	0,	0,	0,	'BEL',	0,	0,	1),
(7,	'Dänemark',	'DK',	3,	'DENMARK',	10,	'',	0,	0,	0,	0,	'DNK',	0,	0,	1),
(8,	'Finnland',	'FI',	3,	'FINLAND',	10,	'',	0,	0,	0,	0,	'FIN',	0,	0,	1),
(9,	'Frankreich',	'FR',	3,	'FRANCE',	10,	'',	0,	0,	0,	0,	'FRA',	0,	0,	1),
(10,	'Griechenland',	'GR',	3,	'GREECE',	10,	'',	0,	0,	0,	0,	'GRC',	0,	0,	1),
(11,	'Großbritannien',	'GB',	3,	'GREAT BRITAIN',	10,	'',	0,	0,	0,	0,	'GBR',	0,	0,	1),
(12,	'Irland',	'IE',	3,	'IRELAND',	10,	'',	0,	0,	0,	0,	'IRL',	0,	0,	1),
(13,	'Island',	'IS',	3,	'ICELAND',	10,	'',	0,	0,	0,	0,	'ISL',	0,	0,	1),
(14,	'Italien',	'IT',	3,	'ITALY',	10,	'',	0,	0,	0,	0,	'ITA',	0,	0,	1),
(15,	'Japan',	'JP',	2,	'JAPAN',	10,	'',	0,	0,	0,	0,	'JPN',	0,	0,	1),
(16,	'Kanada',	'CA',	2,	'CANADA',	10,	'',	0,	0,	0,	0,	'CAN',	0,	0,	1),
(18,	'Luxemburg',	'LU',	3,	'LUXEMBOURG',	10,	'',	0,	0,	0,	0,	'LUX',	0,	0,	1),
(20,	'Namibia',	'NA',	2,	'NAMIBIA',	10,	'',	0,	0,	0,	0,	'NAM',	0,	0,	1),
(21,	'Niederlande',	'NL',	3,	'NETHERLANDS',	10,	'',	0,	0,	0,	0,	'NLD',	0,	0,	1),
(22,	'Norwegen',	'NO',	3,	'NORWAY',	10,	'',	0,	0,	0,	0,	'NOR',	0,	0,	1),
(23,	'Österreich',	'AT',	3,	'AUSTRIA',	1,	'',	0,	0,	0,	0,	'AUT',	0,	0,	1),
(24,	'Portugal',	'PT',	3,	'PORTUGAL',	10,	'',	0,	0,	0,	0,	'PRT',	0,	0,	1),
(25,	'Schweden',	'SE',	3,	'SWEDEN',	10,	'',	0,	0,	0,	0,	'SWE',	0,	0,	1),
(26,	'Schweiz',	'CH',	3,	'SWITZERLAND',	10,	'',	1,	0,	0,	0,	'CHE',	0,	0,	1),
(27,	'Spanien',	'ES',	3,	'SPAIN',	10,	'',	0,	0,	0,	0,	'ESP',	0,	0,	1),
(28,	'USA',	'US',	2,	'USA',	10,	'',	0,	0,	0,	0,	'USA',	0,	0,	1),
(29,	'Liechtenstein',	'LI',	3,	'LIECHTENSTEIN',	10,	'',	0,	0,	0,	0,	'LIE',	0,	0,	1),
(30,	'Polen',	'PL',	3,	'POLAND',	10,	'',	0,	0,	0,	0,	'POL',	0,	0,	1),
(31,	'Ungarn',	'HU',	3,	'HUNGARY',	10,	'',	0,	0,	0,	0,	'HUN',	0,	0,	1),
(32,	'Türkei',	'TR',	2,	'TURKEY',	10,	'',	0,	0,	0,	0,	'TUR',	0,	0,	1),
(33,	'Tschechien',	'CZ',	3,	'CZECH REPUBLIC',	10,	'',	0,	0,	0,	0,	'CZE',	0,	0,	1),
(34,	'Slowakei',	'SK',	3,	'SLOVAKIA',	10,	'',	0,	0,	0,	0,	'SVK',	0,	0,	1),
(35,	'Rum&auml;nien',	'RO',	3,	'ROMANIA',	10,	'',	0,	0,	0,	0,	'ROU',	0,	0,	1),
(36,	'Brasilien',	'BR',	2,	'BRAZIL',	10,	'',	0,	0,	0,	0,	'BRA',	0,	0,	1),
(37,	'Israel',	'IL',	2,	'ISRAEL',	10,	'',	0,	0,	0,	0,	'ISR',	0,	0,	1),
(38,	'Bulgarien',	'BG',	3,	'BULGARIA',	10,	'',	0,	0,	0,	0,	'BGR',	0,	0,	1),
(39,	'Estland',	'EE',	3,	'ESTONIA',	10,	'',	0,	0,	0,	0,	'EST',	0,	0,	1),
(40,	'Kroatien',	'HR',	3,	'CROATIA',	10,	'',	0,	0,	0,	0,	'HRV',	0,	0,	1),
(41,	'Lettland',	'LV',	3,	'LATVIA',	10,	'',	0,	0,	0,	0,	'LVA',	0,	0,	1),
(42,	'Litauen',	'LT',	3,	'LITHUANIA',	10,	'',	0,	0,	0,	0,	'LTU',	0,	0,	1),
(43,	'Malta',	'MT',	3,	'MALTA',	10,	'',	0,	0,	0,	0,	'MLT',	0,	0,	1),
(44,	'Slowenien',	'SI',	3,	'SLOVENIA',	10,	'',	0,	0,	0,	0,	'SVN',	0,	0,	1),
(45,	'Zypern',	'CY',	3,	'CYPRUS',	10,	'',	0,	0,	0,	0,	'CYP',	0,	0,	1),
(46,	'Afghanistan',	'AF',	2,	'AFGHANISTAN',	10,	'',	0,	0,	0,	0,	'AFG',	0,	0,	1),
(47,	'Åland',	'AX',	2,	'ÅLAND ISLANDS',	10,	'',	0,	0,	0,	0,	'ALA',	0,	0,	1),
(48,	'Albanien',	'AL',	2,	'ALBANIA',	10,	'',	0,	0,	0,	0,	'ALB',	0,	0,	1),
(49,	'Algerien',	'DZ',	2,	'ALGERIA',	10,	'',	0,	0,	0,	0,	'DZA',	0,	0,	1),
(50,	'Amerikanisch-Samoa',	'AS',	2,	'AMERICAN SAMOA',	10,	'',	0,	0,	0,	0,	'ASM',	0,	0,	1),
(51,	'Andorra',	'AD',	2,	'ANDORRA',	10,	'',	0,	0,	0,	0,	'AND',	0,	0,	1),
(52,	'Angola',	'AO',	2,	'ANGOLA',	10,	'',	0,	0,	0,	0,	'AGO',	0,	0,	1),
(53,	'Anguilla',	'AI',	2,	'ANGUILLA',	10,	'',	0,	0,	0,	0,	'AIA',	0,	0,	1),
(54,	'Antarktika',	'AQ',	2,	'ANTARCTICA',	10,	'',	0,	0,	0,	0,	'ATA',	0,	0,	1),
(55,	'Antigua und Barbuda',	'AG',	2,	'ANTIGUA AND BARBUDA',	10,	'',	0,	0,	0,	0,	'ATG',	0,	0,	1),
(56,	'Argentinien',	'AR',	2,	'ARGENTINA',	10,	'',	0,	0,	0,	0,	'ARG',	0,	0,	1),
(57,	'Armenien',	'AM',	2,	'ARMENIA',	10,	'',	0,	0,	0,	0,	'ARM',	0,	0,	1),
(58,	'Aruba',	'AW',	2,	'ARUBA',	10,	'',	0,	0,	0,	0,	'ABW',	0,	0,	1),
(59,	'Aserbaidschan',	'AZ',	2,	'AZERBAIJAN',	10,	'',	0,	0,	0,	0,	'AZE',	0,	0,	1),
(60,	'Bahamas',	'BS',	2,	'BAHAMAS',	10,	'',	0,	0,	0,	0,	'BHS',	0,	0,	1),
(61,	'Bahrain',	'BH',	2,	'BAHRAIN',	10,	'',	0,	0,	0,	0,	'BHR',	0,	0,	1),
(62,	'Bangladesch',	'BD',	2,	'BANGLADESH',	10,	'',	0,	0,	0,	0,	'BGD',	0,	0,	1),
(63,	'Barbados',	'BB',	2,	'BARBADOS',	10,	'',	0,	0,	0,	0,	'BRB',	0,	0,	1),
(64,	'Weißrussland',	'BY',	2,	'BELARUS',	10,	'',	0,	0,	0,	0,	'BLR',	0,	0,	1),
(65,	'Belize',	'BZ',	2,	'BELIZE',	10,	'',	0,	0,	0,	0,	'BLZ',	0,	0,	1),
(66,	'Benin',	'BJ',	2,	'BENIN',	10,	'',	0,	0,	0,	0,	'BEN',	0,	0,	1),
(67,	'Bermuda',	'BM',	2,	'BERMUDA',	10,	'',	0,	0,	0,	0,	'BMU',	0,	0,	1),
(68,	'Bhutan',	'BT',	2,	'BHUTAN',	10,	'',	0,	0,	0,	0,	'BTN',	0,	0,	1),
(69,	'Bolivien',	'BO',	2,	'BOLIVIA (PLURINATIONAL STATE OF)',	10,	'',	0,	0,	0,	0,	'BOL',	0,	0,	1),
(70,	'Bonaire, Sint Eustatius und Saba',	'BQ',	2,	'BONAIRE, SINT EUSTATIUS AND SABA',	10,	'',	0,	0,	0,	0,	'BES',	0,	0,	1),
(71,	'Bosnien und Herzegowina',	'BA',	2,	'BOSNIA AND HERZEGOVINA',	10,	'',	0,	0,	0,	0,	'BIH',	0,	0,	1),
(72,	'Botswana',	'BW',	2,	'BOTSWANA',	10,	'',	0,	0,	0,	0,	'BWA',	0,	0,	1),
(73,	'Bouvetinsel',	'BV',	2,	'BOUVET ISLAND',	10,	'',	0,	0,	0,	0,	'BVT',	0,	0,	1),
(74,	'Britisches Territorium im Indischen Ozean',	'IO',	2,	'BRITISH INDIAN OCEAN TERRITORY',	10,	'',	0,	0,	0,	0,	'IOT',	0,	0,	1),
(75,	'Kleinere Inselbesitzungen der Vereinigten Staaten',	'UM',	2,	'UNITED STATES MINOR OUTLYING ISLANDS',	10,	'',	0,	0,	0,	0,	'UMI',	0,	0,	1),
(76,	'Britische Jungferninseln',	'VG',	2,	'VIRGIN ISLANDS (BRITISH)',	10,	'',	0,	0,	0,	0,	'VGB',	0,	0,	1),
(77,	'Amerikanische Jungferninseln',	'VI',	2,	'VIRGIN ISLANDS (U.S.)',	10,	'',	0,	0,	0,	0,	'VIR',	0,	0,	1),
(78,	'Brunei',	'BN',	2,	'BRUNEI DARUSSALAM',	10,	'',	0,	0,	0,	0,	'BRN',	0,	0,	1),
(79,	'Burkina Faso',	'BF',	2,	'BURKINA FASO',	10,	'',	0,	0,	0,	0,	'BFA',	0,	0,	1),
(80,	'Burundi',	'BI',	2,	'BURUNDI',	10,	'',	0,	0,	0,	0,	'BDI',	0,	0,	1),
(81,	'Kambodscha',	'KH',	2,	'CAMBODIA',	10,	'',	0,	0,	0,	0,	'KHM',	0,	0,	1),
(82,	'Kamerun',	'CM',	2,	'CAMEROON',	10,	'',	0,	0,	0,	0,	'CMR',	0,	0,	1),
(83,	'Kap Verde',	'CV',	2,	'CABO VERDE',	10,	'',	0,	0,	0,	0,	'CPV',	0,	0,	1),
(84,	'Kaimaninseln',	'KY',	2,	'CAYMAN ISLANDS',	10,	'',	0,	0,	0,	0,	'CYM',	0,	0,	1),
(85,	'Zentralafrikanische Republik',	'CF',	2,	'CENTRAL AFRICAN REPUBLIC',	10,	'',	0,	0,	0,	0,	'CAF',	0,	0,	1),
(86,	'Tschad',	'TD',	2,	'CHAD',	10,	'',	0,	0,	0,	0,	'TCD',	0,	0,	1),
(87,	'Chile',	'CL',	2,	'CHILE',	10,	'',	0,	0,	0,	0,	'CHL',	0,	0,	1),
(88,	'China',	'CN',	2,	'CHINA',	10,	'',	0,	0,	0,	0,	'CHN',	0,	0,	1),
(89,	'Weihnachtsinsel',	'CX',	2,	'CHRISTMAS ISLAND',	10,	'',	0,	0,	0,	0,	'CXR',	0,	0,	1),
(90,	'Kokosinseln',	'CC',	2,	'COCOS (KEELING) ISLANDS',	10,	'',	0,	0,	0,	0,	'CCK',	0,	0,	1),
(91,	'Kolumbien',	'CO',	2,	'COLOMBIA',	10,	'',	0,	0,	0,	0,	'COL',	0,	0,	1),
(92,	'Union der Komoren',	'KM',	2,	'COMOROS',	10,	'',	0,	0,	0,	0,	'COM',	0,	0,	1),
(93,	'Kongo',	'CG',	2,	'CONGO',	10,	'',	0,	0,	0,	0,	'COG',	0,	0,	1),
(94,	'Kongo (Dem. Rep.)',	'CD',	2,	'CONGO (DEMOCRATIC REPUBLIC OF THE)',	10,	'',	0,	0,	0,	0,	'COD',	0,	0,	1),
(95,	'Cookinseln',	'CK',	2,	'COOK ISLANDS',	10,	'',	0,	0,	0,	0,	'COK',	0,	0,	1),
(96,	'Costa Rica',	'CR',	2,	'COSTA RICA',	10,	'',	0,	0,	0,	0,	'CRI',	0,	0,	1),
(97,	'Kuba',	'CU',	2,	'CUBA',	10,	'',	0,	0,	0,	0,	'CUB',	0,	0,	1),
(98,	'Curaçao',	'CW',	2,	'CURAÇAO',	10,	'',	0,	0,	0,	0,	'CUW',	0,	0,	1),
(99,	'Dschibuti',	'DJ',	2,	'DJIBOUTI',	10,	'',	0,	0,	0,	0,	'DJI',	0,	0,	1),
(100,	'Dominica',	'DM',	2,	'DOMINICA',	10,	'',	0,	0,	0,	0,	'DMA',	0,	0,	1),
(101,	'Dominikanische Republik',	'DO',	2,	'DOMINICAN REPUBLIC',	10,	'',	0,	0,	0,	0,	'DOM',	0,	0,	1),
(102,	'Ecuador',	'EC',	2,	'ECUADOR',	10,	'',	0,	0,	0,	0,	'ECU',	0,	0,	1),
(103,	'Ägypten',	'EG',	2,	'EGYPT',	10,	'',	0,	0,	0,	0,	'EGY',	0,	0,	1),
(104,	'El Salvador',	'SV',	2,	'EL SALVADOR',	10,	'',	0,	0,	0,	0,	'SLV',	0,	0,	1),
(105,	'Äquatorial-Guinea',	'GQ',	2,	'EQUATORIAL GUINEA',	10,	'',	0,	0,	0,	0,	'GNQ',	0,	0,	1),
(106,	'Eritrea',	'ER',	2,	'ERITREA',	10,	'',	0,	0,	0,	0,	'ERI',	0,	0,	1),
(107,	'Äthiopien',	'ET',	2,	'ETHIOPIA',	10,	'',	0,	0,	0,	0,	'ETH',	0,	0,	1),
(108,	'Falklandinseln',	'FK',	2,	'FALKLAND ISLANDS (MALVINAS)',	10,	'',	0,	0,	0,	0,	'FLK',	0,	0,	1),
(109,	'Färöer-Inseln',	'FO',	2,	'FAROE ISLANDS',	10,	'',	0,	0,	0,	0,	'FRO',	0,	0,	1),
(110,	'Fidschi',	'FJ',	2,	'FIJI',	10,	'',	0,	0,	0,	0,	'FJI',	0,	0,	1),
(111,	'Französisch Guyana',	'GF',	2,	'FRENCH GUIANA',	10,	'',	0,	0,	0,	0,	'GUF',	0,	0,	1),
(112,	'Französisch-Polynesien',	'PF',	2,	'FRENCH POLYNESIA',	10,	'',	0,	0,	0,	0,	'PYF',	0,	0,	1),
(113,	'Französische Süd- und Antarktisgebiete',	'TF',	2,	'FRENCH SOUTHERN TERRITORIES',	10,	'',	0,	0,	0,	0,	'ATF',	0,	0,	1),
(114,	'Gabun',	'GA',	2,	'GABON',	10,	'',	0,	0,	0,	0,	'GAB',	0,	0,	1),
(115,	'Gambia',	'GM',	2,	'GAMBIA',	10,	'',	0,	0,	0,	0,	'GMB',	0,	0,	1),
(116,	'Georgien',	'GE',	2,	'GEORGIA',	10,	'',	0,	0,	0,	0,	'GEO',	0,	0,	1),
(117,	'Ghana',	'GH',	2,	'GHANA',	10,	'',	0,	0,	0,	0,	'GHA',	0,	0,	1),
(118,	'Gibraltar',	'GI',	2,	'GIBRALTAR',	10,	'',	0,	0,	0,	0,	'GIB',	0,	0,	1),
(119,	'Grönland',	'GL',	2,	'GREENLAND',	10,	'',	0,	0,	0,	0,	'GRL',	0,	0,	1),
(120,	'Grenada',	'GD',	2,	'GRENADA',	10,	'',	0,	0,	0,	0,	'GRD',	0,	0,	1),
(121,	'Guadeloupe',	'GP',	2,	'GUADELOUPE',	10,	'',	0,	0,	0,	0,	'GLP',	0,	0,	1),
(122,	'Guam',	'GU',	2,	'GUAM',	10,	'',	0,	0,	0,	0,	'GUM',	0,	0,	1),
(123,	'Guatemala',	'GT',	2,	'GUATEMALA',	10,	'',	0,	0,	0,	0,	'GTM',	0,	0,	1),
(124,	'Guernsey',	'GG',	2,	'GUERNSEY',	10,	'',	0,	0,	0,	0,	'GGY',	0,	0,	1),
(125,	'Guinea',	'GN',	2,	'GUINEA',	10,	'',	0,	0,	0,	0,	'GIN',	0,	0,	1),
(126,	'Guinea-Bissau',	'GW',	2,	'GUINEA-BISSAU',	10,	'',	0,	0,	0,	0,	'GNB',	0,	0,	1),
(127,	'Guyana',	'GY',	2,	'GUYANA',	10,	'',	0,	0,	0,	0,	'GUY',	0,	0,	1),
(128,	'Haiti',	'HT',	2,	'HAITI',	10,	'',	0,	0,	0,	0,	'HTI',	0,	0,	1),
(129,	'Heard und die McDonaldinseln',	'HM',	2,	'HEARD ISLAND AND MCDONALD ISLANDS',	10,	'',	0,	0,	0,	0,	'HMD',	0,	0,	1),
(130,	'Heiliger Stuhl',	'VA',	2,	'HOLY SEE',	10,	'',	0,	0,	0,	0,	'VAT',	0,	0,	1),
(131,	'Honduras',	'HN',	2,	'HONDURAS',	10,	'',	0,	0,	0,	0,	'HND',	0,	0,	1),
(132,	'Hong Kong',	'HK',	2,	'HONG KONG',	10,	'',	0,	0,	0,	0,	'HKG',	0,	0,	1),
(133,	'Indien',	'IN',	2,	'INDIA',	10,	'',	0,	0,	0,	0,	'IND',	0,	0,	1),
(134,	'Indonesien',	'ID',	2,	'INDONESIA',	10,	'',	0,	0,	0,	0,	'IDN',	0,	0,	1),
(135,	'Elfenbeinküste',	'CI',	2,	'CÔTE D\'IVOIRE',	10,	'',	0,	0,	0,	0,	'CIV',	0,	0,	1),
(136,	'Iran',	'IR',	2,	'IRAN (ISLAMIC REPUBLIC OF)',	10,	'',	0,	0,	0,	0,	'IRN',	0,	0,	1),
(137,	'Irak',	'IQ',	2,	'IRAQ',	10,	'',	0,	0,	0,	0,	'IRQ',	0,	0,	1),
(138,	'Insel Man',	'IM',	2,	'ISLE OF MAN',	10,	'',	0,	0,	0,	0,	'IMN',	0,	0,	1),
(139,	'Jamaika',	'JM',	2,	'JAMAICA',	10,	'',	0,	0,	0,	0,	'JAM',	0,	0,	1),
(140,	'Jersey',	'JE',	2,	'JERSEY',	10,	'',	0,	0,	0,	0,	'JEY',	0,	0,	1),
(141,	'Jordanien',	'JO',	2,	'JORDAN',	10,	'',	0,	0,	0,	0,	'JOR',	0,	0,	1),
(142,	'Kasachstan',	'KZ',	2,	'KAZAKHSTAN',	10,	'',	0,	0,	0,	0,	'KAZ',	0,	0,	1),
(143,	'Kenia',	'KE',	2,	'KENYA',	10,	'',	0,	0,	0,	0,	'KEN',	0,	0,	1),
(144,	'Kiribati',	'KI',	2,	'KIRIBATI',	10,	'',	0,	0,	0,	0,	'KIR',	0,	0,	1),
(145,	'Kuwait',	'KW',	2,	'KUWAIT',	10,	'',	0,	0,	0,	0,	'KWT',	0,	0,	1),
(146,	'Kirgisistan',	'KG',	2,	'KYRGYZSTAN',	10,	'',	0,	0,	0,	0,	'KGZ',	0,	0,	1),
(147,	'Laos',	'LA',	2,	'LAO PEOPLE\'S DEMOCRATIC REPUBLIC',	10,	'',	0,	0,	0,	0,	'LAO',	0,	0,	1),
(148,	'Libanon',	'LB',	2,	'LEBANON',	10,	'',	0,	0,	0,	0,	'LBN',	0,	0,	1),
(149,	'Lesotho',	'LS',	2,	'LESOTHO',	10,	'',	0,	0,	0,	0,	'LSO',	0,	0,	1),
(150,	'Liberia',	'LR',	2,	'LIBERIA',	10,	'',	0,	0,	0,	0,	'LBR',	0,	0,	1),
(151,	'Libyen',	'LY',	2,	'LIBYA',	10,	'',	0,	0,	0,	0,	'LBY',	0,	0,	1),
(152,	'Macao',	'MO',	2,	'MACAO',	10,	'',	0,	0,	0,	0,	'MAC',	0,	0,	1),
(153,	'Mazedonien',	'MK',	2,	'MACEDONIA (THE FORMER YUGOSLAV REPUBLIC OF)',	10,	'',	0,	0,	0,	0,	'MKD',	0,	0,	1),
(154,	'Madagaskar',	'MG',	2,	'MADAGASCAR',	10,	'',	0,	0,	0,	0,	'MDG',	0,	0,	1),
(155,	'Malawi',	'MW',	2,	'MALAWI',	10,	'',	0,	0,	0,	0,	'MWI',	0,	0,	1),
(156,	'Malaysia',	'MY',	2,	'MALAYSIA',	10,	'',	0,	0,	0,	0,	'MYS',	0,	0,	1),
(157,	'Malediven',	'MV',	2,	'MALDIVES',	10,	'',	0,	0,	0,	0,	'MDV',	0,	0,	1),
(158,	'Mali',	'ML',	2,	'MALI',	10,	'',	0,	0,	0,	0,	'MLI',	0,	0,	1),
(159,	'Marshallinseln',	'MH',	2,	'MARSHALL ISLANDS',	10,	'',	0,	0,	0,	0,	'MHL',	0,	0,	1),
(160,	'Martinique',	'MQ',	2,	'MARTINIQUE',	10,	'',	0,	0,	0,	0,	'MTQ',	0,	0,	1),
(161,	'Mauretanien',	'MR',	2,	'MAURITANIA',	10,	'',	0,	0,	0,	0,	'MRT',	0,	0,	1),
(162,	'Mauritius',	'MU',	2,	'MAURITIUS',	10,	'',	0,	0,	0,	0,	'MUS',	0,	0,	1),
(163,	'Mayotte',	'YT',	2,	'MAYOTTE',	10,	'',	0,	0,	0,	0,	'MYT',	0,	0,	1),
(164,	'Mexiko',	'MX',	2,	'MEXICO',	10,	'',	0,	0,	0,	0,	'MEX',	0,	0,	1),
(165,	'Mikronesien',	'FM',	2,	'MICRONESIA (FEDERATED STATES OF)',	10,	'',	0,	0,	0,	0,	'FSM',	0,	0,	1),
(166,	'Moldawie',	'MD',	2,	'MOLDOVA (REPUBLIC OF)',	10,	'',	0,	0,	0,	0,	'MDA',	0,	0,	1),
(167,	'Monaco',	'MC',	2,	'MONACO',	10,	'',	0,	0,	0,	0,	'MCO',	0,	0,	1),
(168,	'Mongolei',	'MN',	2,	'MONGOLIA',	10,	'',	0,	0,	0,	0,	'MNG',	0,	0,	1),
(169,	'Montenegro',	'ME',	2,	'MONTENEGRO',	10,	'',	0,	0,	0,	0,	'MNE',	0,	0,	1),
(170,	'Montserrat',	'MS',	2,	'MONTSERRAT',	10,	'',	0,	0,	0,	0,	'MSR',	0,	0,	1),
(171,	'Marokko',	'MA',	2,	'MOROCCO',	10,	'',	0,	0,	0,	0,	'MAR',	0,	0,	1),
(172,	'Mosambik',	'MZ',	2,	'MOZAMBIQUE',	10,	'',	0,	0,	0,	0,	'MOZ',	0,	0,	1),
(173,	'Myanmar',	'MM',	2,	'MYANMAR',	10,	'',	0,	0,	0,	0,	'MMR',	0,	0,	1),
(174,	'Nauru',	'NR',	2,	'NAURU',	10,	'',	0,	0,	0,	0,	'NRU',	0,	0,	1),
(175,	'Népal',	'NP',	2,	'NEPAL',	10,	'',	0,	0,	0,	0,	'NPL',	0,	0,	1),
(176,	'Neukaledonien',	'NC',	2,	'NEW CALEDONIA',	10,	'',	0,	0,	0,	0,	'NCL',	0,	0,	1),
(177,	'Neuseeland',	'NZ',	2,	'NEW ZEALAND',	10,	'',	0,	0,	0,	0,	'NZL',	0,	0,	1),
(178,	'Nicaragua',	'NI',	2,	'NICARAGUA',	10,	'',	0,	0,	0,	0,	'NIC',	0,	0,	1),
(179,	'Niger',	'NE',	2,	'NIGER',	10,	'',	0,	0,	0,	0,	'NER',	0,	0,	1),
(180,	'Nigeria',	'NG',	2,	'NIGERIA',	10,	'',	0,	0,	0,	0,	'NGA',	0,	0,	1),
(181,	'Niue',	'NU',	2,	'NIUE',	10,	'',	0,	0,	0,	0,	'NIU',	0,	0,	1),
(182,	'Norfolkinsel',	'NF',	2,	'NORFOLK ISLAND',	10,	'',	0,	0,	0,	0,	'NFK',	0,	0,	1),
(183,	'Nordkorea',	'KP',	2,	'KOREA (DEMOCRATIC PEOPLE\'S REPUBLIC OF)',	10,	'',	0,	0,	0,	0,	'PRK',	0,	0,	1),
(184,	'Nördliche Marianen',	'MP',	2,	'NORTHERN MARIANA ISLANDS',	10,	'',	0,	0,	0,	0,	'MNP',	0,	0,	1),
(185,	'Oman',	'OM',	2,	'OMAN',	10,	'',	0,	0,	0,	0,	'OMN',	0,	0,	1),
(186,	'Pakistan',	'PK',	2,	'PAKISTAN',	10,	'',	0,	0,	0,	0,	'PAK',	0,	0,	1),
(187,	'Palau',	'PW',	2,	'PALAU',	10,	'',	0,	0,	0,	0,	'PLW',	0,	0,	1),
(188,	'Palästina',	'PS',	2,	'PALESTINE, STATE OF',	10,	'',	0,	0,	0,	0,	'PSE',	0,	0,	1),
(189,	'Panama',	'PA',	2,	'PANAMA',	10,	'',	0,	0,	0,	0,	'PAN',	0,	0,	1),
(190,	'Papua-Neuguinea',	'PG',	2,	'PAPUA NEW GUINEA',	10,	'',	0,	0,	0,	0,	'PNG',	0,	0,	1),
(191,	'Paraguay',	'PY',	2,	'PARAGUAY',	10,	'',	0,	0,	0,	0,	'PRY',	0,	0,	1),
(192,	'Peru',	'PE',	2,	'PERU',	10,	'',	0,	0,	0,	0,	'PER',	0,	0,	1),
(193,	'Philippinen',	'PH',	2,	'PHILIPPINES',	10,	'',	0,	0,	0,	0,	'PHL',	0,	0,	1),
(194,	'Pitcairn',	'PN',	2,	'PITCAIRN',	10,	'',	0,	0,	0,	0,	'PCN',	0,	0,	1),
(195,	'Puerto Rico',	'PR',	2,	'PUERTO RICO',	10,	'',	0,	0,	0,	0,	'PRI',	0,	0,	1),
(196,	'Katar',	'QA',	2,	'QATAR',	10,	'',	0,	0,	0,	0,	'QAT',	0,	0,	1),
(197,	'Republic of Kosovo',	'XK',	2,	'REPUBLIC OF KOSOVO',	10,	'',	0,	0,	0,	0,	'KOS',	0,	0,	1),
(198,	'Réunion',	'RE',	2,	'RÉUNION',	10,	'',	0,	0,	0,	0,	'REU',	0,	0,	1),
(199,	'Russland',	'RU',	2,	'RUSSIAN FEDERATION',	10,	'',	0,	0,	0,	0,	'RUS',	0,	0,	1),
(200,	'Ruanda',	'RW',	2,	'RWANDA',	10,	'',	0,	0,	0,	0,	'RWA',	0,	0,	1),
(201,	'Saint-Barthélemy',	'BL',	2,	'SAINT BARTHÉLEMY',	10,	'',	0,	0,	0,	0,	'BLM',	0,	0,	1),
(202,	'Sankt Helena',	'SH',	2,	'SAINT HELENA, ASCENSION AND TRISTAN DA CUNHA',	10,	'',	0,	0,	0,	0,	'SHN',	0,	0,	1),
(203,	'St. Kitts und Nevis',	'KN',	2,	'SAINT KITTS AND NEVIS',	10,	'',	0,	0,	0,	0,	'KNA',	0,	0,	1),
(204,	'Saint Lucia',	'LC',	2,	'SAINT LUCIA',	10,	'',	0,	0,	0,	0,	'LCA',	0,	0,	1),
(205,	'Saint Martin',	'MF',	2,	'SAINT MARTIN (FRENCH PART)',	10,	'',	0,	0,	0,	0,	'MAF',	0,	0,	1),
(206,	'Saint-Pierre und Miquelon',	'PM',	2,	'SAINT PIERRE AND MIQUELON',	10,	'',	0,	0,	0,	0,	'SPM',	0,	0,	1),
(207,	'Saint Vincent und die Grenadinen',	'VC',	2,	'SAINT VINCENT AND THE GRENADINES',	10,	'',	0,	0,	0,	0,	'VCT',	0,	0,	1),
(208,	'Samoa',	'WS',	2,	'SAMOA',	10,	'',	0,	0,	0,	0,	'WSM',	0,	0,	1),
(209,	'San Marino',	'SM',	2,	'SAN MARINO',	10,	'',	0,	0,	0,	0,	'SMR',	0,	0,	1),
(210,	'São Tomé und Príncipe',	'ST',	2,	'SAO TOME AND PRINCIPE',	10,	'',	0,	0,	0,	0,	'STP',	0,	0,	1),
(211,	'Saudi-Arabien',	'SA',	2,	'SAUDI ARABIA',	10,	'',	0,	0,	0,	0,	'SAU',	0,	0,	1),
(212,	'Senegal',	'SN',	2,	'SENEGAL',	10,	'',	0,	0,	0,	0,	'SEN',	0,	0,	1),
(213,	'Serbien',	'RS',	2,	'SERBIA',	10,	'',	0,	0,	0,	0,	'SRB',	0,	0,	1),
(214,	'Seychellen',	'SC',	2,	'SEYCHELLES',	10,	'',	0,	0,	0,	0,	'SYC',	0,	0,	1),
(215,	'Sierra Leone',	'SL',	2,	'SIERRA LEONE',	10,	'',	0,	0,	0,	0,	'SLE',	0,	0,	1),
(216,	'Singapur',	'SG',	2,	'SINGAPORE',	10,	'',	0,	0,	0,	0,	'SGP',	0,	0,	1),
(217,	'Sint Maarten (niederl. Teil)',	'SX',	2,	'SINT MAARTEN (DUTCH PART)',	10,	'',	0,	0,	0,	0,	'SXM',	0,	0,	1),
(218,	'Salomonen',	'SB',	2,	'SOLOMON ISLANDS',	10,	'',	0,	0,	0,	0,	'SLB',	0,	0,	1),
(219,	'Somalia',	'SO',	2,	'SOMALIA',	10,	'',	0,	0,	0,	0,	'SOM',	0,	0,	1),
(220,	'Republik Südafrika',	'ZA',	2,	'SOUTH AFRICA',	10,	'',	0,	0,	0,	0,	'ZAF',	0,	0,	1),
(221,	'Südgeorgien und die Südlichen Sandwichinseln',	'GS',	2,	'SOUTH GEORGIA AND THE SOUTH SANDWICH ISLANDS',	10,	'',	0,	0,	0,	0,	'SGS',	0,	0,	1),
(222,	'Südkorea',	'KR',	2,	'KOREA (REPUBLIC OF)',	10,	'',	0,	0,	0,	0,	'KOR',	0,	0,	1),
(223,	'Südsudan',	'SS',	2,	'SOUTH SUDAN',	10,	'',	0,	0,	0,	0,	'SSD',	0,	0,	1),
(224,	'Sri Lanka',	'LK',	2,	'SRI LANKA',	10,	'',	0,	0,	0,	0,	'LKA',	0,	0,	1),
(225,	'Sudan',	'SD',	2,	'SUDAN',	10,	'',	0,	0,	0,	0,	'SDN',	0,	0,	1),
(226,	'Suriname',	'SR',	2,	'SURINAME',	10,	'',	0,	0,	0,	0,	'SUR',	0,	0,	1),
(227,	'Svalbard und Jan Mayen',	'SJ',	2,	'SVALBARD AND JAN MAYEN',	10,	'',	0,	0,	0,	0,	'SJM',	0,	0,	1),
(228,	'Swasiland',	'SZ',	2,	'SWAZILAND',	10,	'',	0,	0,	0,	0,	'SWZ',	0,	0,	1),
(229,	'Syrien',	'SY',	2,	'SYRIAN ARAB REPUBLIC',	10,	'',	0,	0,	0,	0,	'SYR',	0,	0,	1),
(230,	'Taiwan',	'TW',	2,	'TAIWAN',	10,	'',	0,	0,	0,	0,	'TWN',	0,	0,	1),
(231,	'Tadschikistan',	'TJ',	2,	'TAJIKISTAN',	10,	'',	0,	0,	0,	0,	'TJK',	0,	0,	1),
(232,	'Tansania',	'TZ',	2,	'TANZANIA, UNITED REPUBLIC OF',	10,	'',	0,	0,	0,	0,	'TZA',	0,	0,	1),
(233,	'Thailand',	'TH',	2,	'THAILAND',	10,	'',	0,	0,	0,	0,	'THA',	0,	0,	1),
(234,	'Timor-Leste',	'TL',	2,	'TIMOR-LESTE',	10,	'',	0,	0,	0,	0,	'TLS',	0,	0,	1),
(235,	'Togo',	'TG',	2,	'TOGO',	10,	'',	0,	0,	0,	0,	'TGO',	0,	0,	1),
(236,	'Tokelau',	'TK',	2,	'TOKELAU',	10,	'',	0,	0,	0,	0,	'TKL',	0,	0,	1),
(237,	'Tonga',	'TO',	2,	'TONGA',	10,	'',	0,	0,	0,	0,	'TON',	0,	0,	1),
(238,	'Trinidad und Tobago',	'TT',	2,	'TRINIDAD AND TOBAGO',	10,	'',	0,	0,	0,	0,	'TTO',	0,	0,	1),
(239,	'Tunesien',	'TN',	2,	'TUNISIA',	10,	'',	0,	0,	0,	0,	'TUN',	0,	0,	1),
(240,	'Turkmenistan',	'TM',	2,	'TURKMENISTAN',	10,	'',	0,	0,	0,	0,	'TKM',	0,	0,	1),
(241,	'Turks- und Caicosinseln',	'TC',	2,	'TURKS AND CAICOS ISLANDS',	10,	'',	0,	0,	0,	0,	'TCA',	0,	0,	1),
(242,	'Tuvalu',	'TV',	2,	'TUVALU',	10,	'',	0,	0,	0,	0,	'TUV',	0,	0,	1),
(243,	'Uganda',	'UG',	2,	'UGANDA',	10,	'',	0,	0,	0,	0,	'UGA',	0,	0,	1),
(244,	'Ukraine',	'UA',	2,	'UKRAINE',	10,	'',	0,	0,	0,	0,	'UKR',	0,	0,	1),
(245,	'Uruguay',	'UY',	2,	'URUGUAY',	10,	'',	0,	0,	0,	0,	'URY',	0,	0,	1),
(246,	'Usbekistan',	'UZ',	2,	'UZBEKISTAN',	10,	'',	0,	0,	0,	0,	'UZB',	0,	0,	1),
(247,	'Vanuatu',	'VU',	2,	'VANUATU',	10,	'',	0,	0,	0,	0,	'VUT',	0,	0,	1),
(248,	'Venezuela',	'VE',	2,	'VENEZUELA (BOLIVARIAN REPUBLIC OF)',	10,	'',	0,	0,	0,	0,	'VEN',	0,	0,	1),
(249,	'Vietnam',	'VN',	2,	'VIET NAM',	10,	'',	0,	0,	0,	0,	'VNM',	0,	0,	1),
(250,	'Wallis und Futuna',	'WF',	2,	'WALLIS AND FUTUNA',	10,	'',	0,	0,	0,	0,	'WLF',	0,	0,	1),
(251,	'Westsahara',	'EH',	2,	'WESTERN SAHARA',	10,	'',	0,	0,	0,	0,	'ESH',	0,	0,	1),
(252,	'Jemen',	'YE',	2,	'YEMEN',	10,	'',	0,	0,	0,	0,	'YEM',	0,	0,	1),
(253,	'Sambia',	'ZM',	2,	'ZAMBIA',	10,	'',	0,	0,	0,	0,	'ZMB',	0,	0,	1),
(254,	'Simbabwe',	'ZW',	2,	'ZIMBABWE',	10,	'',	0,	0,	0,	0,	'ZWE',	0,	0,	1);

DROP TABLE IF EXISTS `s_core_countries_areas`;
CREATE TABLE `s_core_countries_areas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `active` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_countries_areas` (`id`, `name`, `active`) VALUES
(1,	'deutschland',	1),
(2,	'welt',	1),
(3,	'europa',	1);

DROP TABLE IF EXISTS `s_core_countries_attributes`;
CREATE TABLE `s_core_countries_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `countryID` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `countryID` (`countryID`),
  CONSTRAINT `s_core_countries_attributes_ibfk_1` FOREIGN KEY (`countryID`) REFERENCES `s_core_countries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_core_countries_states`;
CREATE TABLE `s_core_countries_states` (
  `id` int NOT NULL AUTO_INCREMENT,
  `countryID` int DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `shortcode` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `position` int DEFAULT NULL,
  `active` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `countryID` (`countryID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_countries_states` (`id`, `countryID`, `name`, `shortcode`, `position`, `active`) VALUES
(2,	2,	'Niedersachsen',	'NI',	0,	1),
(3,	2,	'Nordrhein-Westfalen',	'NW',	0,	1),
(5,	2,	'Baden-Württemberg',	'BW',	0,	1),
(6,	2,	'Bayern',	'BY',	0,	1),
(7,	2,	'Berlin',	'BE',	0,	1),
(8,	2,	'Brandenburg',	'BB',	0,	1),
(9,	2,	'Bremen',	'HB',	0,	1),
(10,	2,	'Hamburg',	'HH',	0,	1),
(11,	2,	'Hessen',	'HE',	0,	1),
(12,	2,	'Mecklenburg-Vorpommern',	'MV',	0,	1),
(13,	2,	'Rheinland-Pfalz',	'RP',	0,	1),
(14,	2,	'Saarland',	'SL',	0,	1),
(15,	2,	'Sachsen',	'SN',	0,	1),
(16,	2,	'Sachsen-Anhalt',	'ST',	0,	1),
(17,	2,	'Schleswig-Holstein',	'SH',	0,	1),
(18,	2,	'Thüringen',	'TH',	0,	1),
(20,	28,	'Alabama',	'AL',	0,	1),
(21,	28,	'Alaska',	'AK',	0,	1),
(22,	28,	'Arizona',	'AZ',	0,	1),
(23,	28,	'Arkansas',	'AR',	0,	1),
(24,	28,	'Kalifornien',	'CA',	0,	1),
(25,	28,	'Colorado',	'CO',	0,	1),
(26,	28,	'Connecticut',	'CT',	0,	1),
(27,	28,	'Delaware',	'DE',	0,	1),
(28,	28,	'Florida',	'FL',	0,	1),
(29,	28,	'Georgia',	'GA',	0,	1),
(30,	28,	'Hawaii',	'HI',	0,	1),
(31,	28,	'Idaho',	'ID',	0,	1),
(32,	28,	'Illinois',	'IL',	0,	1),
(33,	28,	'Indiana',	'IN',	0,	1),
(34,	28,	'Iowa',	'IA',	0,	1),
(35,	28,	'Kansas',	'KS',	0,	1),
(36,	28,	'Kentucky',	'KY',	0,	1),
(37,	28,	'Louisiana',	'LA',	0,	1),
(38,	28,	'Maine',	'ME',	0,	1),
(39,	28,	'Maryland',	'MD',	0,	1),
(40,	28,	'Massachusetts',	'MA',	0,	1),
(41,	28,	'Michigan',	'MI',	0,	1),
(42,	28,	'Minnesota',	'MN',	0,	1),
(43,	28,	'Mississippi',	'MS',	0,	1),
(44,	28,	'Missouri',	'MO',	0,	1),
(45,	28,	'Montana',	'MT',	0,	1),
(46,	28,	'Nebraska',	'NE',	0,	1),
(47,	28,	'Nevada',	'NV',	0,	1),
(48,	28,	'New Hampshire',	'NH',	0,	1),
(49,	28,	'New Jersey',	'NJ',	0,	1),
(50,	28,	'New Mexico',	'NM',	0,	1),
(51,	28,	'New York',	'NY',	0,	1),
(52,	28,	'North Carolina',	'NC',	0,	1),
(53,	28,	'North Dakota',	'ND',	0,	1),
(54,	28,	'Ohio',	'OH',	0,	1),
(55,	28,	'Oklahoma',	'OK',	0,	1),
(56,	28,	'Oregon',	'OR',	0,	1),
(57,	28,	'Pennsylvania',	'PA',	0,	1),
(58,	28,	'Rhode Island',	'RI',	0,	1),
(59,	28,	'South Carolina',	'SC',	0,	1),
(60,	28,	'South Dakota',	'SD',	0,	1),
(61,	28,	'Tennessee',	'TN',	0,	1),
(62,	28,	'Texas',	'TX',	0,	1),
(63,	28,	'Utah',	'UT',	0,	1),
(64,	28,	'Vermont',	'VT',	0,	1),
(65,	28,	'Virginia',	'VA',	0,	1),
(66,	28,	'Washington',	'WA',	0,	1),
(67,	28,	'West Virginia',	'WV',	0,	1),
(68,	28,	'Wisconsin',	'WI',	0,	1),
(69,	28,	'Wyoming',	'WY',	0,	1);

DROP TABLE IF EXISTS `s_core_countries_states_attributes`;
CREATE TABLE `s_core_countries_states_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `stateID` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stateID` (`stateID`),
  CONSTRAINT `s_core_countries_states_attributes_ibfk_1` FOREIGN KEY (`stateID`) REFERENCES `s_core_countries_states` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_core_currencies`;
CREATE TABLE `s_core_currencies` (
  `id` int NOT NULL AUTO_INCREMENT,
  `currency` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `standard` int NOT NULL,
  `factor` double NOT NULL,
  `templatechar` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `symbol_position` int unsigned NOT NULL,
  `position` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_currencies` (`id`, `currency`, `name`, `standard`, `factor`, `templatechar`, `symbol_position`, `position`) VALUES
(1,	'EUR',	'Euro',	1,	1,	'&euro;',	0,	0);

DROP TABLE IF EXISTS `s_core_customergroups`;
CREATE TABLE `s_core_customergroups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `groupkey` varchar(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `description` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `tax` int NOT NULL DEFAULT '0',
  `taxinput` int NOT NULL,
  `mode` int NOT NULL,
  `discount` double NOT NULL,
  `minimumorder` double NOT NULL,
  `minimumordersurcharge` double NOT NULL,
  PRIMARY KEY (`id`),
  KEY `groupkey` (`groupkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_customergroups` (`id`, `groupkey`, `description`, `tax`, `taxinput`, `mode`, `discount`, `minimumorder`, `minimumordersurcharge`) VALUES
(1,	'EK',	'Shopkunden',	1,	1,	0,	0,	0,	0),
(2,	'H',	'Händler',	1,	0,	0,	0,	0,	0);

DROP TABLE IF EXISTS `s_core_customergroups_attributes`;
CREATE TABLE `s_core_customergroups_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `customerGroupID` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customerGroupID` (`customerGroupID`),
  CONSTRAINT `s_core_customergroups_attributes_ibfk_1` FOREIGN KEY (`customerGroupID`) REFERENCES `s_core_customergroups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_core_customergroups_discounts`;
CREATE TABLE `s_core_customergroups_discounts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `groupID` int NOT NULL,
  `basketdiscount` double NOT NULL,
  `basketdiscountstart` double NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `groupID` (`groupID`,`basketdiscountstart`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_core_customerpricegroups`;
CREATE TABLE `s_core_customerpricegroups` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `netto` int unsigned NOT NULL,
  `active` int unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_core_detail_states`;
CREATE TABLE `s_core_detail_states` (
  `id` int NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `position` int NOT NULL,
  `mail` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_detail_states` (`id`, `description`, `position`, `mail`) VALUES
(0,	'Offen',	1,	0),
(1,	'In Bearbeitung',	2,	0),
(2,	'Storniert',	3,	0),
(3,	'Abgeschlossen',	4,	0);

DROP TABLE IF EXISTS `s_core_documents`;
CREATE TABLE `s_core_documents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `template` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `numbers` varchar(25) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `left` int NOT NULL,
  `right` int NOT NULL,
  `top` int NOT NULL,
  `bottom` int NOT NULL,
  `pagebreak` int NOT NULL,
  `key` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_documents` (`id`, `name`, `template`, `numbers`, `left`, `right`, `top`, `bottom`, `pagebreak`, `key`) VALUES
(1,	'Rechnung',	'index.tpl',	'doc_0',	25,	10,	20,	20,	10,	'invoice'),
(2,	'Lieferschein',	'index_ls.tpl',	'doc_1',	25,	10,	20,	20,	10,	'delivery_note'),
(3,	'Gutschrift',	'index_gs.tpl',	'doc_2',	25,	10,	20,	20,	10,	'credit'),
(4,	'Stornorechnung',	'index_sr.tpl',	'doc_3',	25,	10,	20,	20,	10,	'cancellation');

DROP TABLE IF EXISTS `s_core_documents_box`;
CREATE TABLE `s_core_documents_box` (
  `id` int NOT NULL AUTO_INCREMENT,
  `documentID` int NOT NULL,
  `name` varchar(35) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `style` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `value` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_documents_box` (`id`, `documentID`, `name`, `style`, `value`) VALUES
(1,	1,	'Body',	'width:100%;\r\nfont-family: Verdana, Arial, Helvetica, sans-serif;\r\nfont-size:11px;',	''),
(2,	1,	'Logo',	'height: 20mm;\r\nwidth: 90mm;\r\nmargin-bottom:5mm;',	'<p><img src=\"http://assets.shopware.com/demoshop-logo/logo--tablet.png\" alt=\"Demoshop\" /></p>'),
(3,	1,	'Header_Recipient',	'',	''),
(4,	1,	'Header',	'height: 60mm;',	''),
(5,	1,	'Header_Sender',	'',	'<p>Demo GmbH - Stra&szlig;e 3 - 00000 Musterstadt</p>'),
(6,	1,	'Header_Box_Left',	'width: 120mm;\r\nheight:60mm;\r\nfloat:left;',	''),
(7,	1,	'Header_Box_Right',	'width: 45mm;\r\nheight: 60mm;\r\nfloat:left;\r\nmargin-top:-20px;\r\nmargin-left:5px;',	'<p><strong>Demo GmbH </strong><br /> Max Mustermann<br /> Stra&szlig;e 3<br /> 00000 Musterstadt<br /> Fon: 01234 / 56789<br /> Fax: 01234 / 			56780<br />info@demo.de<br />www.demo.de</p>'),
(8,	1,	'Header_Box_Bottom',	'font-size:14px;\r\nheight: 10mm;',	''),
(9,	1,	'Content',	'height: 65mm;\r\nwidth: 170mm;',	''),
(10,	1,	'Td',	'white-space:nowrap;\r\npadding: 5px 0;',	''),
(11,	1,	'Td_Name',	'white-space:normal;',	''),
(12,	1,	'Td_Line',	'border-bottom: 1px solid #999;\r\nheight: 0px;',	''),
(13,	1,	'Td_Head',	'border-bottom:1px solid #000;',	''),
(14,	1,	'Footer',	'width: 170mm;\r\nposition:fixed;\r\nbottom:-20mm;\r\nheight: 15mm;',	'<table style=\"vertical-align: top;\" width=\"100%\" border=\"0\">\r\n<tbody>\r\n<tr valign=\"top\">\r\n<td style=\"width: 25%;\">\r\n<p><span style=\"font-size: xx-small;\">Demo GmbH</span></p>\r\n<p><span style=\"font-size: xx-small;\">Steuer-Nr <br />UST-ID: <br />Finanzamt </span><span style=\"font-size: xx-small;\">Musterstadt</span></p>\r\n</td>\r\n<td style=\"width: 25%;\">\r\n<p><span style=\"font-size: xx-small;\">Bankverbindung</span></p>\r\n<p><span style=\"font-size: xx-small;\">Sparkasse Musterstadt<br />BLZ: <br />Konto: </span></p>\r\n<span style=\"font-size: xx-small;\">aaaa<br /></span></td>\r\n<td style=\"width: 25%;\">\r\n<p><span style=\"font-size: xx-small;\">AGB<br /></span></p>\r\n<p><span style=\"font-size: xx-small;\">Gerichtsstand ist Musterstadt<br />Erf&uuml;llungsort Musterstadt<br />Gelieferte Ware bleibt bis zur vollst&auml;ndigen Bezahlung unser Eigentum</span></p>\r\n</td>\r\n<td style=\"width: 25%;\">\r\n<p><span style=\"font-size: xx-small;\">Gesch&auml;ftsf&uuml;hrer</span></p>\r\n<p><span style=\"font-size: xx-small;\">Max Mustermann</span></p>\r\n</td>\r\n</tr>\r\n</tbody>\r\n</table>'),
(15,	1,	'Content_Amount',	'margin-left:90mm;',	''),
(16,	1,	'Content_Info',	'',	'<p>Die Ware bleibt bis zur vollst&auml;ndigen Bezahlung unser Eigentum</p>'),
(68,	2,	'Body',	'width:100%;\r\nfont-family: Verdana, Arial, Helvetica, sans-serif;\r\nfont-size:11px;',	''),
(69,	2,	'Logo',	'height: 20mm;\r\nwidth: 90mm;\r\nmargin-bottom:5mm;',	'<p><img src=\"http://assets.shopware.com/demoshop-logo/logo--tablet.png\" alt=\"Demoshop\" /></p>'),
(70,	2,	'Header_Recipient',	'',	''),
(71,	2,	'Header',	'height: 60mm;',	''),
(72,	2,	'Header_Sender',	'',	'<p>Demo GmbH - Stra&szlig;e 3 - 00000 Musterstadt</p>'),
(73,	2,	'Header_Box_Left',	'width: 120mm;\r\nheight:60mm;\r\nfloat:left;',	''),
(74,	2,	'Header_Box_Right',	'width: 45mm;\r\nheight: 60mm;\r\nfloat:left;\r\nmargin-top:-20px;\r\nmargin-left:5px;',	'<p><strong>Demo GmbH </strong><br /> Max Mustermann<br /> Stra&szlig;e 3<br /> 00000 Musterstadt<br /> Fon: 01234 / 56789<br /> Fax: 01234 / 			56780<br />info@demo.de<br />www.demo.de</p>'),
(75,	2,	'Header_Box_Bottom',	'font-size:14px;\r\nheight: 10mm;',	''),
(76,	2,	'Content',	'height: 65mm;\r\nwidth: 170mm;',	''),
(77,	2,	'Td',	'white-space:nowrap;\r\npadding: 5px 0;',	''),
(78,	2,	'Td_Name',	'white-space:normal;',	''),
(79,	2,	'Td_Line',	'border-bottom: 1px solid #999;\r\nheight: 0px;',	''),
(80,	2,	'Td_Head',	'border-bottom:1px solid #000;',	''),
(81,	2,	'Footer',	'width: 170mm;\r\nposition:fixed;\r\nbottom:-20mm;\r\nheight: 15mm;',	'<table style=\"vertical-align: top;\" width=\"100%\" border=\"0\">\r\n<tbody>\r\n<tr valign=\"top\">\r\n<td style=\"width: 25%;\">\r\n<p><span style=\"font-size: xx-small;\">Demo GmbH</span></p>\r\n<p><span style=\"font-size: xx-small;\">Steuer-Nr <br />UST-ID: <br />Finanzamt </span><span style=\"font-size: xx-small;\">Musterstadt</span></p>\r\n</td>\r\n<td style=\"width: 25%;\">\r\n<p><span style=\"font-size: xx-small;\">Bankverbindung</span></p>\r\n<p><span style=\"font-size: xx-small;\">Sparkasse Musterstadt<br />BLZ: <br />Konto: </span></p>\r\n<span style=\"font-size: xx-small;\">aaaa<br /></span></td>\r\n<td style=\"width: 25%;\">\r\n<p><span style=\"font-size: xx-small;\">AGB<br /></span></p>\r\n<p><span style=\"font-size: xx-small;\">Gerichtsstand ist Musterstadt<br />Erf&uuml;llungsort Musterstadt<br />Gelieferte Ware bleibt bis zur vollst&auml;ndigen Bezahlung unser Eigentum</span></p>\r\n</td>\r\n<td style=\"width: 25%;\">\r\n<p><span style=\"font-size: xx-small;\">Gesch&auml;ftsf&uuml;hrer</span></p>\r\n<p><span style=\"font-size: xx-small;\">Max Mustermann</span></p>\r\n</td>\r\n</tr>\r\n</tbody>\r\n</table>'),
(82,	2,	'Content_Amount',	'margin-left:90mm;',	''),
(83,	2,	'Content_Info',	'',	'<p>Die Ware bleibt bis zur vollst&auml;ndigen Bezahlung unser Eigentum</p>'),
(84,	3,	'Body',	'width:100%;\r\nfont-family: Verdana, Arial, Helvetica, sans-serif;\r\nfont-size:11px;',	''),
(85,	3,	'Logo',	'height: 20mm;\r\nwidth: 90mm;\r\nmargin-bottom:5mm;',	'<p><img src=\"http://assets.shopware.com/demoshop-logo/logo--tablet.png\" alt=\"Demoshop\" /></p>'),
(86,	3,	'Header_Recipient',	'',	''),
(87,	3,	'Header',	'height: 60mm;',	''),
(88,	3,	'Header_Sender',	'',	'<p>Demo GmbH - Stra&szlig;e 3 - 00000 Musterstadt</p>'),
(89,	3,	'Header_Box_Left',	'width: 120mm;\r\nheight:60mm;\r\nfloat:left;',	''),
(90,	3,	'Header_Box_Right',	'width: 45mm;\r\nheight: 60mm;\r\nfloat:left;\r\nmargin-top:-20px;\r\nmargin-left:5px;',	'<p><strong>Demo GmbH </strong><br /> Max Mustermann<br /> Stra&szlig;e 3<br /> 00000 Musterstadt<br /> Fon: 01234 / 56789<br /> Fax: 01234 / 			56780<br />info@demo.de<br />www.demo.de</p>'),
(91,	3,	'Header_Box_Bottom',	'font-size:14px;\r\nheight: 10mm;',	''),
(92,	3,	'Content',	'height: 65mm;\r\nwidth: 170mm;',	''),
(93,	3,	'Td',	'white-space:nowrap;\r\npadding: 5px 0;',	''),
(94,	3,	'Td_Name',	'white-space:normal;',	''),
(95,	3,	'Td_Line',	'border-bottom: 1px solid #999;\r\nheight: 0px;',	''),
(96,	3,	'Td_Head',	'border-bottom:1px solid #000;',	''),
(97,	3,	'Footer',	'width: 170mm;\r\nposition:fixed;\r\nbottom:-20mm;\r\nheight: 15mm;',	'<table style=\"vertical-align: top;\" width=\"100%\" border=\"0\">\r\n<tbody>\r\n<tr valign=\"top\">\r\n<td style=\"width: 25%;\">\r\n<p><span style=\"font-size: xx-small;\">Demo GmbH</span></p>\r\n<p><span style=\"font-size: xx-small;\">Steuer-Nr <br />UST-ID: <br />Finanzamt </span><span style=\"font-size: xx-small;\">Musterstadt</span></p>\r\n</td>\r\n<td style=\"width: 25%;\">\r\n<p><span style=\"font-size: xx-small;\">Bankverbindung</span></p>\r\n<p><span style=\"font-size: xx-small;\">Sparkasse Musterstadt<br />BLZ: <br />Konto: </span></p>\r\n<span style=\"font-size: xx-small;\">aaaa<br /></span></td>\r\n<td style=\"width: 25%;\">\r\n<p><span style=\"font-size: xx-small;\">AGB<br /></span></p>\r\n<p><span style=\"font-size: xx-small;\">Gerichtsstand ist Musterstadt<br />Erf&uuml;llungsort Musterstadt<br />Gelieferte Ware bleibt bis zur vollst&auml;ndigen Bezahlung unser Eigentum</span></p>\r\n</td>\r\n<td style=\"width: 25%;\">\r\n<p><span style=\"font-size: xx-small;\">Gesch&auml;ftsf&uuml;hrer</span></p>\r\n<p><span style=\"font-size: xx-small;\">Max Mustermann</span></p>\r\n</td>\r\n</tr>\r\n</tbody>\r\n</table>'),
(98,	3,	'Content_Amount',	'margin-left:90mm;',	''),
(99,	3,	'Content_Info',	'',	'<p>Die Ware bleibt bis zur vollst&auml;ndigen Bezahlung unser Eigentum</p>'),
(100,	4,	'Body',	'width:100%;\r\nfont-family: Verdana, Arial, Helvetica, sans-serif;\r\nfont-size:11px;',	''),
(101,	4,	'Logo',	'height: 20mm;\r\nwidth: 90mm;\r\nmargin-bottom:5mm;',	'<p><img src=\"http://assets.shopware.com/demoshop-logo/logo--tablet.png\" alt=\"Demoshop\" /></p>'),
(102,	4,	'Header_Recipient',	'',	''),
(103,	4,	'Header',	'height: 60mm;',	''),
(104,	4,	'Header_Sender',	'',	'<p>Demo GmbH - Stra&szlig;e 3 - 00000 Musterstadt</p>'),
(105,	4,	'Header_Box_Left',	'width: 120mm;\r\nheight:60mm;\r\nfloat:left;',	''),
(106,	4,	'Header_Box_Right',	'width: 45mm;\r\nheight: 60mm;\r\nfloat:left;\r\nmargin-top:-20px;\r\nmargin-left:5px;',	'<p><strong>Demo GmbH </strong><br /> Max Mustermann<br /> Stra&szlig;e 3<br /> 00000 Musterstadt<br /> Fon: 01234 / 56789<br /> Fax: 01234 / 			56780<br />info@demo.de<br />www.demo.de</p>'),
(107,	4,	'Header_Box_Bottom',	'font-size:14px;\r\nheight: 10mm;',	''),
(108,	4,	'Content',	'height: 65mm;\r\nwidth: 170mm;',	''),
(109,	4,	'Td',	'white-space:nowrap;\r\npadding: 5px 0;',	''),
(110,	4,	'Td_Name',	'white-space:normal;',	''),
(111,	4,	'Td_Line',	'border-bottom: 1px solid #999;\r\nheight: 0px;',	''),
(112,	4,	'Td_Head',	'border-bottom:1px solid #000;',	''),
(113,	4,	'Footer',	'width: 170mm;\r\nposition:fixed;\r\nbottom:-20mm;\r\nheight: 15mm;',	'<table style=\"vertical-align: top;\" width=\"100%\" border=\"0\">\r\n<tbody>\r\n<tr valign=\"top\">\r\n<td style=\"width: 25%;\">\r\n<p><span style=\"font-size: xx-small;\">Demo GmbH</span></p>\r\n<p><span style=\"font-size: xx-small;\">Steuer-Nr <br />UST-ID: <br />Finanzamt </span><span style=\"font-size: xx-small;\">Musterstadt</span></p>\r\n</td>\r\n<td style=\"width: 25%;\">\r\n<p><span style=\"font-size: xx-small;\">Bankverbindung</span></p>\r\n<p><span style=\"font-size: xx-small;\">Sparkasse Musterstadt<br />BLZ: <br />Konto: </span></p>\r\n<span style=\"font-size: xx-small;\">aaaa<br /></span></td>\r\n<td style=\"width: 25%;\">\r\n<p><span style=\"font-size: xx-small;\">AGB<br /></span></p>\r\n<p><span style=\"font-size: xx-small;\">Gerichtsstand ist Musterstadt<br />Erf&uuml;llungsort Musterstadt<br />Gelieferte Ware bleibt bis zur vollst&auml;ndigen Bezahlung unser Eigentum</span></p>\r\n</td>\r\n<td style=\"width: 25%;\">\r\n<p><span style=\"font-size: xx-small;\">Gesch&auml;ftsf&uuml;hrer</span></p>\r\n<p><span style=\"font-size: xx-small;\">Max Mustermann</span></p>\r\n</td>\r\n</tr>\r\n</tbody>\r\n</table>'),
(114,	4,	'Content_Amount',	'margin-left:90mm;',	''),
(115,	4,	'Content_Info',	'',	'<p>Die Ware bleibt bis zur vollst&auml;ndigen Bezahlung unser Eigentum</p>');

DROP TABLE IF EXISTS `s_core_engine_elements`;
CREATE TABLE `s_core_engine_elements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `groupID` int unsigned NOT NULL DEFAULT '0',
  `domname` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `default` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `type` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `store` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `label` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `required` int NOT NULL DEFAULT '0',
  `position` int NOT NULL DEFAULT '0',
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `layout` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `variantable` int unsigned NOT NULL,
  `help` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `translatable` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `databasefield` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_engine_elements` (`id`, `groupID`, `domname`, `default`, `type`, `store`, `label`, `required`, `position`, `name`, `layout`, `variantable`, `help`, `translatable`) VALUES
(22,	7,	'attr[3]',	'',	'textarea',	NULL,	'Kommentar',	0,	3,	'attr3',	'',	0,	'Optionaler Kommentar',	1),
(33,	7,	'attr[1]',	'',	'text',	NULL,	'Freitext-1',	0,	1,	'attr1',	'w200',	1,	'Freitext zur Anzeige auf der Detailseite',	1),
(34,	7,	'attr[2]',	'',	'text',	NULL,	'Freitext-2',	0,	2,	'attr2',	'w200',	1,	'Freitext zur Anzeige auf der Detailseite',	1);

DROP TABLE IF EXISTS `s_core_engine_groups`;
CREATE TABLE `s_core_engine_groups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `label` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `layout` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `variantable` int unsigned NOT NULL DEFAULT '0',
  `position` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_engine_groups` (`id`, `name`, `label`, `layout`, `variantable`, `position`) VALUES
(1,	'basic',	'Stammdaten',	'column',	1,	1),
(2,	'description',	'Beschreibung',	NULL,	0,	2),
(3,	'advanced',	'Einstellungen',	'column',	1,	5),
(7,	'additional',	'Zusatzfelder',	NULL,	1,	7),
(8,	'reference_price',	'Grundpreisberechnung',	NULL,	0,	4),
(10,	'price',	'Preise und Kundengruppen',	NULL,	0,	3),
(11,	'property',	'Eigenschaften',	NULL,	0,	6);

DROP TABLE IF EXISTS `s_core_licenses`;
CREATE TABLE `s_core_licenses` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `module` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `host` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `label` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `license` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `version` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `notation` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `type` int unsigned NOT NULL,
  `source` int unsigned NOT NULL,
  `added` date NOT NULL,
  `creation` date DEFAULT NULL,
  `expiration` date DEFAULT NULL,
  `active` int NOT NULL,
  `plugin_id` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_core_locales`;
CREATE TABLE `s_core_locales` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `locale` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `language` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `territory` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `locale` (`locale`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_locales` (`id`, `locale`, `language`, `territory`) VALUES
(1,	'de_DE',	'Deutsch',	'Deutschland'),
(2,	'en_GB',	'Englisch',	'Vereinigtes Königreich'),
(3,	'aa_DJ',	'Afar',	'Dschibuti'),
(4,	'aa_ER',	'Afar',	'Eritrea'),
(5,	'aa_ET',	'Afar',	'Äthiopien'),
(6,	'af_NA',	'Afrikaans',	'Namibia'),
(7,	'af_ZA',	'Afrikaans',	'Südafrika'),
(8,	'ak_GH',	'Akan',	'Ghana'),
(9,	'am_ET',	'Amharisch',	'Äthiopien'),
(10,	'ar_AE',	'Arabisch',	'Vereinigte Arabische Emirate'),
(11,	'ar_BH',	'Arabisch',	'Bahrain'),
(12,	'ar_DZ',	'Arabisch',	'Algerien'),
(13,	'ar_EG',	'Arabisch',	'Ägypten'),
(14,	'ar_IQ',	'Arabisch',	'Irak'),
(15,	'ar_JO',	'Arabisch',	'Jordanien'),
(16,	'ar_KW',	'Arabisch',	'Kuwait'),
(17,	'ar_LB',	'Arabisch',	'Libanon'),
(18,	'ar_LY',	'Arabisch',	'Libyen'),
(19,	'ar_MA',	'Arabisch',	'Marokko'),
(20,	'ar_OM',	'Arabisch',	'Oman'),
(21,	'ar_QA',	'Arabisch',	'Katar'),
(22,	'ar_SA',	'Arabisch',	'Saudi-Arabien'),
(23,	'ar_SD',	'Arabisch',	'Sudan'),
(24,	'ar_SY',	'Arabisch',	'Syrien'),
(25,	'ar_TN',	'Arabisch',	'Tunesien'),
(26,	'ar_YE',	'Arabisch',	'Jemen'),
(27,	'as_IN',	'Assamesisch',	'Indien'),
(28,	'az_AZ',	'Aserbaidschanisch',	'Aserbaidschan'),
(29,	'be_BY',	'Weißrussisch',	'Belarus'),
(30,	'bg_BG',	'Bulgarisch',	'Bulgarien'),
(31,	'bn_BD',	'Bengalisch',	'Bangladesch'),
(32,	'bn_IN',	'Bengalisch',	'Indien'),
(33,	'bo_CN',	'Tibetisch',	'China'),
(34,	'bo_IN',	'Tibetisch',	'Indien'),
(35,	'bs_BA',	'Bosnisch',	'Bosnien und Herzegowina'),
(36,	'byn_ER',	'Blin',	'Eritrea'),
(37,	'ca_ES',	'Katalanisch',	'Spanien'),
(38,	'cch_NG',	'Atsam',	'Nigeria'),
(39,	'cs_CZ',	'Tschechisch',	'Tschechische Republik'),
(40,	'cy_GB',	'Walisisch',	'Vereinigtes Königreich'),
(41,	'da_DK',	'Dänisch',	'Dänemark'),
(42,	'de_AT',	'Deutsch',	'Österreich'),
(43,	'de_BE',	'Deutsch',	'Belgien'),
(44,	'de_CH',	'Deutsch',	'Schweiz'),
(45,	'de_LI',	'Deutsch',	'Liechtenstein'),
(46,	'de_LU',	'Deutsch',	'Luxemburg'),
(47,	'dv_MV',	'Maledivisch',	'Malediven'),
(48,	'dz_BT',	'Bhutanisch',	'Bhutan'),
(49,	'ee_GH',	'Ewe-Sprache',	'Ghana'),
(50,	'ee_TG',	'Ewe-Sprache',	'Togo'),
(51,	'el_CY',	'Griechisch',	'Zypern'),
(52,	'el_GR',	'Griechisch',	'Griechenland'),
(53,	'en_AS',	'Englisch',	'Amerikanisch-Samoa'),
(54,	'en_AU',	'Englisch',	'Australien'),
(55,	'en_BE',	'Englisch',	'Belgien'),
(56,	'en_BW',	'Englisch',	'Botsuana'),
(57,	'en_BZ',	'Englisch',	'Belize'),
(58,	'en_CA',	'Englisch',	'Kanada'),
(59,	'en_GU',	'Englisch',	'Guam'),
(60,	'en_HK',	'Englisch',	'Sonderverwaltungszone Hongkong'),
(61,	'en_IE',	'Englisch',	'Irland'),
(62,	'en_IN',	'Englisch',	'Indien'),
(63,	'en_JM',	'Englisch',	'Jamaika'),
(64,	'en_MH',	'Englisch',	'Marshallinseln'),
(65,	'en_MP',	'Englisch',	'Nördliche Marianen'),
(66,	'en_MT',	'Englisch',	'Malta'),
(67,	'en_NA',	'Englisch',	'Namibia'),
(68,	'en_NZ',	'Englisch',	'Neuseeland'),
(69,	'en_PH',	'Englisch',	'Philippinen'),
(70,	'en_PK',	'Englisch',	'Pakistan'),
(71,	'en_SG',	'Englisch',	'Singapur'),
(72,	'en_TT',	'Englisch',	'Trinidad und Tobago'),
(73,	'en_UM',	'Englisch',	'Amerikanisch-Ozeanien'),
(74,	'en_US',	'Englisch',	'Vereinigte Staaten'),
(75,	'en_VI',	'Englisch',	'Amerikanische Jungferninseln'),
(76,	'en_ZA',	'Englisch',	'Südafrika'),
(77,	'en_ZW',	'Englisch',	'Simbabwe'),
(78,	'es_AR',	'Spanisch',	'Argentinien'),
(79,	'es_BO',	'Spanisch',	'Bolivien'),
(80,	'es_CL',	'Spanisch',	'Chile'),
(81,	'es_CO',	'Spanisch',	'Kolumbien'),
(82,	'es_CR',	'Spanisch',	'Costa Rica'),
(83,	'es_DO',	'Spanisch',	'Dominikanische Republik'),
(84,	'es_EC',	'Spanisch',	'Ecuador'),
(85,	'es_ES',	'Spanisch',	'Spanien'),
(86,	'es_GT',	'Spanisch',	'Guatemala'),
(87,	'es_HN',	'Spanisch',	'Honduras'),
(88,	'es_MX',	'Spanisch',	'Mexiko'),
(89,	'es_NI',	'Spanisch',	'Nicaragua'),
(90,	'es_PA',	'Spanisch',	'Panama'),
(91,	'es_PE',	'Spanisch',	'Peru'),
(92,	'es_PR',	'Spanisch',	'Puerto Rico'),
(93,	'es_PY',	'Spanisch',	'Paraguay'),
(94,	'es_SV',	'Spanisch',	'El Salvador'),
(95,	'es_US',	'Spanisch',	'Vereinigte Staaten'),
(96,	'es_UY',	'Spanisch',	'Uruguay'),
(97,	'es_VE',	'Spanisch',	'Venezuela'),
(98,	'et_EE',	'Estnisch',	'Estland'),
(99,	'eu_ES',	'Baskisch',	'Spanien'),
(100,	'fa_AF',	'Persisch',	'Afghanistan'),
(101,	'fa_IR',	'Persisch',	'Iran'),
(102,	'fi_FI',	'Finnisch',	'Finnland'),
(103,	'fil_PH',	'Filipino',	'Philippinen'),
(104,	'fo_FO',	'Färöisch',	'Färöer'),
(105,	'fr_BE',	'Französisch',	'Belgien'),
(106,	'fr_CA',	'Französisch',	'Kanada'),
(107,	'fr_CH',	'Französisch',	'Schweiz'),
(108,	'fr_FR',	'Französisch',	'Frankreich'),
(109,	'fr_LU',	'Französisch',	'Luxemburg'),
(110,	'fr_MC',	'Französisch',	'Monaco'),
(111,	'fr_SN',	'Französisch',	'Senegal'),
(112,	'fur_IT',	'Friulisch',	'Italien'),
(113,	'ga_IE',	'Irisch',	'Irland'),
(114,	'gaa_GH',	'Ga-Sprache',	'Ghana'),
(115,	'gez_ER',	'Geez',	'Eritrea'),
(116,	'gez_ET',	'Geez',	'Äthiopien'),
(117,	'gl_ES',	'Galizisch',	'Spanien'),
(118,	'gsw_CH',	'Schweizerdeutsch',	'Schweiz'),
(119,	'gu_IN',	'Gujarati',	'Indien'),
(120,	'gv_GB',	'Manx',	'Vereinigtes Königreich'),
(121,	'ha_GH',	'Hausa',	'Ghana'),
(122,	'ha_NE',	'Hausa',	'Niger'),
(123,	'ha_NG',	'Hausa',	'Nigeria'),
(124,	'ha_SD',	'Hausa',	'Sudan'),
(125,	'haw_US',	'Hawaiisch',	'Vereinigte Staaten'),
(126,	'he_IL',	'Hebräisch',	'Israel'),
(127,	'hi_IN',	'Hindi',	'Indien'),
(128,	'hr_HR',	'Kroatisch',	'Kroatien'),
(129,	'hu_HU',	'Ungarisch',	'Ungarn'),
(130,	'hy_AM',	'Armenisch',	'Armenien'),
(131,	'id_ID',	'Indonesisch',	'Indonesien'),
(132,	'ig_NG',	'Igbo-Sprache',	'Nigeria'),
(133,	'ii_CN',	'Sichuan Yi',	'China'),
(134,	'is_IS',	'Isländisch',	'Island'),
(135,	'it_CH',	'Italienisch',	'Schweiz'),
(136,	'it_IT',	'Italienisch',	'Italien'),
(137,	'ja_JP',	'Japanisch',	'Japan'),
(138,	'ka_GE',	'Georgisch',	'Georgien'),
(139,	'kaj_NG',	'Jju',	'Nigeria'),
(140,	'kam_KE',	'Kamba',	'Kenia'),
(141,	'kcg_NG',	'Tyap',	'Nigeria'),
(142,	'kfo_CI',	'Koro',	'Côte d?Ivoire'),
(143,	'kk_KZ',	'Kasachisch',	'Kasachstan'),
(144,	'kl_GL',	'Grönländisch',	'Grönland'),
(145,	'km_KH',	'Kambodschanisch',	'Kambodscha'),
(146,	'kn_IN',	'Kannada',	'Indien'),
(147,	'ko_KR',	'Koreanisch',	'Republik Korea'),
(148,	'kok_IN',	'Konkani',	'Indien'),
(149,	'kpe_GN',	'Kpelle-Sprache',	'Guinea'),
(150,	'kpe_LR',	'Kpelle-Sprache',	'Liberia'),
(151,	'ku_IQ',	'Kurdisch',	'Irak'),
(152,	'ku_IR',	'Kurdisch',	'Iran'),
(153,	'ku_SY',	'Kurdisch',	'Syrien'),
(154,	'ku_TR',	'Kurdisch',	'Türkei'),
(155,	'kw_GB',	'Kornisch',	'Vereinigtes Königreich'),
(156,	'ky_KG',	'Kirgisisch',	'Kirgisistan'),
(157,	'ln_CD',	'Lingala',	'Demokratische Republik Kongo'),
(158,	'ln_CG',	'Lingala',	'Kongo'),
(159,	'lo_LA',	'Laotisch',	'Laos'),
(160,	'lt_LT',	'Litauisch',	'Litauen'),
(161,	'lv_LV',	'Lettisch',	'Lettland'),
(162,	'mk_MK',	'Mazedonisch',	'Mazedonien'),
(163,	'ml_IN',	'Malayalam',	'Indien'),
(164,	'mn_CN',	'Mongolisch',	'China'),
(165,	'mn_MN',	'Mongolisch',	'Mongolei'),
(166,	'mr_IN',	'Marathi',	'Indien'),
(167,	'ms_BN',	'Malaiisch',	'Brunei Darussalam'),
(168,	'ms_MY',	'Malaiisch',	'Malaysia'),
(169,	'mt_MT',	'Maltesisch',	'Malta'),
(170,	'my_MM',	'Birmanisch',	'Myanmar'),
(171,	'nb_NO',	'Norwegisch Bokmål',	'Norwegen'),
(172,	'nds_DE',	'Niederdeutsch',	'Deutschland'),
(173,	'ne_IN',	'Nepalesisch',	'Indien'),
(174,	'ne_NP',	'Nepalesisch',	'Nepal'),
(175,	'nl_BE',	'Niederländisch',	'Belgien'),
(176,	'nl_NL',	'Niederländisch',	'Niederlande'),
(177,	'nn_NO',	'Norwegisch Nynorsk',	'Norwegen'),
(178,	'nr_ZA',	'Süd-Ndebele-Sprache',	'Südafrika'),
(179,	'nso_ZA',	'Nord-Sotho-Sprache',	'Südafrika'),
(180,	'ny_MW',	'Nyanja-Sprache',	'Malawi'),
(181,	'oc_FR',	'Okzitanisch',	'Frankreich'),
(182,	'om_ET',	'Oromo',	'Äthiopien'),
(183,	'om_KE',	'Oromo',	'Kenia'),
(184,	'or_IN',	'Orija',	'Indien'),
(185,	'pa_IN',	'Pandschabisch',	'Indien'),
(186,	'pa_PK',	'Pandschabisch',	'Pakistan'),
(187,	'pl_PL',	'Polnisch',	'Polen'),
(188,	'ps_AF',	'Paschtu',	'Afghanistan'),
(189,	'pt_BR',	'Portugiesisch',	'Brasilien'),
(190,	'pt_PT',	'Portugiesisch',	'Portugal'),
(191,	'ro_MD',	'Rumänisch',	'Republik Moldau'),
(192,	'ro_RO',	'Rumänisch',	'Rumänien'),
(193,	'ru_RU',	'Russisch',	'Russische Föderation'),
(194,	'ru_UA',	'Russisch',	'Ukraine'),
(195,	'rw_RW',	'Ruandisch',	'Ruanda'),
(196,	'sa_IN',	'Sanskrit',	'Indien'),
(197,	'se_FI',	'Nord-Samisch',	'Finnland'),
(198,	'se_NO',	'Nord-Samisch',	'Norwegen'),
(199,	'sh_BA',	'Serbo-Kroatisch',	'Bosnien und Herzegowina'),
(200,	'sh_CS',	'Serbo-Kroatisch',	'Serbien und Montenegro'),
(201,	'sh_YU',	'Serbo-Kroatisch',	''),
(202,	'si_LK',	'Singhalesisch',	'Sri Lanka'),
(203,	'sid_ET',	'Sidamo',	'Äthiopien'),
(204,	'sk_SK',	'Slowakisch',	'Slowakei'),
(205,	'sl_SI',	'Slowenisch',	'Slowenien'),
(206,	'so_DJ',	'Somali',	'Dschibuti'),
(207,	'so_ET',	'Somali',	'Äthiopien'),
(208,	'so_KE',	'Somali',	'Kenia'),
(209,	'so_SO',	'Somali',	'Somalia'),
(210,	'sq_AL',	'Albanisch',	'Albanien'),
(211,	'sr_BA',	'Serbisch',	'Bosnien und Herzegowina'),
(212,	'sr_CS',	'Serbisch',	'Serbien und Montenegro'),
(213,	'sr_ME',	'Serbisch',	'Montenegro'),
(214,	'sr_RS',	'Serbisch',	'Serbien'),
(215,	'sr_YU',	'Serbisch',	''),
(216,	'ss_SZ',	'Swazi',	'Swasiland'),
(217,	'ss_ZA',	'Swazi',	'Südafrika'),
(218,	'st_LS',	'Süd-Sotho-Sprache',	'Lesotho'),
(219,	'st_ZA',	'Süd-Sotho-Sprache',	'Südafrika'),
(220,	'sv_FI',	'Schwedisch',	'Finnland'),
(221,	'sv_SE',	'Schwedisch',	'Schweden'),
(222,	'sw_KE',	'Suaheli',	'Kenia'),
(223,	'sw_TZ',	'Suaheli',	'Tansania'),
(224,	'syr_SY',	'Syrisch',	'Syrien'),
(225,	'ta_IN',	'Tamilisch',	'Indien'),
(226,	'te_IN',	'Telugu',	'Indien'),
(227,	'tg_TJ',	'Tadschikisch',	'Tadschikistan'),
(228,	'th_TH',	'Thailändisch',	'Thailand'),
(229,	'ti_ER',	'Tigrinja',	'Eritrea'),
(230,	'ti_ET',	'Tigrinja',	'Äthiopien'),
(231,	'tig_ER',	'Tigre',	'Eritrea'),
(232,	'tn_ZA',	'Tswana-Sprache',	'Südafrika'),
(233,	'to_TO',	'Tongaisch',	'Tonga'),
(234,	'tr_TR',	'Türkisch',	'Türkei'),
(236,	'ts_ZA',	'Tsonga',	'Südafrika'),
(237,	'tt_RU',	'Tatarisch',	'Russische Föderation'),
(238,	'ug_CN',	'Uigurisch',	'China'),
(239,	'uk_UA',	'Ukrainisch',	'Ukraine'),
(240,	'ur_IN',	'Urdu',	'Indien'),
(241,	'ur_PK',	'Urdu',	'Pakistan'),
(242,	'uz_AF',	'Usbekisch',	'Afghanistan'),
(243,	'uz_UZ',	'Usbekisch',	'Usbekistan'),
(244,	've_ZA',	'Venda-Sprache',	'Südafrika'),
(245,	'vi_VN',	'Vietnamesisch',	'Vietnam'),
(246,	'wal_ET',	'Walamo-Sprache',	'Äthiopien'),
(247,	'wo_SN',	'Wolof',	'Senegal'),
(248,	'xh_ZA',	'Xhosa',	'Südafrika'),
(249,	'yo_NG',	'Yoruba',	'Nigeria'),
(250,	'zh_CN',	'Chinesisch',	'China'),
(251,	'zh_HK',	'Chinesisch',	'Sonderverwaltungszone Hongkong'),
(252,	'zh_MO',	'Chinesisch',	'Sonderverwaltungszone Macao'),
(253,	'zh_SG',	'Chinesisch',	'Singapur'),
(254,	'zh_TW',	'Chinesisch',	'Taiwan'),
(255,	'zu_ZA',	'Zulu',	'Südafrika');

DROP TABLE IF EXISTS `s_core_log`;
CREATE TABLE `s_core_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `key` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `text` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `date` datetime NOT NULL,
  `user` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `ip_address` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `user_agent` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `value4` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_log` (`id`, `type`, `key`, `text`, `date`, `user`, `ip_address`, `user_agent`, `value4`) VALUES
(1,	'backend',	'Versandkosten Verwaltung',	'Einstellungen wurden erfolgreich gespeichert.',	'2012-08-28 10:38:26',	'Administrator',	'217.86.247.178',	'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:14.0) Gecko/20100101 Firefox/14.0.1 FirePHP/0.7.1',	''),
(2,	'backend',	'',	'Unable to install \'SwagDemoDataDE\', got message:\n\'Durch die Installation gehen alle Daten verloren. Bitte zur bestätigung die Installation noch einmal ausführen.\'\n',	'2026-02-02 10:22:15',	'Shopware Demo',	'212.185.0.0',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36',	''),
(3,	'backend',	'First Run Wizard',	'Einstellungen gespeichert',	'2026-02-02 10:22:35',	'Shopware Demo',	'212.185.0.0',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36',	''),
(4,	'backend',	'User Manager',	'Benutzer \'Shopware Demo\' wurde upgedated. Damit die Änderungen für die eingeloggten Benutzer übernommen werden, logge dich neu ein und leere den Backend-Cache.',	'2026-02-02 16:22:38',	'Shopware Demo',	'212.185.0.0',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36',	''),
(5,	'backend',	'',	'Damit Du Informationen zu Updates erhältst und Plugins installieren kannst, musst Du Dich in Deinen Shopware Account einloggen.<br> Solltest Du noch keinen Shopware Account besitzen, kannst Du Dich ganz einfach registrieren.',	'2026-02-03 07:53:24',	'Shopware Demo',	'212.185.0.0',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36',	''),
(6,	'backend',	'',	'Damit Du Informationen zu Updates erhältst und Plugins installieren kannst, musst Du Dich in Deinen Shopware Account einloggen.<br> Solltest Du noch keinen Shopware Account besitzen, kannst Du Dich ganz einfach registrieren.',	'2026-02-04 08:31:05',	'Shopware Demo',	'212.185.0.0',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36',	'');

DROP TABLE IF EXISTS `s_core_menu`;
CREATE TABLE `s_core_menu` (
  `id` int NOT NULL AUTO_INCREMENT,
  `parent` int unsigned DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `onclick` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `class` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `position` int NOT NULL DEFAULT '0',
  `active` int NOT NULL DEFAULT '0',
  `pluginID` int unsigned DEFAULT NULL,
  `controller` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `shortcut` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `action` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `content_type` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`,`parent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_menu` (`id`, `parent`, `name`, `onclick`, `class`, `position`, `active`, `pluginID`, `controller`, `shortcut`, `action`, `content_type`) VALUES
(1,	NULL,	'Artikel',	NULL,	'ico package_green article--main',	0,	1,	NULL,	'Article',	NULL,	NULL,	NULL),
(2,	1,	'Anlegen',	'',	'sprite-inbox--plus article--add-article',	-3,	1,	NULL,	'Article',	'STRG + ALT + N',	'Detail',	NULL),
(4,	1,	'Kategorien',	'',	'sprite-blue-folders-stack article--categories',	0,	1,	NULL,	'Category',	NULL,	'Index',	NULL),
(6,	1,	'Hersteller',	NULL,	'sprite-truck article--manufacturers',	2,	1,	NULL,	'Supplier',	NULL,	'Index',	NULL),
(7,	NULL,	'Inhalte',	NULL,	'ico2 note03 contents--main',	0,	1,	NULL,	'Content',	NULL,	NULL,	NULL),
(8,	30,	'Banner',	NULL,	'sprite-image-medium marketing--banner',	0,	1,	NULL,	'Banner',	NULL,	'Index',	NULL),
(9,	30,	'Einkaufswelten',	'',	'sprite-pin marketing--shopping-worlds',	1,	1,	NULL,	'Emotion',	NULL,	'Index',	NULL),
(10,	30,	'Gutscheine',	NULL,	'sprite-mail-open-image marketing--vouchers',	3,	1,	NULL,	'Voucher',	NULL,	'Index',	NULL),
(11,	30,	'Pr&auml;mienartikel',	NULL,	'sprite-star marketing--premium-items',	2,	1,	NULL,	'Premium',	NULL,	'Index',	NULL),
(12,	30,	'Produktexporte',	NULL,	'sprite-folder-export marketing--product-exports',	5,	1,	NULL,	'ProductFeed',	NULL,	'Index',	NULL),
(15,	7,	'Shopseiten',	NULL,	'sprite-documents contents--shopsites',	0,	1,	NULL,	'Site',	NULL,	'Index',	NULL),
(20,	NULL,	'Kunden',	NULL,	'ico customer customers--main',	0,	1,	NULL,	'Customer',	NULL,	NULL,	NULL),
(21,	20,	'Kundenliste',	NULL,	'sprite-ui-scroll-pane-detail customers--customer-list',	0,	1,	NULL,	'Customer',	'STRG + ALT + K',	'Index',	NULL),
(22,	20,	'Bestellungen',	NULL,	'sprite-sticky-notes-pin customers--orders',	0,	1,	NULL,	'Order',	'STRG + ALT + B',	'Index',	NULL),
(23,	NULL,	'Einstellungen',	NULL,	'ico2 wrench_screwdriver settings--main',	0,	1,	NULL,	'ConfigurationMenu',	NULL,	NULL,	NULL),
(25,	23,	'Benutzerverwaltung',	NULL,	'sprite-user-silhouette settings--user-management',	-2,	1,	NULL,	'UserManager',	NULL,	'Index',	NULL),
(26,	23,	'Versandkosten',	NULL,	'sprite-envelope--arrow settings--delivery-charges',	0,	1,	NULL,	'Shipping',	NULL,	'Index',	NULL),
(27,	23,	'Zahlungsarten',	NULL,	'sprite-credit-cards settings--payment-methods',	0,	1,	NULL,	'Payment',	NULL,	'Index',	NULL),
(28,	23,	'E-Mail-Verwaltung',	NULL,	'sprite-mails',	0,	1,	NULL,	'MailManagement',	NULL,	NULL,	NULL),
(29,	23,	'Performance',	NULL,	'sprite-bin-full settings--performance',	-5,	1,	NULL,	'Performance',	NULL,	'Index',	NULL),
(30,	NULL,	'Marketing',	NULL,	'ico2 chart_bar01 marketing--main',	0,	1,	NULL,	'Marketing',	NULL,	NULL,	NULL),
(31,	69,	'Übersicht',	NULL,	'sprite-report-paper marketing--analyses--overview',	-5,	1,	NULL,	'Overview',	NULL,	'Index',	NULL),
(32,	69,	'Statistiken / Diagramme',	NULL,	'sprite-chart',	-4,	1,	NULL,	'Analytics',	NULL,	'Index',	NULL),
(40,	NULL,	'',	NULL,	'ico question_frame shopware-help-menu',	999,	1,	NULL,	NULL,	NULL,	NULL,	NULL),
(41,	114,	'Onlinehilfe aufrufen',	'window.open(\'https://docs.shopware.com\')',	'sprite-lifebuoy misc--help--online-help',	0,	1,	NULL,	'Onlinehelp',	NULL,	NULL,	NULL),
(44,	40,	'Über Shopware',	'createShopwareVersionMessage()',	'sprite-shopware-logo misc--about-shopware',	2,	1,	NULL,	'AboutShopware',	NULL,	'Index',	NULL),
(50,	1,	'Bewertungen',	NULL,	'sprite-balloon article--ratings',	3,	1,	NULL,	'Vote',	NULL,	'Index',	NULL),
(56,	30,	'Partnerprogramm',	'',	'sprite-xfn-colleague marketing--partner-program',	6,	1,	NULL,	'Partner',	NULL,	'Index',	NULL),
(57,	7,	'Formulare',	NULL,	'sprite-application-form contents--forms',	2,	1,	NULL,	'Form',	NULL,	'Index',	NULL),
(58,	30,	'Newsletter',	'',	'sprite-paper-plane marketing--newsletters',	7,	1,	NULL,	'NewsletterManager',	NULL,	'Index',	NULL),
(59,	69,	'Abbruch-Analyse',	'',	'sprite-chart-down-color marketing--analyses--abort-analyses',	0,	1,	NULL,	'CanceledOrder',	NULL,	'Index',	NULL),
(62,	23,	'Riskmanagement',	'',	'sprite-funnel--exclamation',	0,	1,	NULL,	'RiskManagement',	NULL,	'Index',	NULL),
(63,	23,	'Systeminfo',	NULL,	'sprite-blueprint settings--system-info',	-3,	1,	40,	'Systeminfo',	NULL,	'Index',	NULL),
(64,	7,	'Medienverwaltung',	NULL,	'sprite-inbox-image contents--media-manager',	4,	1,	NULL,	'MediaManager',	NULL,	'Index',	NULL),
(65,	20,	'Zahlungen',	NULL,	'sprite-credit-cards settings--payment-methods',	0,	1,	NULL,	'Payments',	NULL,	NULL,	NULL),
(66,	1,	'Übersicht',	'',	'sprite-ui-scroll-pane-list article--overview',	-2,	1,	NULL,	'ArticleList',	'STRG + ALT + O',	'Index',	NULL),
(68,	23,	'Logfile',	'',	'sprite-cards-stack settings--logfile',	-2,	1,	NULL,	'Log',	NULL,	'Index',	NULL),
(69,	30,	'Auswertungen',	NULL,	'sprite-chart marketing--analyses',	-1,	1,	NULL,	'AnalysisMenu',	NULL,	NULL,	NULL),
(72,	1,	'Eigenschaften',	'',	'sprite-property-blue article--properties',	0,	1,	NULL,	'Property',	NULL,	'Index',	NULL),
(75,	20,	'Anlegen',	'',	'sprite-user--plus customers--add-customer',	-1,	1,	NULL,	'Customer',	NULL,	'Detail',	NULL),
(84,	69,	'E-Mail Benachrichtigung',	'',	'sprite-mail-forward',	4,	1,	NULL,	'Notification',	NULL,	'Index',	NULL),
(85,	7,	'Blog',	'',	'sprite-application-blog contents--blog',	1,	1,	NULL,	'Blog',	NULL,	'Index',	NULL),
(88,	114,	'Zum Forum',	'window.open(\'https://forum.shopware.com\')',	'sprite-balloons-box misc--help--board',	-1,	1,	NULL,	'Forum',	NULL,	NULL,	NULL),
(91,	29,	'Shopcache leeren',	NULL,	'sprite-edit-shade settings--performance--cache',	1,	1,	NULL,	'Performance',	'STRG + ALT + X',	'Config',	NULL),
(107,	23,	'Textbausteine',	NULL,	'sprite-edit-shade settings--snippets',	0,	1,	NULL,	'Snippet',	NULL,	'Index',	NULL),
(109,	40,	'Tastaturk&uuml;rzel',	'createKeyNavOverlay()',	'sprite-keyboard-command misc--shortcuts',	1,	1,	NULL,	'ShortCutMenu',	'STRG + ALT + H',	'Index',	NULL),
(110,	23,	'Grundeinstellungen',	NULL,	'sprite-wrench-screwdriver settings--basic-settings',	-5,	1,	NULL,	'Config',	NULL,	'Index',	NULL),
(114,	40,	'Hilfe',	NULL,	'sprite-lifebuoy misc--help',	0,	1,	NULL,	'HelpMenu',	NULL,	NULL,	NULL),
(115,	40,	'Feedback senden',	'window.open(\'https://github.com/shopware5/shopware/issues\')',	'sprite-briefcase--arrow',	0,	1,	NULL,	'Feedback',	NULL,	'Index',	NULL),
(118,	40,	'SwagUpdate',	NULL,	'sprite-arrow-continue-090 misc--software-update',	0,	1,	55,	'SwagUpdate',	NULL,	'Index',	NULL),
(119,	23,	'Theme Manager',	NULL,	'sprite-application-icon-large settings--theme-manager',	0,	1,	NULL,	'Theme',	NULL,	'Index',	NULL),
(120,	23,	'Plugin Manager',	NULL,	'sprite-application-block settings--plugin-manager',	0,	1,	56,	'PluginManager',	'STRG + ALT + P',	'Index',	NULL),
(121,	23,	'Premium Plugins',	NULL,	'sprite-star settings--premium-plugins',	0,	1,	56,	'PluginManager',	NULL,	'PremiumPlugins',	NULL),
(122,	1,	'Product Streams',	'',	'sprite-product-streams',	50,	1,	NULL,	'ProductStream',	'',	'index',	NULL),
(123,	23,	'Freitextfeld-Verwaltung',	'',	'sprite-attributes',	-1,	1,	NULL,	'Attributes',	NULL,	'Index',	NULL),
(124,	29,	'Performance',	NULL,	'sprite-bin-full settings--performance',	2,	1,	NULL,	'Performance',	NULL,	'Index',	NULL),
(125,	NULL,	'Connect',	NULL,	'shopware-connect',	0,	1,	NULL,	NULL,	NULL,	NULL,	NULL),
(127,	7,	'Import/Export',	NULL,	'sprite-arrow-circle-double-135 contents--import-export',	3,	1,	NULL,	'PluginManager',	NULL,	'ImportExport',	NULL),
(128,	20,	'Customer Streams',	'',	'sprite-customer-streams',	20,	1,	NULL,	'Customer',	NULL,	'customer_stream',	NULL),
(132,	28,	'E-Mail-Vorlagen',	NULL,	'sprite-mail--pencil settings--mail-presets',	0,	1,	NULL,	'Mail',	NULL,	'Index',	NULL),
(133,	28,	'E-Mail-Log',	NULL,	'sprite-inbox-document',	1,	1,	NULL,	'MailLog',	NULL,	'Index',	NULL),
(134,	23,	'Inhaltstypen',	NULL,	'sprite-application-form',	0,	1,	NULL,	'ContentTypeManager',	NULL,	'index',	NULL),
(135,	114,	'Support',	'window.open(\'https://account.shopware.com/shops/support?referrer=backend\')',	'sprite-balloons-box sprite-balloon-ellipsis',	-2,	1,	NULL,	'Support',	NULL,	NULL,	NULL),
(136,	40,	'UpdateCheck',	NULL,	'sprite-arrow-continue-090 misc--software-update',	0,	1,	61,	'SwagMigrationConnector',	NULL,	'index',	NULL);

DROP TABLE IF EXISTS `s_core_optin`;
CREATE TABLE `s_core_optin` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `datum` datetime NOT NULL,
  `hash` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `data` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hash` (`hash`),
  KEY `datum` (`datum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_core_payment_data`;
CREATE TABLE `s_core_payment_data` (
  `id` int NOT NULL AUTO_INCREMENT,
  `payment_mean_id` int NOT NULL,
  `user_id` int NOT NULL,
  `use_billing_data` int DEFAULT NULL,
  `bankname` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `bic` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `iban` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `account_number` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `bank_code` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `account_holder` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `created_at` date NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_mean_id_2` (`payment_mean_id`,`user_id`),
  KEY `payment_mean_id` (`payment_mean_id`,`user_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_core_payment_instance`;
CREATE TABLE `s_core_payment_instance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `payment_mean_id` int DEFAULT NULL,
  `order_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `firstname` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `lastname` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `address` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `zipcode` varchar(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `city` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `account_number` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `account_holder` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `bank_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `bank_code` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `bic` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `iban` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `amount` decimal(20,4) DEFAULT NULL,
  `created_at` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `payment_mean_id` (`payment_mean_id`),
  KEY `payment_mean_id_2` (`payment_mean_id`),
  KEY `order_id` (`order_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_core_paymentmeans`;
CREATE TABLE `s_core_paymentmeans` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `template` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `class` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `table` varchar(70) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `hide` int NOT NULL,
  `additionaldescription` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `debit_percent` double NOT NULL DEFAULT '0',
  `surcharge` double NOT NULL DEFAULT '0',
  `surchargestring` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `position` int NOT NULL,
  `active` int NOT NULL DEFAULT '0',
  `esdactive` int NOT NULL,
  `embediframe` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `hideprospect` int NOT NULL,
  `action` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `pluginID` int unsigned DEFAULT NULL,
  `source` int DEFAULT NULL,
  `mobile_inactive` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_paymentmeans` (`id`, `name`, `description`, `template`, `class`, `table`, `hide`, `additionaldescription`, `debit_percent`, `surcharge`, `surchargestring`, `position`, `active`, `esdactive`, `embediframe`, `hideprospect`, `action`, `pluginID`, `source`, `mobile_inactive`) VALUES
(2,	'debit',	'Lastschrift',	'debit.tpl',	'debit.php',	'',	0,	'Zusatztext',	0,	0,	'',	4,	0,	0,	'',	0,	NULL,	NULL,	NULL,	0),
(3,	'cash',	'Nachnahme',	'cash.tpl',	'cash.php',	'',	0,	'(zzgl. 2,00 Euro Nachnahmegebühren)',	0,	0,	'',	2,	0,	0,	'',	0,	NULL,	NULL,	NULL,	0),
(4,	'invoice',	'Rechnung',	'invoice.tpl',	'invoice.php',	'',	0,	'Sie zahlen einfach und bequem auf Rechnung. Shopware bietet z.B. auch die Möglichkeit, Rechnung automatisiert erst ab der 2. Bestellung für Kunden zur Verfügung zu stellen, um Zahlungsausfälle zu vermeiden.',	0,	0,	'',	3,	0,	1,	'',	0,	NULL,	NULL,	NULL,	0),
(5,	'prepayment',	'Vorkasse',	'prepayment.tpl',	'prepayment.php',	'',	0,	'Sie zahlen einfach vorab und erhalten die Ware bequem und günstig bei Zahlungseingang nach Hause geliefert.',	0,	0,	'',	1,	1,	0,	'',	0,	NULL,	NULL,	NULL,	0),
(6,	'sepa',	'SEPA',	'sepa.tpl',	'sepa',	'',	0,	'SEPA debit',	0,	0,	'',	5,	0,	0,	'',	0,	'',	NULL,	1,	0);

DROP TABLE IF EXISTS `s_core_paymentmeans_attributes`;
CREATE TABLE `s_core_paymentmeans_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `paymentmeanID` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `paymentmeanID` (`paymentmeanID`),
  CONSTRAINT `s_core_paymentmeans_attributes_ibfk_1` FOREIGN KEY (`paymentmeanID`) REFERENCES `s_core_paymentmeans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_core_paymentmeans_countries`;
CREATE TABLE `s_core_paymentmeans_countries` (
  `paymentID` int unsigned NOT NULL,
  `countryID` int unsigned NOT NULL,
  PRIMARY KEY (`paymentID`,`countryID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_core_paymentmeans_subshops`;
CREATE TABLE `s_core_paymentmeans_subshops` (
  `paymentID` int unsigned NOT NULL,
  `subshopID` int unsigned NOT NULL,
  PRIMARY KEY (`paymentID`,`subshopID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_core_plugin_categories`;
CREATE TABLE `s_core_plugin_categories` (
  `id` int NOT NULL,
  `locale` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `parent_id` int DEFAULT NULL,
  `name` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`,`locale`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_plugin_categories` (`id`, `locale`, `parent_id`, `name`) VALUES
(-3,	'de_DE',	NULL,	'Empfehlungen'),
(-3,	'en_GB',	NULL,	'Recommendation'),
(-2,	'de_DE',	NULL,	'Neuheiten'),
(-2,	'en_GB',	NULL,	'Newcomer'),
(-1,	'de_DE',	NULL,	'Highlights'),
(-1,	'en_GB',	NULL,	'Highlights'),
(1,	'de_DE',	69,	'Administration'),
(1,	'en_GB',	69,	'Administration'),
(2,	'de_DE',	1,	'Backend-Bearbeitung'),
(2,	'en_GB',	1,	'Backend editing'),
(3,	'de_DE',	1,	'System'),
(3,	'en_GB',	1,	'System'),
(4,	'de_DE',	69,	'SEO Optimierung'),
(4,	'en_GB',	69,	'SEO Optimization'),
(5,	'de_DE',	12,	'Bonitätsprüfung'),
(5,	'en_GB',	12,	'Credit assessment'),
(6,	'de_DE',	12,	'Rechtssicherheit'),
(6,	'en_GB',	12,	'Legal security'),
(7,	'de_DE',	1,	'Mobile Administration'),
(7,	'en_GB',	1,	'Mobile administration'),
(8,	'de_DE',	69,	'Auswertung & Analytics'),
(8,	'en_GB',	69,	'Reporting & Analysis'),
(9,	'de_DE',	72,	'Kommentar / Feedback'),
(9,	'en_GB',	72,	'Comments / Feedback'),
(10,	'de_DE',	8,	'Tracking'),
(10,	'en_GB',	8,	'Tracking'),
(11,	'de_DE',	8,	'Mobile Auswertung'),
(11,	'en_GB',	8,	'Mobile analysis'),
(12,	'de_DE',	69,	'Integration'),
(12,	'en_GB',	69,	'Integration'),
(13,	'de_DE',	12,	'Shopsystem'),
(13,	'en_GB',	12,	'Shop system'),
(14,	'de_DE',	69,	'Preissuchmaschinen / -portale'),
(14,	'en_GB',	69,	'Price search engine / portal'),
(15,	'de_DE',	12,	'Warenwirtschaft'),
(15,	'en_GB',	12,	'ERP'),
(16,	'de_DE',	69,	'Versanddienstleister'),
(16,	'en_GB',	69,	'Shipping provider'),
(17,	'de_DE',	69,	'Zahlungsdienstleister'),
(17,	'en_GB',	69,	'Payment provider'),
(18,	'de_DE',	69,	'Storefront'),
(18,	'en_GB',	69,	'Storefront'),
(19,	'de_DE',	69,	'Sprachen & Internationalisierung'),
(19,	'en_GB',	69,	'Language & Internationalisation'),
(20,	'de_DE',	18,	'Suche & Filter'),
(20,	'en_GB',	18,	'Search & Filter'),
(21,	'de_DE',	18,	'Header & Footer'),
(21,	'en_GB',	18,	'Header & Footer'),
(22,	'de_DE',	18,	'Produktdarstellung'),
(22,	'en_GB',	18,	'Product presentation'),
(23,	'de_DE',	22,	'Übergreifende Darstellung'),
(23,	'en_GB',	22,	'Cross presentation'),
(24,	'de_DE',	18,	'Detailseite'),
(24,	'en_GB',	18,	'Detail Page'),
(25,	'de_DE',	18,	'Menü + Kategorien'),
(25,	'en_GB',	18,	'Menu + category'),
(26,	'de_DE',	69,	'Checkout / Bestellprozess'),
(26,	'en_GB',	69,	'Checkout / Cart process'),
(27,	'de_DE',	69,	'Kundenkonto'),
(27,	'en_GB',	69,	'Customer account'),
(28,	'de_DE',	18,	'Icons + Buttons'),
(28,	'en_GB',	18,	'Icons + Buttons'),
(29,	'de_DE',	18,	'Schriftarten'),
(29,	'en_GB',	18,	'Typefaces'),
(30,	'de_DE',	69,	'Mobil'),
(30,	'en_GB',	69,	'Mobile'),
(31,	'de_DE',	18,	'Widgets + Snippets'),
(31,	'en_GB',	18,	'Widgets + snippets'),
(32,	'de_DE',	18,	'Sonderfunktionen'),
(32,	'en_GB',	18,	'Special features'),
(33,	'de_DE',	NULL,	'Themes'),
(33,	'en_GB',	NULL,	'Themes'),
(34,	'de_DE',	33,	'Branche'),
(34,	'en_GB',	33,	'Industry'),
(35,	'de_DE',	34,	'Haus + Einrichtung'),
(35,	'en_GB',	34,	'Home + furnishings'),
(36,	'de_DE',	34,	'Fashion + Bekleidung'),
(36,	'en_GB',	34,	'Fashion + clothing'),
(37,	'de_DE',	34,	'Garten + Natur'),
(37,	'en_GB',	34,	'Garden + nature'),
(38,	'de_DE',	34,	'Kosmetik + Gesundheit'),
(38,	'en_GB',	34,	'Cosmetic + health'),
(39,	'de_DE',	34,	'Essen + Trinken'),
(39,	'en_GB',	34,	'Food + drink'),
(40,	'de_DE',	34,	'Kinder, Party + Geschenke'),
(40,	'en_GB',	34,	'Children, party + gifts'),
(41,	'de_DE',	34,	'Sport, Lifestyle + Reisen'),
(41,	'en_GB',	34,	'Sport, lifestyle + travel'),
(42,	'de_DE',	34,	'Technik + IT'),
(42,	'en_GB',	34,	'Technology + IT'),
(43,	'de_DE',	34,	'Industrie + Großhandel'),
(43,	'en_GB',	34,	'Industry + wholesale'),
(44,	'de_DE',	33,	'Farbschema'),
(44,	'en_GB',	33,	'Color scheme'),
(45,	'de_DE',	44,	'Rot'),
(45,	'en_GB',	44,	'Red'),
(46,	'de_DE',	44,	'Orange'),
(46,	'en_GB',	44,	'Orange'),
(47,	'de_DE',	44,	'Gelb'),
(47,	'en_GB',	44,	'Yellow'),
(48,	'de_DE',	44,	'Grün'),
(48,	'en_GB',	44,	'Green'),
(49,	'de_DE',	44,	'Blau'),
(49,	'en_GB',	44,	'Blue'),
(50,	'de_DE',	44,	'Lila / pink'),
(50,	'en_GB',	44,	'Purple / pink'),
(51,	'de_DE',	44,	'Schwarz / grau'),
(51,	'en_GB',	44,	'Black / grey'),
(52,	'de_DE',	44,	'Weiß / clear'),
(52,	'en_GB',	44,	'White / clear'),
(53,	'de_DE',	44,	'SALE (grelle Farben)'),
(53,	'en_GB',	44,	'SALE (bright colors)'),
(54,	'de_DE',	44,	'Edel'),
(54,	'en_GB',	44,	'Noble'),
(55,	'de_DE',	44,	'Bunt'),
(55,	'en_GB',	44,	'Colorful'),
(56,	'de_DE',	69,	'Von Shopware entwickelt'),
(56,	'en_GB',	69,	'Developed by Shopware'),
(57,	'de_DE',	69,	'Shopware 5 Premium Apps'),
(57,	'en_GB',	69,	'Shopware 5 Premium Apps'),
(58,	'de_DE',	56,	'Plugins'),
(58,	'en_GB',	56,	'Plugins'),
(59,	'de_DE',	56,	'SDK'),
(59,	'en_GB',	56,	'SDK.'),
(60,	'de_DE',	69,	'Migrations-Tools'),
(60,	'en_GB',	69,	'Migration tools'),
(61,	'de_DE',	56,	'Shopware 3'),
(61,	'en_GB',	56,	'Shopware 3'),
(62,	'de_DE',	69,	'Erlebniswelten'),
(62,	'en_GB',	69,	'Shopping Experiences'),
(63,	'de_DE',	194,	'Die beliebtesten kostenlosen Erweiterungen'),
(63,	'en_GB',	194,	'Best free Extensions'),
(64,	'de_DE',	17,	'Shopware 5 Core Integrationen'),
(64,	'en_GB',	17,	'Shopware 5 Core Integrations'),
(65,	'de_DE',	69,	'Conversion Optimierung'),
(65,	'en_GB',	69,	'Conversion Optimization'),
(66,	'de_DE',	NULL,	'Sale'),
(66,	'en_GB',	NULL,	'Sale'),
(67,	'de_DE',	NULL,	'Plans'),
(67,	'en_GB',	NULL,	'Plans'),
(68,	'de_DE',	69,	'Bundle Angebote'),
(68,	'en_GB',	69,	'Bundle offers'),
(69,	'de_DE',	NULL,	'Erweiterungen'),
(69,	'en_GB',	NULL,	'Extensions'),
(70,	'de_DE',	56,	'Shopware ERP powered by Pickware'),
(70,	'en_GB',	56,	'Shopware ERP powered by Pickware'),
(72,	'de_DE',	69,	'Marketing'),
(72,	'en_GB',	69,	'Marketing'),
(73,	'de_DE',	69,	'B2B-Erweiterungen'),
(73,	'en_GB',	69,	'B2B extensions'),
(74,	'de_DE',	NULL,	'Black Week Sale'),
(74,	'en_GB',	NULL,	'Extended Black Friday Sale'),
(76,	'de_DE',	194,	'Die beliebtesten Apps'),
(76,	'en_GB',	194,	'Most used Apps'),
(77,	'de_DE',	69,	'Die besten Tools, um international zu verkaufen'),
(77,	'en_GB',	69,	'The best tools to sell internationally '),
(78,	'de_DE',	33,	'Von Shopware entwickelt'),
(78,	'en_GB',	33,	'Developed by Shopware'),
(79,	'de_DE',	69,	'Die besten Analyse-Tools'),
(79,	'en_GB',	69,	'The best analysis tools'),
(80,	'de_DE',	194,	'Die beliebtesten Erweiterungen'),
(80,	'en_GB',	194,	'Most used Extensions'),
(81,	'de_DE',	69,	'Neue Erweiterungen'),
(81,	'en_GB',	69,	'New Extensions'),
(82,	'de_DE',	69,	'Letzte Updates'),
(82,	'en_GB',	69,	'Last updates'),
(83,	'de_DE',	69,	'Top Erweiterungen'),
(83,	'en_GB',	69,	'Top Extensions'),
(84,	'de_DE',	33,	'Neue Themes'),
(84,	'en_GB',	33,	'New Themes'),
(85,	'de_DE',	33,	'Top Themes'),
(85,	'en_GB',	33,	'Top Themes'),
(86,	'de_DE',	33,	'Letzte Updates'),
(86,	'en_GB',	33,	'Last Updates'),
(87,	'de_DE',	71,	'Live-Schulungen'),
(87,	'en_GB',	71,	'On-site trainings'),
(88,	'de_DE',	71,	'Online-Zertifizierungen'),
(88,	'en_GB',	71,	'Online certifications'),
(90,	'de_DE',	44,	'Frei konfigurierbar'),
(90,	'en_GB',	44,	'Completly configurable'),
(92,	'de_DE',	74,	'B2B-Erweiterungen'),
(92,	'en_GB',	74,	'B2B extensions'),
(93,	'de_DE',	74,	'Marketing-Tools'),
(93,	'en_GB',	74,	'Marketing-Tools'),
(94,	'de_DE',	74,	'Conversion Optimierung'),
(94,	'en_GB',	74,	'Conversion Optimization'),
(95,	'de_DE',	74,	'Einkaufswelten'),
(95,	'en_GB',	74,	'Shopping Worlds'),
(96,	'de_DE',	74,	'Migrations-Tools'),
(96,	'en_GB',	74,	'Migration tools'),
(97,	'de_DE',	74,	'Mobil'),
(97,	'en_GB',	74,	'Mobile'),
(98,	'de_DE',	74,	'Kundenkonto + Personalisierung'),
(98,	'en_GB',	74,	'Customer account + personalization'),
(99,	'de_DE',	74,	'Bestellprozess (Checkout)'),
(99,	'en_GB',	74,	'Checkout process'),
(100,	'de_DE',	74,	'Sprachen & Internationalisierung'),
(100,	'en_GB',	74,	'Language & Internationalisation'),
(101,	'de_DE',	74,	'Storefront / Detailanpassungen'),
(101,	'en_GB',	74,	'Frontend / detail adjustment'),
(102,	'de_DE',	74,	'Preissuchmaschinen / -portale'),
(102,	'en_GB',	74,	'Price search engine / portal'),
(103,	'de_DE',	74,	'Integration'),
(103,	'en_GB',	74,	'Integration'),
(104,	'de_DE',	74,	'Auswertung und Analyse'),
(104,	'en_GB',	74,	'Evaluation and Analysis'),
(105,	'de_DE',	74,	'SEO Optimierung'),
(105,	'en_GB',	74,	'SEO Optimization'),
(106,	'de_DE',	74,	'Administration'),
(106,	'en_GB',	74,	'Administration'),
(107,	'de_DE',	74,	'Themes'),
(107,	'en_GB',	74,	'Themes'),
(108,	'de_DE',	74,	'Sonstiges'),
(108,	'en_GB',	74,	'Other'),
(109,	'de_DE',	74,	'Premium Plugins'),
(109,	'en_GB',	74,	'Premium Plugins'),
(111,	'de_DE',	69,	'GitHub'),
(111,	'en_GB',	69,	'GitHub'),
(112,	'de_DE',	69,	'Winter Sale'),
(112,	'en_GB',	69,	'Winter Sale'),
(113,	'de_DE',	18,	'Blog'),
(113,	'en_GB',	18,	'Blog'),
(114,	'de_DE',	69,	'Oster Sale'),
(114,	'en_GB',	69,	'Easter Sale'),
(115,	'de_DE',	12,	'Mobile / Express Bezahlung'),
(115,	'en_GB',	12,	'Mobile / Express Payment'),
(116,	'de_DE',	NULL,	'Summer Sale'),
(116,	'en_GB',	NULL,	'Summer Sale'),
(117,	'de_DE',	NULL,	'News'),
(117,	'en_GB',	NULL,	'News'),
(119,	'de_DE',	69,	'Die neuesten Shopware 6 Plugins'),
(119,	'en_GB',	69,	'The newest Shopware 6 plugins'),
(120,	'de_DE',	69,	'Covid-19 - Plugins zur Unterstützung'),
(120,	'en_GB',	69,	'Covid-19 - Plugins for support'),
(121,	'de_DE',	69,	'20 Jahre Shopware - Jubiläums-Sale'),
(121,	'en_GB',	69,	'20 years Shopware - Anniversary sale'),
(122,	'de_DE',	74,	'Weihnachtshelfer'),
(122,	'en_GB',	74,	'Christmas helpers'),
(123,	'de_DE',	112,	'B2B-Erweiterungen'),
(123,	'en_GB',	112,	'B2B extensions'),
(124,	'de_DE',	112,	'Marketing-Tools'),
(124,	'en_GB',	112,	'Marketing-Tools'),
(125,	'de_DE',	112,	'Conversion Optimierung'),
(125,	'en_GB',	112,	'Conversion Optimization'),
(126,	'de_DE',	112,	'Erlebniswelten'),
(126,	'en_GB',	112,	'Shopping Experiences'),
(127,	'de_DE',	112,	'Migrations-Tools'),
(127,	'en_GB',	112,	'Migration tools'),
(128,	'de_DE',	112,	'Kundenkonto + Personalisierung'),
(128,	'en_GB',	112,	'Customer account + personalization'),
(129,	'de_DE',	112,	'Bestellprozess (Checkout)'),
(129,	'en_GB',	112,	'Checkout process'),
(130,	'de_DE',	112,	'Sprachen & Internationalisierung'),
(130,	'en_GB',	112,	'Language & Internationalisation'),
(131,	'de_DE',	112,	'Storefront / Detailanpassungen'),
(131,	'en_GB',	112,	'Frontend / detail adjustment'),
(132,	'de_DE',	112,	'Preissuchmaschinen / -portale'),
(132,	'en_GB',	112,	'Price search engine / portal'),
(133,	'de_DE',	112,	'Integration'),
(133,	'en_GB',	112,	'Integration'),
(134,	'de_DE',	112,	'Auswertung und Analyse'),
(134,	'en_GB',	112,	'Evaluation and Analysis'),
(135,	'de_DE',	112,	'SEO Optimierung'),
(135,	'en_GB',	112,	'SEO Optimization'),
(136,	'de_DE',	112,	'Administration'),
(136,	'en_GB',	112,	'Administration'),
(137,	'de_DE',	112,	'Themes'),
(137,	'en_GB',	112,	'Themes'),
(138,	'de_DE',	112,	'Shopware 5 Premium Plugins'),
(138,	'en_GB',	112,	'Shopware 5 Premium Plugins'),
(139,	'de_DE',	112,	'Sonstiges'),
(139,	'en_GB',	112,	'Other'),
(140,	'de_DE',	66,	'B2B-Erweiterungen'),
(140,	'en_GB',	66,	'B2B extensions'),
(141,	'de_DE',	66,	'Marketing-Tools'),
(141,	'en_GB',	66,	'Marketing-Tools'),
(142,	'de_DE',	66,	'Conversion Optimierung'),
(142,	'en_GB',	66,	'Conversion Optimization'),
(143,	'de_DE',	66,	'Erlebniswelten'),
(143,	'en_GB',	66,	'Shopping Experiences'),
(144,	'de_DE',	66,	'Migrations-Tools'),
(144,	'en_GB',	66,	'Migration tools'),
(145,	'de_DE',	66,	'Kundenkonto'),
(145,	'en_GB',	66,	'Customer account'),
(146,	'de_DE',	66,	'Checkout / Bestellprozess'),
(146,	'en_GB',	66,	'Checkout / Cart process'),
(147,	'de_DE',	66,	'Sprachen & Internationalisierung'),
(147,	'en_GB',	66,	'Language & Internationalisation'),
(148,	'de_DE',	66,	'Storefront'),
(148,	'en_GB',	66,	'Storefront'),
(149,	'de_DE',	66,	'Preissuchmaschinen / -portale'),
(149,	'en_GB',	66,	'Price search engine / portal'),
(150,	'de_DE',	66,	'Integration'),
(150,	'en_GB',	66,	'Integration'),
(151,	'de_DE',	66,	'Auswertung & Analyse'),
(151,	'en_GB',	66,	'Evaluation & Analysis'),
(152,	'de_DE',	66,	'SEO Optimierung'),
(152,	'en_GB',	66,	'SEO Optimization'),
(153,	'de_DE',	66,	'Administration'),
(153,	'en_GB',	66,	'Administration'),
(154,	'de_DE',	66,	'Themes'),
(154,	'en_GB',	66,	'Themes'),
(155,	'de_DE',	66,	'Shopware 5 Premium Plugins'),
(155,	'en_GB',	66,	'Shopware 5 Premium Plugins'),
(156,	'de_DE',	66,	'Sonstiges'),
(156,	'en_GB',	66,	'Other'),
(157,	'de_DE',	NULL,	'Developers'),
(157,	'en_GB',	NULL,	'Developers'),
(158,	'de_DE',	114,	'B2B-Erweiterungen'),
(158,	'en_GB',	114,	'B2B extensions'),
(159,	'de_DE',	114,	'Marketing-Tools'),
(159,	'en_GB',	114,	'Marketing-Tools'),
(160,	'de_DE',	114,	'Conversion Optimierung'),
(160,	'en_GB',	114,	'Conversion Optimization'),
(161,	'de_DE',	114,	'Erlebniswelten'),
(161,	'en_GB',	114,	'Shopping Experiences'),
(162,	'de_DE',	114,	'Migrations-Tools'),
(162,	'en_GB',	114,	'Migration tools'),
(163,	'de_DE',	114,	'Kundenkonto + Personalisierung'),
(163,	'en_GB',	114,	'Customer account + personalization'),
(164,	'de_DE',	114,	'Bestellprozess (Checkout)'),
(164,	'en_GB',	114,	'Checkout process'),
(165,	'de_DE',	114,	'Sprachen & Internationalisierung'),
(165,	'en_GB',	114,	'Language & Internationalisation'),
(166,	'de_DE',	114,	'Storefront / Detailanpassungen'),
(166,	'en_GB',	114,	'Frontend / detail adjustment'),
(167,	'de_DE',	114,	'Preissuchmaschinen / -portale'),
(167,	'en_GB',	114,	'Price search engine / portal'),
(168,	'de_DE',	114,	'Integration'),
(168,	'en_GB',	114,	'Integration'),
(169,	'de_DE',	114,	'Auswertung und Analyse'),
(169,	'en_GB',	114,	'Evaluation and Analysis'),
(170,	'de_DE',	114,	'SEO Optimierung'),
(170,	'en_GB',	114,	'SEO Optimization'),
(171,	'de_DE',	114,	'Administration'),
(171,	'en_GB',	114,	'Administration'),
(172,	'de_DE',	114,	'Themes'),
(172,	'en_GB',	114,	'Themes'),
(173,	'de_DE',	114,	'Shopware 5 Premium Plugins'),
(173,	'en_GB',	114,	'Shopware 5 Premium Plugins'),
(174,	'de_DE',	114,	'Sonstiges'),
(174,	'en_GB',	114,	'Other'),
(175,	'de_DE',	69,	'Coming soon'),
(175,	'en_GB',	69,	'Coming soon'),
(176,	'de_DE',	116,	'B2B-Erweiterungen'),
(176,	'en_GB',	116,	'B2B extensions'),
(177,	'de_DE',	116,	'Marketing-Tools'),
(177,	'en_GB',	116,	'Marketing-Tools'),
(178,	'de_DE',	116,	'Conversion Optimierung'),
(178,	'en_GB',	116,	'Conversion Optimization'),
(179,	'de_DE',	116,	'Erlebniswelten'),
(179,	'en_GB',	116,	'Shopping Experiences'),
(180,	'de_DE',	116,	'Migrations-Tools'),
(180,	'en_GB',	116,	'Migration tools'),
(181,	'de_DE',	116,	'Kundenkonto + Personalisierung'),
(181,	'en_GB',	116,	'Customer account + personalization'),
(182,	'de_DE',	116,	'Bestellprozess (Checkout)'),
(182,	'en_GB',	116,	'Checkout process'),
(183,	'de_DE',	116,	'Sprachen & Internationalisierung'),
(183,	'en_GB',	116,	'Language & Internationalisation'),
(184,	'de_DE',	116,	'Storefront / Detailanpassungen'),
(184,	'en_GB',	116,	'Frontend / detail adjustment'),
(185,	'de_DE',	116,	'Preissuchmaschinen / -portale'),
(185,	'en_GB',	116,	'Price search engine / portal'),
(186,	'de_DE',	116,	'Integration'),
(186,	'en_GB',	116,	'Integration'),
(187,	'de_DE',	116,	'Auswertung und Analyse'),
(187,	'en_GB',	116,	'Evaluation and Analysis'),
(188,	'de_DE',	116,	'SEO Optimierung'),
(188,	'en_GB',	116,	'SEO Optimization'),
(189,	'de_DE',	116,	'Administration'),
(189,	'en_GB',	116,	'Administration'),
(190,	'de_DE',	116,	'Themes'),
(190,	'en_GB',	116,	'Themes'),
(191,	'de_DE',	116,	'Sonstiges'),
(191,	'en_GB',	116,	'Other'),
(192,	'de_DE',	194,	'Die beliebtesten Themes'),
(192,	'en_GB',	194,	'Most used Themes'),
(193,	'de_DE',	66,	'Extended Black Friday Sale'),
(193,	'en_GB',	66,	'Extended Black Friday Sale'),
(194,	'de_DE',	69,	'Best of'),
(194,	'en_GB',	69,	'Best of'),
(195,	'de_DE',	69,	'Mit Shopware loslegen'),
(195,	'en_GB',	69,	'Start with Shopware'),
(196,	'de_DE',	69,	'Shopware Collections'),
(196,	'en_GB',	69,	'Shopware Collections'),
(300,	'de_DE',	69,	'KI Tools'),
(300,	'en_GB',	69,	'AI Tools'),
(301,	'de_DE',	72,	'E-Mail Marketing'),
(301,	'en_GB',	72,	'Email Marketing'),
(302,	'de_DE',	72,	'Gutscheine & Angebote'),
(302,	'en_GB',	72,	'Promotions & Voucher'),
(303,	'de_DE',	72,	'Treueprogramme & Belohnung'),
(303,	'en_GB',	72,	'Loyality & Rewards'),
(304,	'de_DE',	72,	'Empfehlungsmarketing'),
(304,	'en_GB',	72,	'Recommendations'),
(305,	'de_DE',	72,	'Weitere Marketing Lösungen'),
(305,	'en_GB',	72,	'Other Marketing / Commercials'),
(306,	'de_DE',	69,	'Social Commerce'),
(306,	'en_GB',	69,	'Social Commerce'),
(307,	'de_DE',	12,	'Schnittstellen & Middleware'),
(307,	'en_GB',	12,	'Middleware & Connectors'),
(308,	'de_DE',	12,	'PIM & DAM'),
(308,	'en_GB',	12,	'PIM & DAM'),
(310,	'de_DE',	12,	'CMS'),
(310,	'en_GB',	12,	'CMS'),
(311,	'de_DE',	12,	'CRM'),
(311,	'en_GB',	12,	'CRM'),
(312,	'de_DE',	69,	'Personalisierung'),
(312,	'en_GB',	69,	'Personalization'),
(313,	'de_DE',	66,	'Versand'),
(313,	'en_GB',	66,	'Shipping'),
(314,	'de_DE',	66,	'Bezahlung'),
(314,	'en_GB',	66,	'Payment'),
(315,	'de_DE',	66,	'KI Tools'),
(315,	'en_GB',	66,	'AI Tools'),
(316,	'de_DE',	66,	'Social Commerce'),
(316,	'en_GB',	66,	'Social Commerce'),
(317,	'de_DE',	66,	'Personalisierung'),
(317,	'en_GB',	66,	'Personalization'),
(318,	'de_DE',	196,	'ZEO'),
(318,	'en_GB',	196,	'ZEO'),
(319,	'de_DE',	196,	'TC-Innovations'),
(319,	'en_GB',	196,	'TC-Innovations'),
(320,	'de_DE',	196,	'Net Inventors'),
(320,	'en_GB',	196,	'Net Inventors'),
(321,	'de_DE',	69,	'Produktkonfiguration'),
(321,	'en_GB',	69,	'Product Configuration'),
(322,	'de_DE',	196,	'digitvision'),
(322,	'en_GB',	196,	'digitvision'),
(323,	'de_DE',	196,	'PremSoft'),
(323,	'en_GB',	196,	'PremSoft'),
(324,	'de_DE',	33,	'Universelle Themes'),
(324,	'en_GB',	33,	'Multipurpose Themes'),
(325,	'de_DE',	66,	'Produktkonfiguration'),
(325,	'en_GB',	66,	'Product Configuration'),
(326,	'de_DE',	66,	'Zahlungsdienstleister'),
(326,	'en_GB',	66,	'Payment provider'),
(327,	'de_DE',	66,	'Versanddienstleister'),
(327,	'en_GB',	66,	'Shipping provider'),
(328,	'de_DE',	NULL,	'Extension Days'),
(328,	'en_GB',	NULL,	'Extension Days'),
(329,	'de_DE',	196,	'pluszwei'),
(329,	'en_GB',	196,	'pluszwei'),
(330,	'de_DE',	196,	'lenz'),
(330,	'en_GB',	196,	'lenz'),
(331,	'de_DE',	196,	'alphanauten'),
(331,	'en_GB',	196,	'alphanauten'),
(332,	'de_DE',	196,	'swkweb'),
(332,	'en_GB',	196,	'swkweb'),
(333,	'de_DE',	196,	'2hats'),
(333,	'en_GB',	196,	'2hats'),
(334,	'de_DE',	196,	'Acris'),
(334,	'en_GB',	196,	'Acris'),
(335,	'de_DE',	196,	'Kiplingi'),
(335,	'en_GB',	196,	'Kiplingi'),
(336,	'de_DE',	69,	'Lösungen für die USA'),
(336,	'en_GB',	69,	'Solutions for the US'),
(337,	'de_DE',	69,	'Lösungen für AT & CH'),
(337,	'en_GB',	69,	'Solutions for AT & CH'),
(338,	'de_DE',	69,	'Lösungen für Benelux'),
(338,	'en_GB',	69,	'Solutions for Benelux'),
(339,	'de_DE',	69,	'Lösungen für Italien'),
(339,	'en_GB',	69,	'Solutions for Italy'),
(340,	'de_DE',	12,	'OMS & WMS'),
(340,	'en_GB',	12,	'OMS & WMS'),
(342,	'de_DE',	74,	'Produktkonfiguration'),
(342,	'en_GB',	74,	'Product Configuration'),
(343,	'de_DE',	74,	'Personalisierung'),
(343,	'en_GB',	74,	'Personalization'),
(344,	'de_DE',	74,	'Social Commerce'),
(344,	'en_GB',	74,	'Social Commerce'),
(345,	'de_DE',	74,	'KI Tools'),
(345,	'en_GB',	74,	'AI Tools'),
(346,	'de_DE',	74,	'Zahlungsdienstleister'),
(346,	'en_GB',	74,	'Payment provider'),
(347,	'de_DE',	74,	'Versanddienstleister'),
(347,	'en_GB',	74,	'Shipping provider'),
(348,	'de_DE',	112,	'Produktkonfiguration'),
(348,	'en_GB',	112,	'Product Configuration'),
(349,	'de_DE',	112,	'Personalisierung'),
(349,	'en_GB',	112,	'Personalization'),
(350,	'de_DE',	112,	'Social Commerce'),
(350,	'en_GB',	112,	'Social Commerce'),
(351,	'de_DE',	112,	'KI Tools'),
(351,	'en_GB',	112,	'AI Tools'),
(352,	'de_DE',	112,	'Zahlungsdienstleister'),
(352,	'en_GB',	112,	'Payment provider'),
(353,	'de_DE',	112,	'Versanddienstleister'),
(353,	'en_GB',	112,	'Shipping provider'),
(355,	'de_DE',	NULL,	'Integrationen'),
(355,	'en_GB',	NULL,	'Integrations'),
(356,	'de_DE',	355,	'AI'),
(356,	'en_GB',	355,	'AI'),
(357,	'de_DE',	355,	'AR/3D/VR'),
(357,	'en_GB',	355,	'AR/3D/VR'),
(358,	'de_DE',	355,	'CMS'),
(358,	'en_GB',	355,	'CMS'),
(359,	'de_DE',	355,	'ERP'),
(359,	'en_GB',	355,	'ERP'),
(360,	'de_DE',	359,	'Microsoft Business Central'),
(360,	'en_GB',	359,	'Microsoft Business Central'),
(361,	'de_DE',	359,	'Odoo'),
(361,	'en_GB',	359,	'Odoo'),
(362,	'de_DE',	359,	'Pickware'),
(362,	'en_GB',	359,	'Pickware'),
(363,	'de_DE',	359,	'SAP'),
(363,	'en_GB',	359,	'SAP'),
(364,	'de_DE',	359,	'Lösungen'),
(364,	'en_GB',	359,	'Solutions'),
(365,	'de_DE',	355,	'Bestandsverwaltung'),
(365,	'en_GB',	355,	'Inventory Management'),
(366,	'de_DE',	365,	'OMS'),
(366,	'en_GB',	365,	'OMS'),
(367,	'de_DE',	365,	'WMS'),
(367,	'en_GB',	365,	'WMS'),
(368,	'de_DE',	355,	'Rechnungsstellung & Offline-Zahlungen'),
(368,	'en_GB',	355,	'Invoicing & Offline Payments'),
(369,	'de_DE',	355,	'iPaaS'),
(369,	'en_GB',	355,	'iPaaS'),
(370,	'de_DE',	355,	'Rechtliches'),
(370,	'en_GB',	355,	'Legal'),
(371,	'de_DE',	355,	'Kundenbindung, Vertrauen & Bewertungen'),
(371,	'en_GB',	355,	'Loyalty, Trust & Reviews'),
(372,	'de_DE',	355,	'Marketing-Automatisierung'),
(372,	'en_GB',	355,	'Marketing Automation'),
(373,	'de_DE',	372,	'Affiliate-Marketing'),
(373,	'en_GB',	372,	'Affiliate Marketing'),
(374,	'de_DE',	372,	'CDP'),
(374,	'en_GB',	372,	'CDP'),
(375,	'de_DE',	372,	'CRM'),
(375,	'en_GB',	372,	'CRM'),
(376,	'de_DE',	372,	'E-Mail- & Newsletter-Marketing'),
(376,	'en_GB',	372,	'Email & Newsletter Marketing'),
(377,	'de_DE',	355,	'SEO'),
(377,	'en_GB',	355,	'SEO'),
(378,	'de_DE',	355,	'Zahlungen'),
(378,	'en_GB',	355,	'Payments'),
(379,	'de_DE',	378,	'PayPal'),
(379,	'en_GB',	378,	'PayPal'),
(380,	'de_DE',	378,	'Zahlungsdienstleister'),
(380,	'en_GB',	378,	'Payment Service Providers'),
(381,	'de_DE',	378,	'BNPL & lokale Zahlungsmethoden'),
(381,	'en_GB',	378,	'BNPL & Local Payment Methods'),
(382,	'de_DE',	378,	'Betrugsprävention & Forderungsmanagement'),
(382,	'en_GB',	378,	'Fraud Prevention & Debt Recovery'),
(383,	'de_DE',	355,	'Personalisierung'),
(383,	'en_GB',	355,	'Personalization'),
(384,	'de_DE',	355,	'PIM & DAM'),
(384,	'en_GB',	355,	'PIM & DAM'),
(385,	'de_DE',	355,	'Berichte & Analysen'),
(385,	'en_GB',	355,	'Reporting & Analytics'),
(386,	'de_DE',	385,	'Tracking'),
(386,	'en_GB',	385,	'Tracking'),
(387,	'de_DE',	355,	'Vertriebskanäle'),
(387,	'en_GB',	355,	'Sales Channels'),
(388,	'de_DE',	387,	'Shopware Multichannel Connect'),
(388,	'en_GB',	387,	'Shopware Multichannel Connect'),
(389,	'de_DE',	387,	'Marktplatz-Anbindungen'),
(389,	'en_GB',	387,	'Marketplace Connectors'),
(390,	'de_DE',	387,	'Datenfeed-Management'),
(390,	'en_GB',	387,	'Data Feed Management'),
(391,	'de_DE',	387,	'Preissuchmaschinen'),
(391,	'en_GB',	387,	'Price Search Engine'),
(392,	'de_DE',	355,	'Suche & Empfehlungen'),
(392,	'en_GB',	355,	'Search & Recommendation'),
(393,	'de_DE',	392,	'Shopware Deep Search'),
(393,	'en_GB',	392,	'Shopware Deep Search'),
(394,	'de_DE',	392,	'Produktentdeckung'),
(394,	'en_GB',	392,	'Product Discovery'),
(395,	'de_DE',	355,	'Versand & Fulfillment'),
(395,	'en_GB',	355,	'Shipping & Fulfillment'),
(396,	'de_DE',	395,	'Carrier-Services'),
(396,	'en_GB',	395,	'Carrier Services'),
(397,	'de_DE',	395,	'Versanddienstleister'),
(397,	'en_GB',	395,	'Shipping Service Providers'),
(398,	'de_DE',	395,	'Versandservices'),
(398,	'en_GB',	395,	'Shipment Services'),
(399,	'de_DE',	395,	'Cross-border'),
(399,	'en_GB',	395,	'Cross-border'),
(400,	'de_DE',	355,	'Steuern'),
(400,	'en_GB',	355,	'Tax'),
(401,	'de_DE',	355,	'Weitere Partner-Integrationen'),
(401,	'en_GB',	355,	'Additional Partner Integrations'),
(402,	'de_DE',	355,	'Kapitaldarlehen'),
(402,	'en_GB',	355,	'Capital Loans');

DROP TABLE IF EXISTS `s_core_plugins`;
CREATE TABLE `s_core_plugins` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `namespace` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `label` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `source` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `translations` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `description_long` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `active` int unsigned NOT NULL,
  `added` datetime NOT NULL,
  `installation_date` datetime DEFAULT NULL,
  `update_date` datetime DEFAULT NULL,
  `refresh_date` datetime DEFAULT NULL,
  `author` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `copyright` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `license` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `version` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `support` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `changes` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `link` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `store_version` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `store_date` datetime DEFAULT NULL,
  `capability_update` int NOT NULL,
  `capability_install` int NOT NULL,
  `capability_enable` int NOT NULL,
  `update_source` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `update_version` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `capability_secure_uninstall` int NOT NULL DEFAULT '0',
  `in_safe_mode` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_plugins` (`id`, `namespace`, `name`, `label`, `source`, `description`, `translations`, `description_long`, `active`, `added`, `installation_date`, `update_date`, `refresh_date`, `author`, `copyright`, `license`, `version`, `support`, `changes`, `link`, `store_version`, `store_date`, `capability_update`, `capability_install`, `capability_enable`, `update_source`, `update_version`, `capability_secure_uninstall`, `in_safe_mode`) VALUES
(2,	'Core',	'ErrorHandler',	'ErrorHandler',	'Default',	'',	NULL,	'',	1,	'2012-08-28 00:00:00',	'2010-10-18 00:00:00',	'2010-10-18 00:00:00',	'2026-02-02 16:23:01',	'shopware AG',	'Copyright © 2012, shopware AG',	NULL,	'1',	NULL,	'',	NULL,	NULL,	NULL,	1,	0,	0,	NULL,	NULL,	0,	0),
(7,	'Core',	'Cron',	'Cron',	'Default',	'',	NULL,	'',	0,	'2012-08-28 00:00:00',	NULL,	NULL,	'2026-02-02 16:23:01',	'shopware AG',	'Copyright © 2012, shopware AG',	NULL,	'1',	NULL,	'',	NULL,	NULL,	NULL,	1,	1,	1,	NULL,	NULL,	0,	0),
(8,	'Core',	'Router',	'Router',	'Default',	'',	NULL,	'',	1,	'2012-08-28 00:00:00',	'2010-10-18 00:00:00',	'2010-10-18 00:00:00',	'2026-02-02 16:23:01',	'shopware AG',	'Copyright © 2012, shopware AG',	NULL,	'1',	NULL,	'',	NULL,	NULL,	NULL,	1,	0,	0,	NULL,	NULL,	0,	0),
(9,	'Core',	'CronBirthday',	'CronBirthday',	'Default',	'',	NULL,	'',	0,	'2012-08-28 00:00:00',	NULL,	NULL,	'2026-02-02 16:23:01',	'shopware AG',	'Copyright © 2012, shopware AG',	NULL,	'1',	NULL,	'',	NULL,	NULL,	NULL,	1,	1,	1,	NULL,	NULL,	0,	0),
(10,	'Core',	'System',	'System',	'Default',	'',	NULL,	'',	1,	'2012-08-28 00:00:00',	'2010-10-18 00:00:00',	'2010-10-18 00:00:00',	'2026-02-02 16:23:01',	'shopware AG',	'Copyright © 2012, shopware AG',	NULL,	'1',	NULL,	'',	NULL,	NULL,	NULL,	1,	0,	0,	NULL,	NULL,	0,	0),
(11,	'Core',	'ViewportForward',	'ViewportForward',	'Default',	'',	NULL,	'',	1,	'2012-08-28 00:00:00',	'2010-10-18 00:00:00',	'2010-10-18 00:00:00',	'2026-02-02 16:23:01',	'shopware AG',	'Copyright © 2012, shopware AG',	NULL,	'1',	NULL,	'',	NULL,	NULL,	NULL,	1,	0,	0,	NULL,	NULL,	0,	0),
(12,	'Core',	'Shop',	'Shop',	'Default',	'',	NULL,	'',	1,	'2012-08-28 00:00:00',	'2010-10-18 00:00:00',	'2010-10-18 00:00:00',	NULL,	'shopware AG',	'Copyright © 2010, shopware AG',	'',	'1',	'http://www.shopware.de/wiki/',	'',	'http://www.shopware.de/',	NULL,	NULL,	0,	0,	0,	NULL,	NULL,	0,	0),
(13,	'Core',	'PostFilter',	'PostFilter',	'Default',	'',	NULL,	'',	1,	'2012-08-28 00:00:00',	'2010-10-18 00:00:00',	'2010-10-18 00:00:00',	'2026-02-02 16:23:01',	'shopware AG',	'Copyright © 2012, shopware AG',	NULL,	'1',	NULL,	'',	NULL,	NULL,	NULL,	1,	0,	0,	NULL,	NULL,	0,	0),
(14,	'Core',	'CronRating',	'CronRating',	'Default',	'',	NULL,	'',	0,	'2012-08-28 00:00:00',	NULL,	NULL,	'2026-02-02 16:23:01',	'shopware AG',	'Copyright © 2012, shopware AG',	NULL,	'1',	NULL,	'',	NULL,	NULL,	NULL,	1,	1,	1,	NULL,	NULL,	0,	0),
(15,	'Core',	'ControllerBase',	'ControllerBase',	'Default',	'',	NULL,	'',	1,	'2012-08-28 00:00:00',	'2010-10-18 00:00:00',	'2010-10-18 00:00:00',	'2026-02-02 16:23:01',	'shopware AG',	'Copyright © 2012, shopware AG',	NULL,	'1',	NULL,	'',	NULL,	NULL,	NULL,	1,	0,	0,	NULL,	NULL,	0,	0),
(16,	'Core',	'CronStock',	'CronStock',	'Default',	'',	NULL,	'',	0,	'2012-08-28 00:00:00',	NULL,	NULL,	'2026-02-02 16:23:01',	'shopware AG',	'Copyright © 2012, shopware AG',	NULL,	'1',	NULL,	'',	NULL,	NULL,	NULL,	1,	1,	1,	NULL,	NULL,	0,	0),
(19,	'Frontend',	'RouterRewrite',	'RouterRewrite',	'Default',	'',	NULL,	'',	1,	'2012-08-28 00:00:00',	'2010-10-18 00:00:00',	'2010-10-18 00:00:00',	'2026-02-02 16:23:01',	'shopware AG',	'Copyright © 2012, shopware AG',	NULL,	'1',	NULL,	'',	NULL,	NULL,	NULL,	0,	0,	0,	NULL,	NULL,	0,	0),
(22,	'Frontend',	'Seo',	'Seo',	'Default',	'',	NULL,	'',	1,	'2012-08-28 00:00:00',	'2010-10-18 00:00:00',	'2010-10-18 00:00:00',	'2026-02-02 16:23:01',	'shopware AG',	'Copyright © 2012, shopware AG',	NULL,	'1',	NULL,	'',	NULL,	NULL,	NULL,	1,	0,	0,	NULL,	NULL,	0,	0),
(29,	'Frontend',	'AdvancedMenu',	'Erweitertes Menü',	'Default',	'',	NULL,	'',	0,	'2012-08-28 00:00:00',	NULL,	NULL,	'2026-02-02 16:23:01',	'shopware AG',	'Copyright © 2012, shopware AG',	NULL,	'1',	NULL,	'',	NULL,	NULL,	NULL,	1,	1,	1,	NULL,	NULL,	0,	0),
(31,	'Frontend',	'Statistics',	'Statistics',	'Default',	'',	NULL,	'',	1,	'2012-08-28 00:00:00',	'2010-10-18 00:00:00',	'2010-10-18 00:00:00',	'2026-02-02 16:23:01',	'shopware AG',	'Copyright © 2012, shopware AG',	NULL,	'1',	NULL,	'',	NULL,	NULL,	NULL,	1,	1,	1,	NULL,	NULL,	0,	0),
(33,	'Frontend',	'Notification',	'Notification',	'Default',	'',	NULL,	'',	0,	'2012-08-28 00:00:00',	NULL,	NULL,	'2026-02-02 16:23:01',	'shopware AG',	'Copyright © 2012, shopware AG',	NULL,	'1',	NULL,	'',	NULL,	NULL,	NULL,	1,	1,	1,	NULL,	NULL,	0,	0),
(34,	'Frontend',	'TagCloud',	'TagCloud',	'Default',	'',	NULL,	'',	0,	'2012-08-28 00:00:00',	NULL,	NULL,	'2026-02-02 16:23:01',	'shopware AG',	'Copyright © 2012, shopware AG',	NULL,	'1',	NULL,	'',	NULL,	NULL,	NULL,	1,	1,	1,	NULL,	NULL,	0,	0),
(35,	'Frontend',	'InputFilter',	'InputFilter',	'Default',	'',	NULL,	'',	1,	'2012-08-28 00:00:00',	'2010-10-18 00:00:00',	'2010-10-18 00:00:00',	'2026-02-02 16:23:01',	'shopware AG',	'Copyright © 2012, shopware AG',	NULL,	'1',	NULL,	'',	NULL,	NULL,	NULL,	1,	0,	1,	NULL,	NULL,	0,	0),
(36,	'Backend',	'Auth',	'Auth',	'Default',	'',	NULL,	'',	1,	'2012-08-28 00:00:00',	'2010-10-18 00:00:00',	'2010-10-18 00:00:00',	'2026-02-02 16:23:01',	'shopware AG',	'Copyright © 2012, shopware AG',	NULL,	'1',	NULL,	'',	NULL,	NULL,	NULL,	1,	0,	0,	NULL,	NULL,	0,	0),
(37,	'Backend',	'Menu',	'Menu',	'Default',	'',	NULL,	'',	1,	'2012-08-28 00:00:00',	'2010-10-18 00:00:00',	'2010-10-18 00:00:00',	NULL,	'shopware AG',	'Copyright © 2010, shopware AG',	'',	'1',	'http://www.shopware.de/wiki/',	'',	'http://www.shopware.de/',	NULL,	NULL,	0,	0,	0,	NULL,	NULL,	0,	0),
(40,	'Backend',	'Check',	'Systeminfo',	'Default',	'',	NULL,	'',	1,	'2010-10-18 00:00:00',	'2010-10-18 00:00:00',	'2010-10-18 00:00:00',	NULL,	'shopware AG',	'Copyright © 2011, shopware AG',	'',	'1.0.0',	'http://wiki.shopware.de',	'',	'http://www.shopware.de/',	NULL,	NULL,	0,	0,	0,	NULL,	NULL,	0,	0),
(43,	'Backend',	'Locale',	'Locale',	'Default',	'',	NULL,	'',	1,	'2012-08-27 22:28:53',	'2012-08-27 22:28:53',	'2012-08-27 22:28:53',	NULL,	'shopware AG',	'Copyright &copy; 2011, shopware AG',	'',	'1.0.0',	'http://wiki.shopware.de',	'',	'http://www.shopware.de/',	NULL,	NULL,	0,	0,	0,	NULL,	NULL,	0,	0),
(44,	'Core',	'RestApi',	'RestApi',	'Default',	'',	NULL,	'',	1,	'2012-07-13 12:03:13',	'2012-07-13 12:03:36',	'2012-07-13 12:03:36',	'2026-02-02 16:23:01',	'shopware AG',	'Copyright © 2012, shopware AG',	NULL,	'1.0.0',	NULL,	'',	NULL,	NULL,	NULL,	1,	0,	0,	NULL,	NULL,	0,	0),
(49,	'Core',	'PasswordEncoder',	'PasswordEncoder',	'Default',	NULL,	NULL,	NULL,	1,	'2013-04-16 12:13:54',	'2013-04-16 14:07:23',	'2013-04-16 14:07:23',	'2026-02-02 16:23:01',	'shopware AG',	'Copyright © 2012, shopware AG',	NULL,	'1.0.0',	NULL,	NULL,	NULL,	NULL,	NULL,	1,	0,	0,	NULL,	NULL,	0,	0),
(50,	'Core',	'MarketingAggregate',	'Shopware Marketing Aggregat Funktionen',	'Default',	NULL,	NULL,	NULL,	1,	'2013-04-30 14:19:13',	'2013-04-30 14:26:48',	'2013-04-30 14:26:48',	'2026-02-02 16:23:01',	'shopware AG',	'Copyright © 2012, shopware AG',	NULL,	'1.0.0',	NULL,	NULL,	'http://www.shopware.de/',	NULL,	NULL,	1,	0,	0,	NULL,	NULL,	0,	0),
(51,	'Core',	'RebuildIndex',	'Shopware Such- und SEO-Index',	'Default',	NULL,	NULL,	NULL,	1,	'2013-05-19 10:53:24',	'2013-05-21 13:28:04',	'2013-05-21 13:28:04',	'2026-02-02 16:23:01',	'shopware AG',	'Copyright © 2012, shopware AG',	NULL,	'1.0.0',	NULL,	NULL,	'http://www.shopware.de/',	NULL,	NULL,	1,	0,	0,	NULL,	NULL,	0,	0),
(52,	'Core',	'HttpCache',	'Frontendcache (HttpCache)',	'Default',	NULL,	NULL,	NULL,	0,	'2013-05-27 15:57:59',	'2013-05-27 15:58:09',	'2013-05-27 15:58:09',	'2026-02-02 16:23:01',	'shopware AG',	'Copyright © 2012, shopware AG',	NULL,	'1.1.0',	NULL,	NULL,	NULL,	NULL,	NULL,	1,	0,	0,	NULL,	NULL,	0,	0),
(53,	'Core',	'PaymentMethods',	'Payment Methods',	'Default',	'Shopware Payment Methods handling. This plugin is required to handle payment methods, and should not be deactivated',	NULL,	NULL,	1,	'2013-10-30 08:12:22',	'2013-10-30 08:13:26',	'2013-10-30 08:13:26',	'2026-02-02 16:23:01',	'shopware AG',	'Copyright © 2012, shopware AG',	NULL,	'1.0.1',	NULL,	NULL,	NULL,	NULL,	NULL,	0,	0,	0,	NULL,	NULL,	0,	0),
(55,	'Backend',	'SwagUpdate',	'Shopware Auto Update',	'Default',	NULL,	NULL,	NULL,	1,	'2014-05-06 09:03:01',	'2014-05-06 09:03:06',	'2014-05-06 09:03:06',	'2026-02-02 16:23:01',	'shopware AG',	'Copyright © 2012, shopware AG',	NULL,	'1.0.0',	NULL,	NULL,	NULL,	NULL,	NULL,	1,	1,	1,	NULL,	NULL,	0,	0),
(56,	'Backend',	'PluginManager',	'Plugin Manager',	'Default',	NULL,	NULL,	NULL,	1,	'2014-11-07 11:55:46',	'2014-11-07 11:55:54',	'2014-11-07 11:55:54',	'2014-11-07 11:55:57',	'shopware AG',	'Copyright © 2012, shopware AG',	NULL,	'1.0.0',	NULL,	NULL,	NULL,	NULL,	NULL,	1,	1,	1,	NULL,	NULL,	0,	0),
(57,	'Core',	'CronProductExport',	'CronProductExport',	'Default',	NULL,	NULL,	NULL,	0,	'2026-02-02 10:22:14',	NULL,	NULL,	'2026-02-02 16:23:01',	'shopware AG',	'Copyright © 2012, shopware AG',	NULL,	'1.0.0',	NULL,	NULL,	NULL,	NULL,	NULL,	1,	1,	1,	NULL,	NULL,	0,	0),
(58,	'Frontend',	'CronRefresh',	'CronRefresh',	'Default',	NULL,	NULL,	NULL,	0,	'2026-02-02 10:22:14',	NULL,	NULL,	'2026-02-02 16:23:01',	'shopware AG',	'Copyright © 2012, shopware AG',	NULL,	'1.0.0',	NULL,	NULL,	NULL,	NULL,	NULL,	1,	1,	1,	NULL,	NULL,	0,	0),
(59,	'Frontend',	'SwagDemoDataDE',	'Shopware 5 Demo Data DE',	'Community',	NULL,	NULL,	NULL,	1,	'2026-02-02 10:22:14',	'2026-02-02 10:22:21',	'2026-02-02 10:22:21',	'2026-02-02 16:23:01',	'shopware AG',	'Copyright © 2012, shopware AG',	NULL,	'5.6.0',	NULL,	NULL,	NULL,	NULL,	NULL,	1,	1,	1,	NULL,	NULL,	0,	0),
(60,	'ShopwarePlugins',	'SwagPaymentPayPalUnified',	'PayPal',	'',	'This module offers you the most popular PayPal products from a single source: PayPal Checkout, PayPal Express button and banners for the new PayPal Installment Payment. And best of all, this module lets you use all products individually or simultaneously in your online store.',	'{\"de\":{\"label\":\"PayPal\",\"description\":\"Dieses Modul bietet Ihnen die beliebtesten PayPal-Produkte aus einer Hand: PayPal Checkout, PayPal Express-Button und Banner f\\u00fcr die neue PayPal Ratenzahlung. Und das Beste daran: Mit diesem Modul k\\u00f6nnen Sie alle Produkte einzeln oder gleichzeitig in Ihrem Online-Shop nutzen.\"},\"en\":{\"label\":\"PayPal\",\"description\":\"This module offers you the most popular PayPal products from a single source: PayPal Checkout, PayPal Express button and banners for the new PayPal Installment Payment. And best of all, this module lets you use all products individually or simultaneously in your online store.\"}}',	NULL,	0,	'2026-02-02 10:22:28',	NULL,	NULL,	'2026-02-02 16:23:01',	'shopware AG',	NULL,	NULL,	'6.1.10',	NULL,	'{\"6.1.10\":{\"de\":[\"\\n            PT-13158 - Fehler w\\u00e4hrend des Bestellabschlusses in iOS Web-Views behoben;\\n        \"],\"en\":[\"\\n            PT-13158 - Fix error during order checkout in iOS web views;\\n        \"]},\"6.1.9\":{\"de\":[\"\\n            PT-13158 - Fehler beim Laden des PayPal-Buttons f\\u00fcr den Bestellabschluss in Web-Views behoben;\\n        \"],\"en\":[\"\\n            PT-13158 - Fix error while loading the checkout PayPal button in web views;\\n        \"]},\"6.1.8\":{\"de\":[\"\\n            PT-13157 - Die Zahlungsart \\\"Giropay\\\" wird deaktiviert kann nicht mehr verwendet werden;\\n        \"],\"en\":[\"\\n            PT-13157 - The \\u201cGiropay\\u201d payment method is deactivated and can no longer be used;\\n        \"]},\"6.1.7\":{\"de\":[\"\\n            PT-13154 - Die Zahlungsart \\\"Sofort\\\" wird deaktiviert kann nicht mehr verwendet werden;\\n        \"],\"en\":[\"\\n            PT-13154 - The \\u201cSofort\\u201d payment method is deactivated and can no longer be used;\\n        \"]},\"6.1.6\":{\"de\":[\"\\n            PT-13151 - Verbessert den Bestellabschluss f\\u00fcr alternative Zahlungsarten (APMs) in Verbindung mit Session-Timeouts;\\n        \"],\"en\":[\"\\n            PT-13151 - Improves order completion for alternative payment methods (APMs) in combination with session timeouts;\\n        \"]},\"6.1.5\":{\"de\":[\"\\n            PT-13146 - Behebt ein Problem mit der Anzeige der PayPal Buttons im Safari Browser;\\n        \"],\"en\":[\"\\n            PT-13146 - Fixes a problem with displaying the PayPal buttons in Safari browser;\\n        \"]},\"6.1.4\":{\"de\":[\"\\n            PT-13146 - Die Zahlungsart \\\"Sofort\\\" ist nun standardm\\u00e4\\u00dfig deaktiviert;\\n        \"],\"en\":[\"\\n            PT-13146 - The payment method \\\"Sofort\\\" is now deactivated by default;\\n        \"]},\"6.1.3\":{\"de\":[\"\\n            PT-13146 - Behebt ein Problem bei der \\u00dcberpr\\u00fcfung der Transaktionsf\\u00e4higkeit ohne PPCP;\\n        \"],\"en\":[\"\\n            PT-13146 - Fixes a problem when checking transaction eligibility without PPCP;\\n        \"]},\"6.1.2\":{\"de\":[\"\\n            PT-13142 - Verbessert die Bearbeitung von Webhooks;\\n            PT-13143 - Behebt ein Problem mit der Kreditkarten Zahlung;\\n        \"],\"en\":[\"\\n            PT-13142 - Improves the processing of webhooks;\\n            PT-13143 - Fixes a problem with credit card payment;\\n        \"]},\"6.1.1\":{\"de\":[\"\\n            PT-13127 - Verbessert die Benutzerfreundlichkeit f\\u00fcr den Rechnungskauf im Checkout-Formular;\\n            PT-13134 - Verhindert das Erstellen einer PayPal Zahlung mit einem leeren Warenkorb;\\n            PT-13138 - Verbessert die Bearbeitung von Webhooks;\\n            PT-13140 - Die Zahlungsart \\\"MyBank\\\" ist nun standardm\\u00e4\\u00dfig deaktiviert;\\n        \"],\"en\":[\"\\n            PT-13127 - Improves the usability for pay upon invoice in the checkout form;\\n            PT-13134 - Prevents the creation of a PayPal payment with an empty shopping cart;\\n            PT-13138 - Improves the processing of webhooks;\\n            PT-13140 - The payment method \\\"MyBank\\\" is now deactivated by default;\\n        \"]},\"6.1.0\":{\"de\":[\"\\n            PT-13108 - Nutzt Standard-Landingpage, wenn die Einstellung nicht korrekt gesetzt wurde;\\n            PT-13109 - Behebt Darstellungsfehler im Listing im Zusammenhang mit dem PayLater Button;\\n            PT-13115 - Kreditkartenzahlen ohne 3DS-Authentifizierung k\\u00f6nnen nun erlaubt werden;\\n        \"],\"en\":[\"\\n            PT-13108 - Uses default landing page if setting is not set correctly;\\n            PT-13109 - Fixes display error in the listing in connection with the PayLater button;\\n            PT-13115 - Credit card payments without 3DS authentication can now be allowed;\\n        \"]},\"6.0.8\":{\"de\":[\"\\n            PT-13099 - Bei wiederkehrendem Express-Checkout ist die PayPal Zahlungsart nun korrekt ausgew\\u00e4hlt;\\n        \"],\"en\":[\"\\n            PT-13099 - For recurring express checkout, the PayPal payment method is now correctly selected;\\n        \"]},\"6.0.7\":{\"de\":[\"\\n            PT-13098 - Gutscheinfeld wird im Warenkorb wieder korrekt angezeigt;\\n        \"],\"en\":[\"\\n            PT-13098 - Voucher field is displayed correctly again in the shopping cart;\\n        \"]},\"6.0.6\":{\"de\":[\"\\n            PT-13097 - Gutscheinfeld wird wieder korrekt angezeigt;\\n        \"],\"en\":[\"\\n            PT-13097 - Voucher field is displayed correctly again;\\n        \"]},\"6.0.5\":{\"de\":[\"\\n            PT-13073 - Verbessert das Erstellen der API Daten;\\n            PT-13074 - Verbessert den Express-Checkout wenn der Kunde einen Gutschein in den Warenkorb legt;\\n            PT-13079 - Verbessert die Status-\\u00dcberpr\\u00fcfung von ausstehenden PayPal Zahlungen;\\n            PT-13080 - Wenn die Lieferadresse w\\u00e4hrend eines \\\"PayPal Express\\\" Kaufes ge\\u00e4ndert wird, wird diese an PayPal \\u00fcbermittelt;\\n        \"],\"en\":[\"\\n            PT-13073 - Improves the creation of API data;\\n            PT-13074 - Improves the express checkout when the customer adds a voucher to the shopping cart;\\n            PT-13079 - Improves the status check of pending PayPal payments;\\n            PT-13080 - If the shipping address is changed during a \\\"PayPal Express\\\" purchase, it will be transmitted to PayPal;\\n        \"]},\"6.0.4\":{\"de\":[\"\\n            PT-13029 - \\u00dcbertragung der PayPal-Einstellungen via API verbessert;\\n            PT-13030 - Verbessert die Benutzerfreundlichkeit f\\u00fcr Express Bestellungen mit falsch separierten Namen;\\n            PT-13038 - F\\u00fcgt eine Beschreibung der Bestellnummer\\/Rechnungsnummer zu PayPal-Bestellungen hinzu;\\n            PT-13044 - Verbessert die \\u00dcbertragung von Fehlermeldungen;\\n            PT-13053 - Ber\\u00fccksichtigt Radio-Optionen im Checkout-Formular;\\n            PT-13057 - Falls ein Sprachshop keine eigenen Einstellungen hat, werden die Einstellungen des Hauptshops ber\\u00fccksichtigt;\\n            PT-13060 - Deaktiviert vor\\u00fcbergehend die Zahlungsmethode \\\"Trustly\\\", da sie derzeit bei PayPal nicht verf\\u00fcgbar ist;\\n            PT-13062 - Standardgr\\u00f6\\u00dfe der PayPal Buttons ge\\u00e4ndert und Gr\\u00f6\\u00dfe angepasst;\\n            PT-13063 - Verbessert den Kaufen-Button-Text f\\u00fcr Express-Bestellungen;\\n            PT-13066 - Individuelle Eingabefelder werden nun bei der Bestellbest\\u00e4tigung mit \\u00fcbertragen;\\n            PT-13069 - Bestellnummer wird wiederhergestellt, wenn der Kunde die Zahlung abbricht;\\n            PT-13075 - \\u00dcbersetzungen f\\u00fcr \\\"PayPal, Sp\\u00e4ter bezahlen\\\" hinzugef\\u00fcgt;\\n        \"],\"en\":[\"\\n            PT-13029 - Improved transfer of PayPal settings via API;\\n            PT-13030 - Improves usability for Express Orders with incorrectly separated names;\\n            PT-13038 - Adds a description of the order number\\/invoice number to PayPal orders;\\n            PT-13044 - Improves the transmission of error messages;\\n            PT-13053 - Considers radio buttons in checkout form;\\n            PT-13057 - If a language shop does not have its own settings, the settings of the main shop are taken into account;\\n            PT-13060 - Temporarily disables the \\\"Trustly\\\" payment method, as it is currently not available at PayPal;\\n            PT-13062 - Default size of PayPal buttons changed and resized;\\n            PT-13063 - Improves the buy button text for Express Orders;\\n            PT-13066 - Custom input fields are now transferred with the order confirmation;\\n            PT-13069 - Order number is restored when the customer cancels the payment;\\n            PT-13075 - Translations for \\\"PayPal, Pay Later\\\" added;\\n        \"]},\"6.0.3\":{\"de\":[\"\\n            PT-13059 - F\\u00fcgt eine Request-ID f\\u00fcr Rechnungskauf und alternative Zahlungsarten (APMs) hinzu, um doppelte Zahlungen zu vermeiden;\\n        \"],\"en\":[\"\\n            PT-13059 - Adds a request ID for pay upon invoice and alternative payment methods (APMs) to avoid duplicate payments;\\n        \"]},\"6.0.2\":{\"de\":[\"\\n            PT-10637 - Setzt richtigen Zahlungsstatus nach Teilr\\u00fcckzahlung;\\n            PT-12473 - Wiederholende Bestellungen mit Express Checkout erstellen keine doppelten Gastkonten mehr;\\n            PT-12673 - Verbessert den Zahlungsabschluss f\\u00fcr alternative Zahlungsarten (APMs);\\n            PT-12937 - Fehlende Banner f\\u00fcr \\\"Sp\\u00e4ter Zahlen\\\" hinzugef\\u00fcgt;\\n            PT-12937 - Sidebar wird nur angezeigt, wenn das Banner verf\\u00fcgbar ist;\\n            PT-12723 - Verbessert die Benutzerfreundlichkeit der Kreditkartenfelder;\\n            PT-12761 - Verbessert die Benutzerfreundlichkeit beim Wechsel der Zahlungsart;\\n            PT-12833 - Verhindert das Anzeigen mehrerer gleicher Nachrichten im Zahlartenmodul;\\n            PT-12942 - F\\u00fcgt eine Warnung f\\u00fcr erneute Autorisierung hinzu;\\n            PT-12987 - Verbessert den Verf\\u00fcgbarkeitstest von Rechnungskauf und Kreditkarten Zahlung in den Einstellungen;\\n            PT-13008 - Behebt API Fehler f\\u00fcr alternative Zahlungsarten (APMs);\\n            PT-13012 - \\u00c4ndert den englischen Namen f\\u00fcr \\\"PayPal, Pay Later\\\" im Frontend zu \\\"PayPal, Pay in 3\\\";\\n            PT-13022 - Webhooks f\\u00fcr abgeschlossene und abgelehnte PayPal-Bestellungen hinzugef\\u00fcgt;\\n            PT-13028 - Das Bestellnummern-Prefix wird wieder mit an PayPal \\u00fcbertragen;\\n            PT-13031 - Verbessert das Update auf die Version 6.0.0;\\n            PT-13032 - F\\u00fcgt PayPalExpress nur hinzu, wenn es erforderlich ist, damit kein Javascript Fehler auftritt;\\n        \"],\"en\":[\"\\n            PT-10637 - Sets correct payment status after partial refund;\\n            PT-12473 - Recurring orders with Express Checkout no longer create duplicate guest accounts;\\n            PT-12673 - Improves payment completion for alternative payment methods (APMs);\\n            PT-12937 - Missing banner for \\\"Pay later\\\" added;\\n            PT-12937 - Sidebar is displayed only when the banner is available;\\n            PT-12723 - Improves the usability of the credit card fields;\\n            PT-12761 - Improves the usability when changing the payment method;\\n            PT-12833 - Prevents displaying multiple identical messages in the payment type module;\\n            PT-12942 - Adds a warning for reauthorization;\\n            PT-12987 - Improves the availability test of pay upon invoice and credit card payment in the settings;\\n            PT-13008 - Fixes API error for alternative payment methods (APMs);\\n            PT-13012 - Changed the English name for \\\"PayPal, Pay Later\\\" in the frontend to \\\"PayPal, Pay in 3\\\";\\n            PT-13022 - Added Webhooks for completed and denied PayPal Orders;\\n            PT-13028 - The order number prefix will be transferred to PayPal again;\\n            PT-13031 - Improves the update to version 6.0.0;\\n            PT-13032 - Adds PayPalExpress only if it is required so there is no Javascript error;\\n        \"]},\"6.0.1\":{\"de\":[\"\\n            PT-137010 - Verbessert die Tracking-API;\\n        \"],\"en\":[\"\\n            PT-137010 - Improves the Tracking-API;\\n        \"]},\"6.0.0\":{\"de\":[\"\\n            PT-12545 - Vereinheitlicht das Verhalten der Buttons bei Style \\u00c4nderungen;\\n            PT-12601 - Verbessert Geburtsdatum und Telefonnummer \\u00dcberpr\\u00fcfung f\\u00fcr Rechnungskauf;\\n            PT-12878 - Zahlungsinformationen f\\u00fcr Rechnungskauf werden nun auf der Finish-Seite angezeigt;\\n            PT-12714 - Entfernt zweiten Titel aus Einstellungs-Tab;\\n            PT-12757 - Externe Abh\\u00e4ngigkeiten k\\u00f6nnen au\\u00dferhalb des Plugins bereitgestellt werden;\\n            PT-12775 - Entfernt die OXXO Zahlungsart;\\n            PT-12982 - Shopware-Bestellnummer wird direkt beim Erstellen der PayPal-Zahlung mitgesendet, dadurch reduziert sich die Anzahl der PayPal-API-Aufrufe;\\n            PT-12725 - PayPal tracking API hinzugef\\u00fcgt;\\n            PT-12648 - PayPal Express Button Sprachfeld entfernt;\\n            PT-12892 - F\\u00fcgt einen Link f\\u00fcr weitere Informationen zu Rechnungskauf hinzu;\\n            PT-12926 - Verbessert Verhalten bei invalider Adresse;\\n            PT-12964 - Entfernt die Weiterleitungsoption zu PayPal zugunsten des bevorzugten In-Context \\/ Mini-Browsers (optimiert f\\u00fcr conversion);\\n            PT-12945 - PayPal Express verwendet \\\"nicht definiert\\\" als Anrede, wenn vorhanden;\\n            PT-12991 - Smart Payment Button Symbole werden immer gleich angezeigt;\\n            PT-12992 - Verbessert die Betrugs\\u00fcberpr\\u00fcfung f\\u00fcr Rechnungskauf;\\n            PT-12994 - \\\"PayPal, Sp\\u00e4ter Bezahlen\\\" ist jetzt am PayPal und Express Check-out verf\\u00fcgbar;\\n            PT-13008 - Verbessert den Zahlungsabschluss f\\u00fcr alternative Zahlungsarten (APM\'s);\\n        \"],\"en\":[\"\\n            PT-12545 - Unifies the behavior of buttons on style changes;\\n            PT-12601 - Improved date of birth and phone number validation for pay upon invoice;\\n            PT-12878 - Payment information for pay upon invoice is now loaded on the finish page;\\n            PT-12714 - Removes second title from settings tab;\\n            PT-12757 - External dependencies can be provided outside the plugin;\\n            PT-12775 - Removes the OXXO payment method;\\n            PT-12982 - Shopware order number is sent directly when creating the PayPal payment, reducing the number of PayPal API calls;\\n            PT-12725 - Added PayPal tracking API;\\n            PT-12648 - PayPal Express button language field removed;\\n            PT-12892 - Add link for more information about pay upon invoice;\\n            PT-12926 - Improves behavior with invalid address;\\n            PT-12964 - Removes redirect to PayPal option in favor of the preferred in-context \\/ mini-browser flow (optimized for conversion);\\n            PT-12945 - PayPal Express used \\\"not defined\\\" as salutation if it is available;\\n            PT-12991 - Smart Payment Button symbols are always displaying the same;\\n            PT-12992 - Improves fraud checking for pay upon invoice;\\n            PT-12994 - \\\"PayPal, PayLater\\\" is now available at PayPal and Express Check-out;\\n            PT-13008 - Improves payment completion for alternative payment methods (APM\'s);\\n        \"]},\"5.0.4\":{\"de\":[\"\\n            PT-12725 - Verbessert das Risikomanagement bei Express Checkout und ausgeschlossenen Produkten;\\n            PT-12859 - Kunden k\\u00f6nnen beim Express Checkout wieder die Adresse wechseln;\\n            PT-12880 - \\u00d6ffnen von Bestellungen mit gel\\u00f6schter Zahlungsart wieder m\\u00f6glich;\\n            PT-12909 - Verbessert das Verhalten der Pflichtfelder in den Einstellungen;\\n            PT-12931 - F\\u00fcgt das Zahlungsziel zum Rechnungstemplate hinzu;\\n            PT-12936 - Verbessert die \\u00dcberpr\\u00fcfung der Kundentelefonnummer beim Rechnungskauf;\\n            PT-12953 - Verbessert die 3D-Secure \\u00dcberpr\\u00fcfung bei Kreditkartenzahlungen;\\n            PT-12975 - Verbessert Verhalten bei doppelten Bestellnummern;\\n        \"],\"en\":[\"\\n            PT-12725 - Improves risk management for Express Checkout and excluded products;\\n            PT-12859 - Customers can change the address again during Express Checkout;\\n            PT-12880 - Opening orders with deleted payment method is possible again;\\n            PT-12909 - Improves the behavior of mandatory fields in the settings;\\n            PT-12931 - Adds the payment term to the invoice template;\\n            PT-12936 - Improves the verification of the customer phone number for pay upon invoice;\\n            PT-12953 - Improves 3D-Secure verification for credit card payments;\\n            PT-12975 - Improves behavior in case of duplicate order numbers;\\n        \"]},\"5.0.3\":{\"de\":[\"\\n            PT-12922 - 404-Fehler beim Zahlungsvorgang behoben;\\n            PT-12922 - F\\u00fcgt eine Benachrichtigung f\\u00fcr den Kunden hinzu, wenn die bei PayPal ausgew\\u00e4hlte Zahlungsart abgelehnt wird;\\n        \"],\"en\":[\"\\n            PT-12922 - Fix 404 error during payment process;\\n            PT-12922 - Adds a notification for the customer when the payment method selected at PayPal is rejected;\\n        \"]},\"5.0.2\":{\"de\":[\"\\n            PT-12947 - Korrigiert doppelte Nutzung von Bestellnummern, wenn Nicht-PayPal-Zahlungsarten genutzt werden;\\n        \"],\"en\":[\"\\n            PT-12947 - Fixes duplicate use of order numbers if non-PayPal payment methods are used;\\n        \"]},\"5.0.1\":{\"de\":[\"\\n            PT-12946 - Behebt Probleme beim Erstellen der Bestellnummer falls keine Storefront-Session verf\\u00fcgbar ist;\\n        \"],\"en\":[\"\\n            PT-12946 - Fixes problem while creating the order number if no storefront session is available;\\n        \"]},\"5.0.0\":{\"de\":[\"\\n            PT-12890 - Senden der Bestellnummern zu PayPal verbessert, um doppelte Bestellungen zu verhindern;\\n            PT-12893 - Schnellbesteller werden, wenn erforderlich, zur\\u00fcck zu PayPal geleitet;\\n            PT-12903 - API-URL aktualisiert;\\n            PT-12935 - Entfernt Italien als akzeptiertes Land f\\u00fcr die Zahlungsmethode Sofort;\\n        \"],\"en\":[\"\\n            PT-12890 - Improved sending of order numbers to PayPal to prevent duplicate orders;\\n            PT-12893 - Express Orderers will be redirected back to PayPal if necessary;\\n            PT-12903 - Update API-URL;\\n            PT-12935 - Italy is no longer accepted country for the Sofort payment method;\\n        \"]},\"4.3.3\":{\"de\":[\"\\n            PT-12853 - Stabilit\\u00e4t von Bestellungen mit Express Checkout verbessert;\\n            PT-12872 - Informationen zur \\u00dcberweisung f\\u00fcr den Rechnungskauf werden nur angezeigt, wenn sie auch verf\\u00fcgbar sind;\\n            PT-12874 - Bestellkommentare von Kunden werden nun korrekt gespeichert;\\n            PT-12885 - Risk-Management von Rechnungskauf verbessert;\\n        \"],\"en\":[\"\\n            PT-12853 - Improve stability of orders with Express Checkout;\\n            PT-12872 - Bank transfer information for pay upon invoice is only displayed if it is available;\\n            PT-12874 - Customer order comments are now saved correctly;\\n            PT-12885 - Improves risk management of pay upon invoice;\\n        \"]},\"4.3.2\":{\"de\":[\"\\n            PT-12852 - Stabilit\\u00e4t des Storefront-Javascripts verbessert;\\n            PT-12870 - Verbessert das Handling von APIv1 Zahlungen im PayPal Bestellmodul;\\n            PT-12871 - Behebt Problem, wenn die AGB-Checkbox deaktiviert ist;\\n        \"],\"en\":[\"\\n            PT-12800 - Improved stability of storefront javascript;\\n            PT-12870 - Improves the handling of APIv1 payments in the PayPal order module;\\n            PT-12871 - Fixes problem when TOS checkbox is disabled;\\n        \"]},\"4.3.1\":{\"de\":[\"\\n            PT-12746 - Den alternativen Zahlungsarten, wie Sofort und GiroPay, stehen nun mehr Kundendaten zur Verf\\u00fcgung;\\n            PT-12877 - Korrigiert Speichern der Zahlungsreferenz beim Rechnungskauf;\\n        \"],\"en\":[\"\\n            PT-12746 - More customer data is now available to the alternative payment methods, such as Sofort and GiroPay;\\n            PT-12877 - Fixed saving of payment reference of pay upon invoice;\\n        \"]},\"4.3.0\":{\"de\":[\"\\n            PT-12747 - Informationen zur \\u00dcberweisung f\\u00fcr den Rechnungskauf in der Bestellbest\\u00e4tigung-Mail und im Rechnungsdokument hinzugef\\u00fcgt;\\n            PT-12750 - Verbessert das Risikomanagement f\\u00fcr den Rechnungskauf;\\n            PT-12760 - Zus\\u00e4tzlichen Bestellung-Best\\u00e4tigen-Schritt entfernt;\\n            PT-12794 - Neuer Button in den Bestellungsdetails hinzugef\\u00fcgt, um Tracking-ID bei PayPal einzutragen;\\n            PT-12827 - Eine Warnung informiert nun \\u00fcber das Ablaufen vom PayPal Plus Rechnungskauf;\\n            PT-12863 - Behebt Problem mit der Weiterleitung zu PayPal, wenn der In-Context-Modus nicht genutzt wird;\\n            PT-12866 - Entfernt nicht l\\u00e4nger ben\\u00f6tigten Hinweis in den Einstellungen zu Geburtstag und Telefonnummer beim Rechnungskauf;\\n        \"],\"en\":[\"\\n            PT-12747 - Added information about bank transfer for pay upon invoice in the order confirmation email and invoice document;\\n            PT-12750 - Improved the risk management for pay upon invoice;\\n            PT-12760 - Removed additional order confirm step;\\n            PT-12794 - Added new button in order details to enter tracking ID at PayPal;\\n            PT-12827 - A warning now informs about the expiry of PayPal Plus pay upon invoice;\\n            PT-12863 - Fixes problem with redirect to PayPal when in-context mode is not used;\\n            PT-12866 - Removes no longer needed notice in settings for birthday and phone number for pay upon invoice;\\n        \"]},\"4.2.3\":{\"de\":[\"\\n            PT-12800 - Verbessert die Berechnung des Warenkorbs und die \\u00dcbertragung zu PayPal;\\n            PT-12803 - Verbessert das Handling von Kreditkartenzahlungen;\\n            PT-12826 - Die PayPal-Zahlungsarten werden nicht l\\u00e4nger bei jedem Update des Plugins aktiviert;\\n        \"],\"en\":[\"\\n            PT-12800 - Improves the calculation of the cart and the transfer to PayPal;\\n            PT-12803 - Improves handling of credit cart payments;\\n            PT-12826 - PayPal payment methods are no longer activated with every update of the plugin;\\n        \"]},\"4.2.2\":{\"de\":[\"\\n            PT-12820 - Kompatibilit\\u00e4t mit Drittanbieter-Plugins verbessert;\\n            PT-12824 - Status-E-Mail-Versand ist nun abh\\u00e4ngig von den Einstellungen in der Datenbank;\\n        \"],\"en\":[\"\\n            PT-12820 - Improve compatibility with third party plugins;\\n            PT-12824 - Status email sending considers the settings in the database now;\\n        \"]},\"4.2.1\":{\"de\":[\"PT-12823 - Update von Plugin-Versionen kleiner als 4.0.0 korrigiert;\"],\"en\":[\"PT-12823 - Fix update from plugin versions lower than 4.0.0;\"]},\"4.2.0\":{\"de\":[\"\\n            PT-12634 - Verbesserte Fehlerbenachrichtigung, falls die PayPal-H\\u00e4ndler-ID ung\\u00fcltig ist;\\n            PT-12706 - \\\"PayPal, sp\\u00e4ter bezahlen\\\" ist nun eine eigene Zahlungsart;\\n            PT-12776 - Sendet Status-Mails nach \\u00c4nderung des Bestell- oder Zahlungsstatus;\\n            PT-12780 - Behebt Fehler bei der Steuerberechnung von CustomProducts w\\u00e4hrend eines Rechnungskaufes;\\n            PT-12785 - Verbesserte Fehlerbenachrichtigung, falls das Einziehen \\/ Autorisieren nicht fehlschl\\u00e4gt;\\n        \"],\"en\":[\"\\n            PT-12634 - Improved error notification if PayPal merchant ID is invalid;\\n            PT-12706 - \\\"PayPal, pay later\\\" is now a separate payment method;\\n            PT-12776 - Sends status mails after change of order or payment status;\\n            PT-12780 - Fixes error while tax calculation during an invoice purchase with CustomProducts;\\n            PT-12785 - Improved error notification if capture \\/ authorisation fails;\\n        \"]},\"4.1.4\":{\"de\":[\"\\n            PT-11456 - Ber\\u00fccksichtigt Namen in der Lieferadresse beim Express-Checkout, wenn dieser verf\\u00fcgbar ist;\\n            PT-12718 - Behebt fehlerhaftes Verhalten beim Aktivieren \\/ Deaktivieren der Zahlungsart Kredit- oder Debitkarte;\\n            PT-12748 - Verbessert das Setzen des aktuellen Zahlungsstatus bei Transaktionen;\\n            PT-12754 - Behebt Probleme beim Ermitteln der Steuern;\\n            PT-12769 - Behebt Probleme mit Kredit- oder Debitkarten-Checkout;\\n        \"],\"en\":[\"\\n            PT-11456 - Considers name of shipping address if provided during Express Checkout;\\n            PT-12718 - Fixes incorrect behaviour when activating \\/ deactivating the payment type credit or debit card;\\n            PT-12748 - Improves the setting of the current payment status for transactions;\\n            PT-12754 - Fixes problems with determining the taxes;\\n            PT-12769 - Fixes problems with the credit or debit card checkout;\\n        \"]},\"4.1.3\":{\"de\":[\"\\n            PT-12675 - Behebt Problem mit Rabatten w\\u00e4hrend eines Rechnungskaufes;\\n            PT-12733 - Behebt Fehler bei R\\u00fcckzahlungen von Bestellungen aus Subshops;\\n            PT-12745 - Verbessert den Bezahlvorgang mit Kredit- oder Debitkarte;\\n        \"],\"en\":[\"\\n            PT-12675 - Fixes problem with discounts during an invoice purchase;\\n            PT-12733 - Fixes bug with refunds of orders from subshops;\\n            PT-12745 - Improves the payment process with credit or debit card;\\n        \"]},\"4.1.2\":{\"de\":[\"\\n            PT-12739 - Fehler bei PayPal Express behoben, wenn die Artikelnummer nummerisch sind und f\\u00fchrende Nullen haben;\\n            PT-12740 - Fehler bei PayPal Express behoben, wenn die Bestellnummer mitgesendet wird;\\n            PT-12744 - Erkennung von SEPA-Verf\\u00fcgbarkeit auf der Zahlungsauswahlseite korrigiert, wenn Smart Payment Buttons aktiviert sind;\\n        \"],\"en\":[\"\\n            PT-12739 - Fixed PayPal Express error when item numbers are numeric and have leading zeros;\\n            PT-12740 - Fixed an error with PayPal Express when the order number is sent;\\n            PT-12744 - Fixed detection of SEPA eligibility on the payment selection page if Smart Payment Buttons are enabled;\\n        \"]},\"4.1.1\":{\"de\":[\"\\n            PT-12704 - Verbessert das Verhalten bei fehlgeschlagenen Zahlungen, wenn die Bestellnummer an PayPal \\u00fcbertragen wird;\\n            PT-12704 - SEPA-Zahlungen werden nun korrekt im PayPal-Bestellmodul angezeigt;\\n            PT-12722 - Verbessert die Erkennung von SEPA-Verf\\u00fcgbarkeit auf der Zahlungsauswahlseite;\\n        \"],\"en\":[\"\\n            PT-12704 - Improves the behaviour in case of failed payments when the order number is transmitted to PayPal;\\n            PT-12704 - SEPA payments are now correctly displayed in the PayPal order module;\\n            PT-12722 - Improves the detection of SEPA eligibility on the payment selection page;\\n        \"]},\"4.1.0\":{\"de\":[\"\\n            PT-12674 - Zus\\u00e4tzliche Felder f\\u00fcr Telefonnummer und Geburtstag f\\u00fcr den Rechnungskauf hinzugef\\u00fcgt;\\n            PT-12700 - Nettopreisbehandlung verbessert;\\n            PT-12701 - Behebt Problem mit den Alternativen Zahlungsarten;\\n            PT-12703 - Verhalten der Zahlungsart Lastschrift verbessert;\\n            PT-12705 - Verbesserung der UI im Einstellungsmodul;\\n            PT-12709 - Adressvalidierung bei Nutzung von Express-Checkout korrigiert;\\n        \"],\"en\":[\"\\n            PT-12674 - Added additional fields for phone number and birthday for Pay upon invoice;\\n            PT-12700 - Improve net price handling;\\n            PT-12701 - Fixes problem with alternative payment methods;\\n            PT-12703 - Improved behaviour of the direct debit payment type;\\n            PT-12705 - Improvement of the UI in the settings module;\\n            PT-12709 - Fixes address validation while using Express-Checkout;\\n        \"]},\"4.0.2\":{\"de\":[\"\\n            PT-12692 - Das PayPal-Bestellmodul l\\u00e4sst sich wieder \\u00f6ffnen, wenn der Shop eine virtuelle URL konfiguriert hat;\\n            PT-12697 - Fehler beim Error-Handling von Kreditkartenzahlungen;\\n        \"],\"en\":[\"\\n            PT-12692 - The PayPal order module can be opened again if the shop has configured a virtual URL;\\n            PT-12697 - Fixed error handling of credit card payments;\\n        \"]},\"4.0.1\":{\"de\":[\"\\n            PT-12667 - PayerId wird nun w\\u00e4hrend der Autorisierung automatisch bef\\u00fcllt;\\n            PT-12679 - PayPal Plus Zahlungen werden nun wieder korrekt in der \\u00dcbersicht gelistet;\\n            PT-12680 - Zahlungsstatus wird nun korrekt bei R\\u00fcckbuchungen gesetzt;\\n            PT-12690 - Fehler bei der Benutzung von PayPal PLUS behoben;\\n            PT-12691 - Rechnungskauf verbessert;\\n        \"],\"en\":[\"\\n            PT-12667 - PayerId is now automatically set during authorisation;\\n            PT-12679 - PayPal Plus payments are now listed correctly in the overview again;\\n            PT-12680 - Payment status is now set correctly for refunds;\\n            PT-12690 - Fix issue while using PayPal PLUS;\\n            PT-12691 - Improve Pay upon invoice;\\n        \"]},\"4.0.0\":{\"de\":[\"\\n            PT-12487 - Integration neue Zahlungsart \'Rechnungskauf\';\\n            PT-12487 - Integration neue Zahlungsart \'Kredit- oder Debitkarte\';\\n            PT-12487 - Integration mehrerer Drittanbieter-Zahlungsarten wie \'sofort\', \'giropay\', \'ideal\' und mehr;\\n        \"],\"en\":[\"\\n            PT-12487 - Integration of new payment method \'Pay upon invoice\';\\n            PT-12487 - Integration of new payment method \'Credit or debit card\';\\n            PT-12487 - Integration of several third-party payment methods like \'sofort\', \'giropay\', \'ideal\' and more;\\n        \"]},\"3.2.0\":{\"de\":[\"\\n            PT-10683 - PayPal Express Button ist nun f\\u00fcr ESD Produkte deaktiviert, da diese ein Kundenkonto ben\\u00f6tigen;\\n            PT-12405 - Berechnung der Nettopreis-Benutzung f\\u00fcr L\\u00e4nder mit \\\"Steuerfrei f\\u00fcr Unternehmen\\\"-Option korrigiert;\\n        \"],\"en\":[\"\\n            PT-10683 - PayPal Express button is now disabled for ESD products, as those need a customer account;\\n            PT-12405 - Fixed calculation of net price usage for countries with \\\"Tax free for companies\\\" option;\\n        \"]},\"3.1.3\":{\"de\":[\"\\n            PT-12279 - L\\u00f6st ein neues Event w\\u00e4hrend des Erstellen der Rechnungsanweisung aus;\\n            PT-12341 - Behebt fehlerhaftes Verhalten des Ratenzahlungsbanners;\\n            PT-12345 - Verbessert das Verhalten des Ratenzahlungsbanners;\\n            PT-12346 - Behebt Fehler w\\u00e4hrend der Adress\\u00e4nderung;\\n            PT-12348 - Behebt fehler im Express-Checkout Prozess;\\n        \"],\"en\":[\"\\n            PT-12279 - Triggers a new event during the creation of the invoice instruction;\\n            PT-12341 - Fixes incorrect behavior of the installment banner;\\n            PT-12345 - Improves the behaviour of the instalment banner;\\n            PT-12346 - Fixes errors during the address change;\\n            PT-12348 - Fixes error in Express Checkout process;\\n        \"]},\"3.1.2\":{\"de\":[\"\\n            PT-12286 - Behebt Fehler im Risiko Management;\\n        \"],\"en\":[\"\\n            PT-12286 - Fixes errors in risk management;\\n        \"]},\"3.1.1\":{\"de\":[\"\\n            PT-12267 - Behebt Fehler im Risiko Management;\\n        \"],\"en\":[\"\\n            PT-12267 - Fixes errors in risk management;\\n        \"]},\"3.1.0\":{\"de\":[\"\\n            PT-10201 - PayPal Express Button ber\\u00fccksichtigt nun auch das Risiko Management;\\n            PT-12236 - Korrigiert das Setzen des PayPal-Payment-Typs f\\u00fcr Rechnungskauf;\\n        \"],\"en\":[\"\\n            PT-10201 - PayPal Express button now also considers the risk management;\\n            PT-12236 - Corrects setting the PayPal payment type for pay upon invoice;\\n        \"]},\"3.0.4\":{\"de\":[\"PT-12234 - Korrigiert das Setzen des PayPal-Payment-Typs f\\u00fcr Rechnungskauf;\"],\"en\":[\"PT-12234 - Corrects setting the PayPal payment type for pay upon invoice;\"]},\"3.0.3\":{\"de\":[\"PT-12230 - Korrigiert das Setzen des PayPal-Payment-Typs;\"],\"en\":[\"PT-12230 - Corrects setting the PayPal payment type;\"]},\"3.0.2\":{\"de\":[\"\\n            PT-12125 - Shopware 5.7 und PHP8 Kompatibilit\\u00e4t;\\n            PT-12178 - Der PayPal-Payment-Typ ist nun als Variable in der Bestellbest\\u00e4tigungs-Mail verf\\u00fcgbar;\\n            PT-12208 - F\\u00fcgt einen PayPal-Plus-Cookie zum Consent-Manager hinzu;\\n        \"],\"en\":[\"\\n            PT-12125 - Shopware 5.7 and PHP8 compatibility;\\n            PT-12178 - The PayPal payment type is now available as a variable in the order confirmation mail;\\n            PT-12208 - Adds a PayPal Plus cookie to the Consent Manager;\\n        \"]},\"3.0.1\":{\"de\":[\"PT-12068 - Korrigiert Link in den Einstellungen f\\u00fcr Ratenzahlung;\"],\"en\":[\"PT-12068 - Corrects link in installments settings;\"]},\"3.0.0\":{\"de\":[\"\\n            PT-10770 - Composer-Name des Plugin ist nun mit Composer 2 kompatibel;\\n            PT-11863 - Ben\\u00f6tigte Mindestversion von Shopware auf 5.2.27 angehoben;\\n            PT-12040 - In-Shop Ratenzahlung Integration entfernt;\\n        \"],\"en\":[\"\\n            PT-10770 - Composer name of the plugin is now compatible with Composer 2;\\n            PT-11863 - Increased required minimum version of shopware to 5.2.27;\\n            PT-12040 - Removed in-shop installments integration;\\n        \"]},\"2.8.1\":{\"de\":[\"\\n            PT-11760 - Behebt Fehler w\\u00e4hrend der installation \\u00fcber den \\\"Shopware First Run Wizard\\\";\\n            PT-11763 - Behebt fehlerhaftes Verhalten des Express-Checkout Buttons;\\n            PT-11782 - Optimiert PayPal Link in Bestellbest\\u00e4tigungsmail;\\n        \"],\"en\":[\"\\n            PT-11760 - Fixes error during installation via the \\\"Shopware First Run Wizard\\\";\\n            PT-11763 - Fixes incorrect behavior of the Express Checkout Button;\\n            PT-11782 - Optimizes PayPal link in order confirmation email;\\n        \"]},\"2.8.0\":{\"de\":[\"\\n            PT-11491 - PayPal-Cookies zum Consent-Manager hinzugef\\u00fcgt;\\n        \"],\"en\":[\"\\n            PT-11491 - Add PayPal cookies to consent manager;\\n        \"]},\"2.7.0\":{\"de\":[\"\\n            PT-10536 - Behebt Anzeigefehler des Logos in Bestellbest\\u00e4tigungsmail;\\n            PT-11361 - Entfernt Werbung f\\u00fcr Kostenlose Retouren;\\n            PT-11469 - Shop-Einstellungen werden im Firefox wieder korrekt geladen;\\n            PT-11574 - Behebt Fehler des Express-Checkout-Buttons in Produkt-Listings;\\n            PT-11575 - Javascript auf der Bestellbest\\u00e4tigungsseite verbessert, wenn der In-Context Modus benutzt wird;\\n        \"],\"en\":[\"\\n            PT-10536 - Fixes display error of logo in order confirmation mail;\\n            PT-11361 - Remove free return advertising;\\n            PT-11469 - Shop settings are loaded correctly in Firefox again;\\n            PT-11574 - Fixes error of Express Checkout button in product listings;\\n            PT-11575 - Improve Javascript on confirm page, if in-context mode is used;\\n        \"]},\"2.6.5\":{\"de\":[\"Erweiterbarkeit durch Dritt-Plugins verbessert;\"],\"en\":[\"Improved extensibility via third-party plugins;\"]},\"2.6.4\":{\"de\":[\"PT-11190 - Kompatibilit\\u00e4t mit \\u00e4lteren Shopware-Versionen wiederhergestellt;\"],\"en\":[\"PT-11190 - Restore compatibility with older Shopware versions;\"]},\"2.6.3\":{\"de\":[\"PT-11166 - Express Checkout unterst\\u00fctzt nun wieder korrekt die \\\"Warenkorb \\u00fcbertragen\\\" Option;\"],\"en\":[\"PT-11166 - Express Checkout correctly considers the \\\"Submit cart\\\" configuration again;\"]},\"2.6.2\":{\"de\":[\"PT-11003 - Fehler im Javascript behoben;\"],\"en\":[\"PT-11003 - Fix error in Javascript;\"]},\"2.6.1\":{\"de\":[\"PT-11003 - Fehler beim Update behoben;\"],\"en\":[\"PT-11003 - Fix error on update;\"]},\"2.6.0\":{\"de\":[\"\\n            PT-11003 - Neue Option f\\u00fcr das Bewerben von Ratenzahlung;\\n            PT-11082 - Umsatzsteuer-ID wird nun wieder korrekt ber\\u00fccksichtigt;\\n        \"],\"en\":[\"\\n            PT-11003 - New option for advertising installment payment;\\n            PT-11082 - VAT ID is now correctly considered again;\\n        \"]},\"2.5.2\":{\"de\":[\"PT-11021 - Fehler im Express Checkout behoben;\"],\"en\":[\"PT-11021 - Fix error in Express Checkout;\"]},\"2.5.1\":{\"de\":[\"\\n            PT-10636 - Ber\\u00fccksichtigt nun Kundengruppen Einstellungen korrekt;\\n            PT-10996 - Setzt nun mindestens Shopware 5.6.3 f\\u00fcr das Payment-Token-Feature voraus;\\n        \"],\"en\":[\"\\n            PT-10636 - Now considers customer group settings correctly;\\n            PT-10996 - Now requires at least Shopware 5.6.3 for the payment token feature;\\n        \"]},\"2.5.0\":{\"de\":[\"\\n            PT-9896 - Neue allgemeine Option f\\u00fcr das Warenkorb \\u00fcbertragen hinzugef\\u00fcgt;\\n            PT-10621 - Mit Shopware 5.6 kann nun die Session wiederhergestellt werden, falls diese verloren geht, w\\u00e4hrend der Kunde sich auf der PayPal Seite befindet;\\n            PT-10823 - Wenn verschiedene Aktionen \\u00fcber das PayPal Bestellmodul ausgel\\u00f6st werden, wird nun auch der Zahlungsstatus der Bestellung aktualisiert;\\n            PT-10825 - Anzeige der Smart Payment Buttons verbessert;\\n            PT-10855 - Problem behoben, dass manchmal beim Erstellen einer Zahlung f\\u00fcr den In-Context-Modus aufgetreten ist;\\n            PT-10919 - Fehlermeldungen im Bestellmodul sind nun fest gesetzt;\\n            PT-10953 - Anzeige des Maximalbetrags zur Wiedergutschrift im Bestellmodul wurde korrigiert;\\n        \"],\"en\":[\"\\n            PT-9896 - Add new general option for shopping cart submit;\\n            PT-10621 - With Shopware 5.6 the session can now be restored if it gets lost while the customer is on the PayPal page;\\n            PT-10823 - If several actions are triggered via the PayPal order module, the payment status of the order is now also updated;\\n            PT-10825 - Improve displaying of Smart Payment Buttons;\\n            PT-10855 - Fixed problem that sometimes occurred when creating a payment for in-context mode;\\n            PT-10919 - Error messages in the order module are now sticky;\\n            PT-10953 - Fix displaying of maximum amount for refund in order module;\\n        \"]},\"2.4.1\":{\"de\":[\"PT-10794 - Anzeigefehler mit aktivierten SPB auf der Best\\u00e4tigungsseite behoben;\"],\"en\":[\"PT-10794 - Fix display bug with activated SPB on confirm page;\"]},\"2.4.0\":{\"de\":[\"PT-10573, PT-10724, PT-10732, PT-10765 - Smart Payment Buttons integriert;\"],\"en\":[\"PT-10573, PT-10724, PT-10732, PT-10765 - Introduced Smart Payment Buttons;\"]},\"2.3.0\":{\"de\":[\"SW-24197, PT-10430 - Shopware 5.6 Kompatibilit\\u00e4t;\"],\"en\":[\"SW-24197, PT-10430 - Shopware 5.6 compatibility;\"]},\"2.2.4\":{\"de\":[\"PT-10584 - Fehler in der Suche im Bestellmodul behoben;\"],\"en\":[\"PT-10584 - Fixed search in order module;\"]},\"2.2.3\":{\"de\":[\"PT-10562 - Bestellbest\\u00e4tigung wird nun trotzdem wieder versendet, auch wenn der Bezahlvorgang fehlgeschlagen ist. Stellt Kompatibilit\\u00e4t mit anderen Plugins wieder her;\"],\"en\":[\"PT-10562 - Order confirmation will now be sent again, even if the payment process has failed. Restores compatibility with other plugins;\"]},\"2.2.2\":{\"de\":[\"PT-10330 - Wenn die Bestellnummer mitgesendet wird, wird nun in einem Fehlerfall zur Abschlussseite, statt zur Zahlungsartenauswahlseite, umgeleitet;\"],\"en\":[\"PT-10330 - If the order number gets sent and an error occurs, a redirect to the finish page is done, instead of the payment method selection page;\"]},\"2.2.1\":{\"de\":[\"PT-10471 - Ajax Calls des Plugins verbessert;\"],\"en\":[\"PT-10471 - Improve ajax calls of the plugin;\"]},\"2.2.0\":{\"de\":[\"\\n            PT-10162 - Die Sprache f\\u00fcr den Express-Checkout kann jetzt bei Bedarf individuell eingestellt werden;\\n            PT-10335 - Problem mit Bestellnummern mit Prefix in der PayPal Bestell\\u00fcbersicht behoben;\\n            PT-10349 - Das Bundesland wird nun beim Express Checkout korrekt gespeichert;\\n            PT-10371 - PayPal Dokument Erweiterungen werden nur noch geladen, wenn der richtige Dokumententyp ausgew\\u00e4hlt wurde;\\n            PT-10373 - Ajax Calls des Plugins verbessert;\\n        \"],\"en\":[\"\\n            PT-10162 - If required the locale code for the express checkout can now be set individually;\\n            PT-10335 - Solved problem with order numbers with prefix in PayPal order overview;\\n            PT-10349 - The country state is now saved correctly during express checkout;\\n            PT-10371 - PayPal document extensions are only loaded if the correct document type has been selected;\\n            PT-10373 - Improve ajax calls of the plugin;\\n        \"]},\"2.1.3\":{\"de\":[\"\\n            PT-7290 - Bestellungen ohne Versandart sind nun nicht mehr m\\u00f6glich;\\n            PT-10236 - Adresse des Kunden wird nun wieder korrekt auf der Abschlussseite angezeigt;\\n        \"],\"en\":[\"\\n            PT-7290 - Orders without shipping method are no longer possible;\\n            PT-10236 - Customer\'s address is now correctly displayed on the finish page again;\\n        \"]},\"2.1.2\":{\"de\":[\"PT-10176 - Pr\\u00fcfung des Risk-Management-Auschlusses verbessert;\"],\"en\":[\"PT-10176 - Improved risk management exclusion check;\"]},\"2.1.1\":{\"de\":[\"\\n            PT-9974 - Kompatibilit\\u00e4t mit Microsoft Browsern verbessert;\\n            PT-10102 - Javascript Fehler behoben, falls PayPal \\u00fcber das Risk-Management ausgeschlossen wurde;\\n        \"],\"en\":[\"\\n            PT-9974 - Improve compatibility with Microsoft browsers;\\n            PT-10102 - Fix javascript error, if PayPal was excluded via risk management;\\n        \"]},\"2.1.0\":{\"de\":[\"\\n            PT-9419 - Der Express-Checkout Button kann nun optional auf Kategorieseiten angezeigt werden. Die Shopware-Einstellung \\\"Kaufenbutton im Listing anzeigen\\\" muss aktiviert sein;\\n            PT-9754 - Verbessert die Kompatibilit\\u00e4t mit anderen Plugins in der PDF-Belegerstellung;\\n            PT-9965 - Verhalten Express Checkout Button auf der Registrierungsseite verbessert;\\n            PT-9989 - Der Kunde hat nun nicht mehr die M\\u00f6glichkeit auf der PayPal Seite seine Lieferadresse zu \\u00e4ndern;\\n        \"],\"en\":[\"\\n            PT-9419 - The Express-Checkout button can now be displayed optionally on category pages. The Shopware setting \\\"Display buy button in listing\\\" must be activated;\\n            PT-9754 - Improve compatibility with other plugins in PDF document creation;\\n            PT-9965 - Improve behaviour of Express Checkout button on registration page;\\n            PT-9989 - The customer has no longer the option to change the shipping address on the PayPal page;\\n        \"]},\"2.0.3\":{\"de\":[\"PT-9990 - Kundenkommentare werden bei Zahlungen \\u00fcber Plus wieder korrekt gespeichert;\"],\"en\":[\"PT-9990 - Customer comments are saved correctly again for payments via Plus;\"]},\"2.0.2\":{\"de\":[\"PT-9947 - \\u00dcbertragung der Sprachcodes f\\u00fcr deutschsprachige L\\u00e4nder korrigiert;\"],\"en\":[\"PT-9947 - Fixed language codes transmission for German speaking countries;\"]},\"2.0.1\":{\"de\":[\"\\n            PT-9838 - Zum \\u00c4ndern des Bezahlstatus wird nun die Core-Funktionalit\\u00e4t genutzt;\\n            PT-9942 - Kompatibilit\\u00e4t mit Custom Products verbessert;\\n            PT-9945 - Abweichende Steuerregeln werden nun korrekt angewendet, wenn mit Express Checkout bezahlt wird;\\n        \"],\"en\":[\"\\n            PT-9838 - The core functionality is now used to change the payment status;\\n            PT-9942 - Improved compatibility with Custom Products;\\n            PT-9945 - Different tax rules are now correctly applied when paying with Express Checkout;\\n        \"]},\"2.0.0\":{\"de\":[\"\\n            PT-9704 - Paypal Expresscheckout Konfiguration optimiert\\n            PT-9705 - Google Pagespeed Kritieren optimiert\\n            PT-9738 - Fehler in der Suche der Paypal Bestell\\u00fcbersicht behoben\\n            PT-9752 - Bessere Fehlermeldungen bei der \\u00dcbermittlung von inkorrekten Adressdaten\\n        \"],\"en\":[\"\\n            PT-9704 - Optimized Paypal expresscheckout configuration\\n            PT-9705 - Optimization for Google pagespeed\\n            PT-9738 - Fixed an error in order overview search\\n            PT-9752 - Optimized error messages when submitting incorrect address data\\n        \"]},\"1.1.1\":{\"de\":[\"\\n            PT-8708 - Obsoleten Code und Tabellenspalte entfernt;\\n            PT-9564 - Kompatibilit\\u00e4t mit Safari auf iOS und MacOS verbessert;\\n            PT-9710 - \\u00dcbertragung von Preisen zu PayPal verbessert;\\n        \"],\"en\":[\"\\n            PT-8708 - Removed obsolete code and table column;\\n            PT-9564 - Improved compatibility with Safari on iOS and MacOS;\\n            PT-9710 - Improved transmission of prices to PayPal;\\n        \"]},\"1.1.0\":{\"de\":[\"\\n            PT-8708, PT-9707 - Die PayPal Experience Profile Logik wurde durch den Application Context ersetzt. Dadurch entf\\u00e4llt die Bildauswahl f\\u00fcr das Logo auf der PayPal-Seite. Dies wird nun \\u00fcber das H\\u00e4ndlerkonto bezogen;\\n            PT-9269 - Englische \\u00dcbersetzungen korrigiert;\\n            PT-9649 - Nach einer R\\u00fcckerstattung wird der Status der Bestellung korrekt gesetzt;\\n            PT-9710 - \\u00dcbertragung von Preisen zu PayPal verbessert;\\n        \"],\"en\":[\"\\n            PT-8708, PT-9707 - The PayPal Experience Profile logic has been replaced by the Application Context. Therefore the image selection for the logo on the PayPal page is no longer necessary. This will now be retrieved via the merchant account;\\n            PT-9269 - Fixed English translation;\\n            PT-9649 - The status of the order will be set correctly after a refund;\\n            PT-9710 - Improved transmission of prices to PayPal;\\n        \"]},\"1.0.7\":{\"de\":[\"\\n            PT-9420 - Ratenzahlung wird nun korrekt nach der Registrierung angezeigt;\\n            PT-9542 - Anzeige Fehler beim Zahlungsartwechsel behoben;\\n            PT-9590 - Netto\\/Brutto Behandlung bei Express Checkout verbessert;\\n            PT-9596 - Behandlung von \'Steuerfrei f\\u00fcr Unternehmen\' verbessert;\\n            PT-9604 - Versandkosten werden nun bei Nutzung von Plus korrekt zu PayPal \\u00fcbertragen;\\n        \"],\"en\":[\"\\n            PT-9420 - After registration installments is shown correctly;\\n            PT-9542 - Fix display bug on payment method change;\\n            PT-9590 - Improve net\\/gross handling on Express Checkout;\\n            PT-9596 - Improve handling of tax free for companies;\\n            PT-9604 - Shipping costs are now transferred correctly to PayPal if Plus is used;\\n        \"]},\"1.0.6\":{\"de\":[\"\\n            PT-9367 - Aufgrund des PayPal Risk-Managements werden Custom Products Optionen nicht mehr als einzelne Warenkorb Positionen \\u00fcbertragen. Evtl. Aufschl\\u00e4ge werden auf den Produktpreis aufgerechnet;\\n            PT-9383 - Workflow mit steuerfreien oder Netto-Bestellungen verbessert;\\n            PT-9424 - AGB-Checkbox Validierung verbessert;\\n            PT-9546 - Setzen des L\\u00e4ndersprachcodes korrigiert;\\n        \"],\"en\":[\"\\n            PT-9367 - Due to PayPal\'s risk management, Custom Products options are no longer transferred as single shopping cart items. Possible surcharges will be added to the product price;\\n            PT-9383 - Improve workflow with tax free and net orders;\\n            PT-9424 - Improve ToS checkbox validation;\\n            PT-9546 - Fix setting of country language code;\\n        \"]},\"1.0.5\":{\"de\":[\"\\n            PT-9318 - Sprache der Buttons f\\u00fcr Express Checkout und In-Context richtet sich nun nach der Shopsprache;\\n            PT-9366 - Javascript Warnung korrigiert;\\n            Datum in der Bestell\\u00fcbersicht korrigiert;\\n        \"],\"en\":[\"\\n            PT-9318 - The shop language is now considered for the language of the express checkout and in-context buttons;\\n            PT-9366 - Fix Javascript warning;\\n            Fix date in order overview;\\n        \"]},\"1.0.4\":{\"de\":[\"PT-9241 - Optimierung von PayPal Bannern und Buttons;\"],\"en\":[\"PT-9241 - Optimize PayPal banners and buttons;\"]},\"1.0.3\":{\"de\":[\"\\n            PT-9252 - Anzeige des Express Checkout Buttons korrigiert;\\n            PT-9285 - Logo f\\u00fcr Drittanbieter Zahlungsart im Plus iFrame nun pflegbar;\\n            PT-9290 - Einstellungen werden jetzt entfernt, wenn dies bei der Deinstallation ausgew\\u00e4hlt wird;\\n        \"],\"en\":[\"\\n            PT-9252 - Fix displaying of express checkout button;\\n            PT-9285 - Logo for third party payment in Plus iFrame method can now be maintained;\\n            PT-9290 - If selected on uninstallation, the settings now will be removed;\\n        \"]},\"1.0.2\":{\"de\":[\"PT-9245 - Bezahldatum wird nun korrekt gesetzt;\"],\"en\":[\"PT-9245 - Date of payment is now set correctly;\"]},\"1.0.1\":{\"de\":[\"\\n            PT-9236 - PDF Elemente lassen sich nun korrekt editieren;\\n            PT-9267 - Benutzt nun minifizierte Javascript Library;\\n        \"],\"en\":[\"\\n            PT-9236 - PDF elements could now edited correctly;\\n            PT-9267 - Now uses minified Javascript library;\\n        \"]},\"1.0.0\":{\"de\":[\"Erstver\\u00f6ffentlichung;\"],\"en\":[\"First release;\"]}}',	'http://store.shopware.com',	NULL,	NULL,	1,	1,	1,	NULL,	NULL,	1,	0),
(61,	'ShopwarePlugins',	'SwagMigrationConnector',	'Shopware Migration Connector',	'',	'The Migration Connector provides API endpoints that allow Shopware 6 to create a secure data connection with the active Shopware 5 shop which support the data migration to Shopware 6.',	'{\"de\":{\"label\":\"Shopware Migration Connector\",\"description\":\"Der Migration Connector stellt API-Endpunkte zur Verf\\u00fcgung, die es Shopware 6 erm\\u00f6glicht, eine sichere Datenverbindung zum aktiven Shopware 5-Shop zu erstellen.\"},\"en\":{\"label\":\"Shopware Migration Connector\",\"description\":\"The Migration Connector provides API endpoints that allow Shopware 6 to create a secure data connection with the active Shopware 5 shop which support the data migration to Shopware 6.\"}}',	NULL,	1,	'2026-02-02 16:22:58',	'2026-02-02 16:22:59',	'2026-02-02 16:22:59',	'2026-02-02 16:23:01',	'shopware AG',	NULL,	NULL,	'2.1.0',	NULL,	'{\"2.1.0\":{\"de\":[\"\\n            MIG-1033 - Die Migration der SEO-Hauptkategorie f\\u00fcr Produkte wurde hinzugef\\u00fcgt;\\n            MIG-1077 - Optimierter Datenabruf unter Ber\\u00fccksichtigung der \\u00fcbergebenen Batch-Werte;\\n        \"],\"en\":[\"\\n            MIG-1033 - Added the migration of SEO main category for products;\\n            MIG-1077 - Optimized repository query to respect specified batch sizes;\\n        \"]},\"2.0.1\":{\"de\":[\"\\n            MIG-845 - Verbesserung der Migration von Bestelldokumenten;\\n            MIG-975 - Prefix f\\u00fcr Nummernkreise wird nur noch korrekt f\\u00fcr Produktbestellnummern verwendet;\\n            MIG-983 - Korrigiert einen Fehler bei der Migration von Kunden, die an einen Shop gebunden sind;\\n        \"],\"en\":[\"\\n            MIG-845 - Improve migration of order documents;\\n            MIG-975 - Prefix for number ranges is now only correctly used for product order numbers;\\n            MIG-983 - Fix migration of shop bounded customers;\\n        \"]},\"2.0.0\":{\"de\":[\"\\n            MIG-825 - [BREAKING] Hinzuf\\u00fcgen der Parameter `order by` und `where` zu `fetchIdentifiers` von `Repository\\/AbstractRepository.php`;\\n            MIG-825 - [BREAKING] \\u00c4ndern der Funktionen `addTableSelection` und `fetchIdentifiers` von `Repository\\/AbstractRepository.php` auf final;\\n            MIG-825 - Verbessert die Performance bei der Migration von Bestellungen;\\n            MIG-865 - Migration der ESD-Dateien f\\u00fcr SW5.4 korrigiert;\\n            MIG-899 - Ge\\u00e4ndertes Verhalten der Migration von SEO URLs. Die URL Gro\\u00df-\\/Kleinschreibung von Shopware 5 wird nun ber\\u00fccksichtigt;\\n        \"],\"en\":[\"\\n            MIG-825 - [BREAKING] Added parameters `orderBy` and `where` to `fetchIdentifiers` of `Repository\\/AbstractRepository.php`;\\n            MIG-825 - [BREAKING] Changed functions `addTableSelection` and `fetchIdentifiers` of `Repository\\/AbstractRepository.php` to be final;\\n            MIG-825 - Improve performance of the migration of orders;\\n            MIG-865 - Fix migration of esd files for SW5.4;\\n            MIG-899 - Changed behavior of the migration of seo urls. It now considers the URL case setting of shopware 5;\\n        \"]},\"1.4.3\":{\"de\":[\"\\n            MIG-865 - Migration der Dokumente und ESD-Dateien korrigiert;\\n            MIG-881 - Migration der Versandkosten korrigiert;\\n        \"],\"en\":[\"\\n            MIG-865 - Fix migration of documents and esd files;\\n            MIG-881 - Fix migration of shipping costs;\\n        \"]},\"1.4.2\":{\"de\":[\"\\n            NEXT-31037 - Problem von falsch typisierten Daten in SW5.7.15 + \\u00e4lter und PHP8.1 behoben, welche zu Fehlern im Migrations-Assistenten f\\u00fchren\\n        \"],\"en\":[\"\\n            NEXT-31037 - Fixed wrongly typed values in SW5.7.15 + older and PHP8.1, which result in migration assistant errors\\n        \"]},\"1.4.1\":{\"de\":[\"\\n            MIG-840 - Migration der Kategorie-Medien korrigiert;\\n        \"],\"en\":[\"\\n            MIG-840 - Fix migration of category media;\\n        \"]},\"1.4.0\":{\"de\":[\"\\n            NEXT-22545 - Migration der ESD-Download-Produkte hinzugef\\u00fcgt\\n        \"],\"en\":[\"\\n            NEXT-22545 - Migration of esd download products added\\n        \"]},\"1.3.4\":{\"de\":[\"PT-12392 - Behebt Fehler beim \\u00d6ffnen der Migrations-Information im Backend;\"],\"en\":[\"PT-12392 - Fixes error when opening the migration information in the backend;\"]},\"1.3.3\":{\"de\":[\"\\n            MIG-244 - Optimiert die Migration von Hauptvarianten-Informationen\\n        \"],\"en\":[\"\\n            MIG-244 - Optimize main variant information\\n        \"]},\"1.3.2\":{\"de\":[\"\\n            MIG-233 - Migration der Merkzettel \\/ Wunschlisten hinzugef\\u00fcgt\\n        \"],\"en\":[\"\\n            MIG-233 - Migration of notes \\/ wishlists added\\n        \"]},\"1.3.1\":{\"de\":[\"\\n            MIG-220 - Optimiert die Migration von Produkt-Vorschaubildern\\n        \"],\"en\":[\"\\n            MIG-220 - Optimize product image cover migration\\n        \"]},\"1.3.0\":{\"de\":[\"\\n            MIG-182 - Migration der Gutscheine hinzugef\\u00fcgt\\n            MIG-187 - Verbessert die Migration von Medien ohne Dateinamen\\n        \"],\"en\":[\"\\n            MIG-182 - Migration of vouchers added\\n            MIG-187 - Improves the migration of media without filename\\n        \"]},\"1.2.5\":{\"de\":[\"\\n            MIG-110 - Verbessert die Migration der Medien\\n            MIG-114 - Erm\\u00f6glicht die Migration der Hauptvarianten-Information\\n            PT-12123 - Shopware 5.7 und PHP 8 Kompatibilit\\u00e4t;\\n        \"],\"en\":[\"\\n            MIG-110 - Improves the migration of media\\n            MIG-114 - Provide migration of main variant information\\n            PT-12123 - Shopware 5.7 and PHP 8 compatibility;\\n        \"]},\"1.2.4\":{\"de\":[\"\\n            MIG-107 - Verbessert die Migration von Versandarten\\n            MIG-109 - Verbessern der Migration der Bestellungen\\n        \"],\"en\":[\"\\n            MIG-107 - Improves the migration of shipping methods\\n            MIG-109 - Improve migration of orders\\n        \"]},\"1.2.3\":{\"de\":[\"\\n            MIG-73 - Optimiert die Migration der Varianten\\u00fcbersetzungen\\n        \"],\"en\":[\"\\n            MIG-73 - Optimizes migration of variant translations\\n        \"]},\"1.2.2\":{\"de\":[\"\\n            MIG-69 - Behebt einen Fehler beim Update-Check\\n        \"],\"en\":[\"\\n            MIG-69 - Fixes a problem with the update check\\n        \"]},\"1.2.1\":{\"de\":[\"\\n            MIG-22 - Behebt ein Problem bei der Migration von Bestellungen, das durch abgebrochene Bestellungen verursacht wurde\\n        \"],\"en\":[\"\\n            MIG-22 - Fixes a problem with migrating orders, caused by aborted orders\\n        \"]},\"1.2.0\":{\"de\":[\"\\n            PT-11910 - Migration von Cross-Selling-Relationen\\n        \"],\"en\":[\"\\n            PT-11910 - Migration of cross selling relations\\n        \"]},\"1.1.3\":{\"de\":[\"\\n            PT-11819 - Optimiert die Produkt-Varianten Migration\\n        \"],\"en\":[\"\\n            PT-11819 - Optimizes product variant migration\\n        \"]},\"1.1.2\":{\"de\":[\"\\n            PT-11732 - Behebt ein Problem mit der PHP R\\u00fcckw\\u00e4rts-Kompatibilit\\u00e4t\\n        \"],\"en\":[\"\\n            PT-11732 - Solves an issue regarding php backwards compatibility\\n        \"]},\"1.1.1\":{\"de\":[\"\\n            NTR - Behebt ein Problem mit der PHP R\\u00fcckw\\u00e4rts-Kompatibilit\\u00e4t\\n        \"],\"en\":[\"\\n            NTR - Solves an issue regarding php backwards compatibility\\n        \"]},\"1.1.0\":{\"de\":[\"\\n            PT-11586 - Optimierte Produktmigration\\n        \"],\"en\":[\"\\n            PT-11586 - Optimized product migration\\n        \"]},\"1.0.0\":{\"de\":[\"\\n            PT-11394 - Behebt ein Problem mit der Produktsichtbarkeit in verschachtelten Shop-Strukturen\\n        \"],\"en\":[\"\\n            PT-11394 - Fix product visibility for nested shop structures\\n        \"]},\"0.13.0\":{\"de\":[\"\\n            NTR - \\u00dcberarbeitung der API;\\n            PT-10954 - Beheben des Releasedatum-Checks;\\n        \"],\"en\":[\"\\n            NTR - Refactor API;\\n            PT-10954 - Fix release date check;\\n        \"]},\"0.12.0\":{\"de\":[\"\\n            NTR - Behebt ein Problem mit der mysql Kompatibilit\\u00e4t;\\n            PT-10819 - Optimiert das Holen der Newsletter-Empf\\u00e4nger;\\n            PT-10846 - Migration von Produktbewertungen;\\n            PT-10861 - Migration von SEO Urls;\\n        \"],\"en\":[\"\\n            NTR - Fix mysql compatibility;\\n            PT-10819 - Optimize newsletter fetch query;\\n            PT-10846 - Migration of product reviews;\\n            PT-10861 - Migration of seo urls;\\n        \"]},\"0.11.0\":{\"de\":[\"\\n            PT-10403 - Endpunkt f\\u00fcr Versandkosten hinzugef\\u00fcgt;\\n            PT-10477 - Optimiert das Auslesen der Produktsichtbarkeit;\\n        \"],\"en\":[\"\\n            PT-10403 - Added endpoint for shipping costs;\\n            PT-10477 - Optimizes reading of product visibility;\\n        \"]},\"0.10.2\":{\"de\":[\"Problem mit Herstellerbildern behoben\"],\"en\":[\"Fixed problem with manufacturer images\"]},\"0.10.1\":{\"de\":[\"PT-10679 - Kompatibilit\\u00e4t zu Shopware 5.4 wiederhergestellt;\"],\"en\":[\"PT-10679 - Restored compatibility to Shopware 5.4;\"]},\"0.10.0\":{\"de\":[\"Optimierungen und Shopware 5.6 Kompatibilit\\u00e4t;\"],\"en\":[\"Optimizations and Shopware 5.6 compatibility;\"]},\"0.9.0\":{\"de\":[\"Erstver\\u00f6ffentlichung;\"],\"en\":[\"Initial release;\"]}}',	'http://store.shopware.com',	NULL,	NULL,	1,	1,	1,	NULL,	NULL,	1,	0);

DROP TABLE IF EXISTS `s_core_pricegroups`;
CREATE TABLE `s_core_pricegroups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `description` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_pricegroups` (`id`, `description`) VALUES
(1,	'Standard');

DROP TABLE IF EXISTS `s_core_pricegroups_discounts`;
CREATE TABLE `s_core_pricegroups_discounts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `groupID` int NOT NULL,
  `customergroupID` int NOT NULL,
  `discount` double NOT NULL,
  `discountstart` double NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `groupID` (`groupID`,`customergroupID`,`discountstart`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_core_rewrite_urls`;
CREATE TABLE `s_core_rewrite_urls` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `org_path` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `path` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `main` int unsigned NOT NULL,
  `subshopID` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `path` (`path`,`subshopID`),
  KEY `org_path` (`org_path`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_rewrite_urls` (`id`, `org_path`, `path`, `main`, `subshopID`) VALUES
(12,	'sViewport=custom&sCustom=2',	'Hilfe-/-Support',	0,	1),
(52,	'sViewport=cat&sCategory=7',	'Freizeit-und-Elektro/',	0,	1),
(53,	'sViewport=detail&sArticle=3',	'Freizeit-und-Elektro/3/Hauptartikel-mit-ESD-Download',	0,	1),
(54,	'sViewport=detail&sArticle=4',	'Freizeit-und-Elektro/4/Artikel-mit-Standard-Konfigurator-Rucksack',	0,	1),
(55,	'sViewport=detail&sArticle=9',	'Bekleidung/9/Artikel-mit-Auswahl-Konfigurator-Hose',	0,	1),
(56,	'sViewport=detail&sArticle=5',	'Bekleidung/5/Variantenartikel-Shirt',	0,	1),
(57,	'sViewport=detail&sArticle=14',	'Bekleidung/14/Artikel-mit-Bild-Konfigurator-Handtasche',	0,	1),
(58,	'sViewport=detail&sArticle=13',	'Lebensmittel/13/Hauptartikel-mit-Bewertungen-Schokolade',	0,	1),
(59,	'sViewport=detail&sArticle=12',	'Lebensmittel/12/Hauptartikel-mit-Cross-Selling-Fisch',	0,	1),
(60,	'sViewport=detail&sArticle=11',	'Lebensmittel/11/Hauptartikel-mit-Abverkauf-Brot',	0,	1),
(61,	'sViewport=detail&sArticle=10',	'Lebensmittel/10/Hauptartikel-mit-Grundpreisberechnung-Tube',	0,	1),
(62,	'sViewport=detail&sArticle=8',	'Bekleidung/8/Hauptartikel-mit-Ressourcen-Socken',	0,	1),
(63,	'sViewport=detail&sArticle=7',	'Bekleidung/7/Hauptartikel-mit-Eigenschaften-Hemd',	0,	1),
(64,	'sViewport=detail&sArticle=6',	'Bekleidung/6/Hauptartikel-mit-Kennzeichnung-Versandkostenfrei-Hervorhebung-Handschuh',	0,	1),
(65,	'sViewport=detail&sArticle=2',	'Freizeit-und-Elektro/2/Hauptartikel-mit-E-Mail-Benachrichtigung-Waschmaschine',	0,	1),
(66,	'sViewport=detail&sArticle=1',	'Freizeit-und-Elektro/1/Hauptartikel-Mobile',	0,	1),
(197,	'sViewport=detail&sArticle=1',	'Freizeit-und-Elektro/1/Hauptartikel',	0,	1),
(198,	'sViewport=detail&sArticle=4',	'Freizeit-und-Elektro/4/Artikel-mit-Standard-Konfigurator',	0,	1),
(207,	'sViewport=detail&sArticle=2',	'Freizeit-und-Elektro/2/Hauptartikel-mit-E-Mail-Benachrichtigung',	0,	1),
(286,	'sViewport=detail&sArticle=8',	'Bekleidung/Damen/8/Hauptartikel-mit-Ressourcen',	1,	1),
(287,	'sViewport=detail&sArticle=5',	'Bekleidung/Damen/5/Variantenartikel',	1,	1),
(288,	'sViewport=detail&sArticle=13',	'Lebensmittel/Suesses/13/Hauptartikel-mit-Bewertungen',	1,	1),
(289,	'sViewport=detail&sArticle=1',	'Freizeit-Elektro/1/Hauptartikel',	1,	1),
(290,	'sViewport=detail&sArticle=9',	'Bekleidung/Herren/9/Artikel-mit-Auswahl-Konfigurator',	1,	1),
(291,	'sViewport=detail&sArticle=12',	'Lebensmittel/Fisch/12/Hauptartikel-mit-Cross-Selling',	1,	1),
(292,	'sViewport=detail&sArticle=10',	'Lebensmittel/Suesses/10/Hauptartikel-mit-Grundpreisberechnung',	1,	1),
(293,	'sViewport=detail&sArticle=7',	'Bekleidung/Herren/7/Hauptartikel-mit-Eigenschaften',	1,	1),
(294,	'sViewport=detail&sArticle=6',	'Bekleidung/Herren/6/Hauptartikel-mit-Kennzeichnung-Versandkostenfrei-und-Hervorhebung',	1,	1),
(295,	'sViewport=detail&sArticle=3',	'Freizeit-Elektro/3/Hauptartikel-mit-ESD-Download',	1,	1),
(296,	'sViewport=detail&sArticle=2',	'Freizeit-Elektro/2/Hauptartikel-mit-E-Mail-Benachrichtigung',	1,	1),
(297,	'sViewport=detail&sArticle=14',	'Bekleidung/Damen/14/Artikel-mit-Bild-Konfigurator',	1,	1),
(298,	'sViewport=detail&sArticle=4',	'Freizeit-Elektro/4/Artikel-mit-Standard-Konfigurator',	1,	1),
(299,	'sViewport=detail&sArticle=11',	'Lebensmittel/Backwaren/11/Hauptartikel-mit-Abverkauf',	1,	1),
(323,	'sViewport=cat&sCategory=5',	'Lebensmittel/',	1,	1),
(324,	'sViewport=cat&sCategory=8',	'Lebensmittel/Backwaren/',	1,	1),
(325,	'sViewport=cat&sCategory=9',	'Lebensmittel/Fisch/',	1,	1),
(326,	'sViewport=cat&sCategory=10',	'Lebensmittel/Suesses/',	1,	1),
(327,	'sViewport=cat&sCategory=6',	'Bekleidung/',	1,	1),
(328,	'sViewport=cat&sCategory=11',	'Bekleidung/Damen/',	1,	1),
(329,	'sViewport=cat&sCategory=12',	'Bekleidung/Herren/',	1,	1),
(330,	'sViewport=cat&sCategory=7',	'Freizeit-Elektro/',	1,	1),
(331,	'sViewport=forms&sFid=5',	'Kontaktformular',	1,	1),
(332,	'sViewport=forms&sFid=8',	'Partnerformular',	1,	1),
(333,	'sViewport=forms&sFid=9',	'Defektes-Produkt',	1,	1),
(334,	'sViewport=forms&sFid=10',	'Rueckgabe',	1,	1),
(335,	'sViewport=forms&sFid=16',	'Anfrage-Formular',	1,	1),
(336,	'sViewport=forms&sFid=17',	'Partner-form',	1,	1),
(337,	'sViewport=forms&sFid=18',	'Contact',	1,	1),
(338,	'sViewport=forms&sFid=19',	'Defective-product',	1,	1),
(339,	'sViewport=forms&sFid=20',	'Return',	1,	1),
(340,	'sViewport=forms&sFid=21',	'Inquiry-form',	1,	1),
(341,	'sViewport=forms&sFid=22',	'Support-beantragen',	1,	1),
(342,	'sViewport=custom&sCustom=2',	'Hilfe/Support',	1,	1),
(343,	'sViewport=custom&sCustom=3',	'Impressum',	1,	1),
(344,	'sViewport=custom&sCustom=4',	'AGB',	1,	1),
(345,	'sViewport=custom&sCustom=6',	'Versand-und-Zahlungsbedingungen',	1,	1),
(346,	'sViewport=custom&sCustom=7',	'Datenschutz',	1,	1),
(347,	'sViewport=custom&sCustom=8',	'Widerrufsrecht',	1,	1),
(348,	'sViewport=custom&sCustom=9',	'UEber-uns',	1,	1),
(349,	'sViewport=custom&sCustom=43',	'rechtliche-Vorabinformationen',	1,	1),
(350,	'sViewport=custom&sCustom=45',	'Widerrufsformular',	1,	1),
(351,	'sViewport=listing&sAction=manufacturer&sSupplier=1',	'Shopware-Fashion/',	1,	1),
(352,	'sViewport=listing&sAction=manufacturer&sSupplier=2',	'Shopware-Food/',	1,	1),
(353,	'sViewport=listing&sAction=manufacturer&sSupplier=3',	'Shopware-Freetime/',	1,	1);

DROP TABLE IF EXISTS `s_core_rulesets`;
CREATE TABLE `s_core_rulesets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `paymentID` int NOT NULL,
  `rule1` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `value1` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `rule2` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `value2` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_core_sessions`;
CREATE TABLE `s_core_sessions` (
  `id` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `data` mediumblob NOT NULL,
  `modified` int unsigned NOT NULL,
  `expiry` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sess_expiry` (`expiry`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

DROP TABLE IF EXISTS `s_core_sessions_backend`;
CREATE TABLE `s_core_sessions_backend` (
  `id` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `data` mediumblob NOT NULL,
  `modified` int unsigned NOT NULL,
  `expiry` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sess_expiry` (`expiry`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

DROP TABLE IF EXISTS `s_core_shop_currencies`;
CREATE TABLE `s_core_shop_currencies` (
  `shop_id` int unsigned NOT NULL,
  `currency_id` int unsigned NOT NULL,
  PRIMARY KEY (`shop_id`,`currency_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_shop_currencies` (`shop_id`, `currency_id`) VALUES
(1,	1);

DROP TABLE IF EXISTS `s_core_shop_pages`;
CREATE TABLE `s_core_shop_pages` (
  `shop_id` int unsigned NOT NULL,
  `group_id` int unsigned NOT NULL,
  PRIMARY KEY (`shop_id`,`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_core_shops`;
CREATE TABLE `s_core_shops` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `main_id` int unsigned DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `position` int NOT NULL,
  `host` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `base_path` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `base_url` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `hosts` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `secure` int unsigned NOT NULL,
  `template_id` int unsigned DEFAULT NULL,
  `document_template_id` int unsigned DEFAULT NULL,
  `category_id` int unsigned DEFAULT NULL,
  `locale_id` int unsigned DEFAULT NULL,
  `currency_id` int unsigned DEFAULT NULL,
  `customer_group_id` int unsigned DEFAULT NULL,
  `fallback_id` int unsigned DEFAULT NULL,
  `customer_scope` int NOT NULL,
  `default` int unsigned NOT NULL,
  `active` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `main_id` (`main_id`),
  KEY `host` (`host`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_shops` (`id`, `main_id`, `name`, `title`, `position`, `host`, `base_path`, `base_url`, `hosts`, `secure`, `template_id`, `document_template_id`, `category_id`, `locale_id`, `currency_id`, `customer_group_id`, `fallback_id`, `customer_scope`, `default`, `active`) VALUES
(1,	NULL,	'swagfiveseven',	NULL,	0,	'swagfiveseven.de',	NULL,	NULL,	'swagfiveseven.de',	0,	22,	22,	3,	1,	1,	1,	NULL,	0,	1,	1);

DROP TABLE IF EXISTS `s_core_shops_attributes`;
CREATE TABLE `s_core_shops_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `shopID` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shopID` (`shopID`),
  CONSTRAINT `FK__s_core_shops` FOREIGN KEY (`shopID`) REFERENCES `s_core_shops` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_core_snippets`;
CREATE TABLE `s_core_snippets` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `namespace` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `shopID` int unsigned NOT NULL,
  `localeID` int unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  `dirty` int DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `namespace` (`namespace`,`shopID`,`name`,`localeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

DROP TABLE IF EXISTS `s_core_states`;
CREATE TABLE `s_core_states` (
  `id` int NOT NULL,
  `name` varchar(55) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `description` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `position` int NOT NULL,
  `group` varchar(25) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `mail` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_states` (`id`, `name`, `description`, `position`, `group`, `mail`) VALUES
(-1,	'cancelled',	'Abgebrochen',	25,	'state',	0),
(0,	'open',	'Offen',	1,	'state',	1),
(1,	'in_process',	'In Bearbeitung (Wartet)',	2,	'state',	1),
(2,	'completed',	'Komplett abgeschlossen',	3,	'state',	0),
(3,	'partially_completed',	'Teilweise abgeschlossen',	4,	'state',	0),
(4,	'cancelled_rejected',	'Storniert / Abgelehnt',	5,	'state',	1),
(5,	'ready_for_delivery',	'Zur Lieferung bereit',	6,	'state',	1),
(6,	'partially_delivered',	'Teilweise ausgeliefert',	7,	'state',	1),
(7,	'completely_delivered',	'Komplett ausgeliefert',	8,	'state',	1),
(8,	'clarification_required',	'Klärung notwendig',	9,	'state',	1),
(9,	'partially_invoiced',	'Teilweise in Rechnung gestellt',	1,	'payment',	0),
(10,	'completely_invoiced',	'Komplett in Rechnung gestellt',	2,	'payment',	0),
(11,	'partially_paid',	'Teilweise bezahlt',	3,	'payment',	0),
(12,	'completely_paid',	'Komplett bezahlt',	4,	'payment',	0),
(13,	'1st_reminder',	'1. Mahnung',	5,	'payment',	0),
(14,	'2nd_reminder',	'2. Mahnung',	6,	'payment',	0),
(15,	'3rd_reminder',	'3. Mahnung',	7,	'payment',	0),
(16,	'encashment',	'Inkasso',	8,	'payment',	0),
(17,	'open',	'Offen',	0,	'payment',	0),
(18,	'reserved',	'Reserviert',	9,	'payment',	0),
(19,	'delayed',	'Verzoegert',	10,	'payment',	0),
(20,	're_crediting',	'Wiedergutschrift',	11,	'payment',	0),
(21,	'review_necessary',	'Überprüfung notwendig',	12,	'payment',	0),
(30,	'no_credit_approved',	'Es wurde kein Kredit genehmigt.',	30,	'payment',	1),
(31,	'the_credit_has_been_preliminarily_accepted',	'Der Kredit wurde vorlaeufig akzeptiert.',	31,	'payment',	1),
(32,	'the_credit_has_been_accepted',	'Der Kredit wurde genehmigt.',	32,	'payment',	1),
(33,	'the_payment_has_been_ordered',	'Die Zahlung wurde angewiesen.',	33,	'payment',	1),
(34,	'a_time_extension_has_been_registered',	'Es wurde eine Zeitverlaengerung eingetragen.',	34,	'payment',	1),
(35,	'the_process_has_been_cancelled',	'Vorgang wurde abgebrochen.',	35,	'payment',	1);

DROP TABLE IF EXISTS `s_core_subscribes`;
CREATE TABLE `s_core_subscribes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subscribe` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `type` int unsigned NOT NULL,
  `listener` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `pluginID` int unsigned DEFAULT NULL,
  `position` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscribe` (`subscribe`,`type`,`listener`),
  KEY `plugin_namespace_init_storage` (`type`,`subscribe`,`position`),
  KEY `pluginID` (`pluginID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

DROP TABLE IF EXISTS `s_core_tax`;
CREATE TABLE `s_core_tax` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tax` decimal(10,2) NOT NULL,
  `description` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `tax` (`tax`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_tax` (`id`, `tax`, `description`) VALUES
(1,	19.00,	'19%'),
(4,	7.00,	'7 %');

DROP TABLE IF EXISTS `s_core_tax_rules`;
CREATE TABLE `s_core_tax_rules` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `areaID` int unsigned DEFAULT NULL,
  `countryID` int unsigned DEFAULT NULL,
  `stateID` int unsigned DEFAULT NULL,
  `groupID` int unsigned NOT NULL,
  `customer_groupID` int unsigned NOT NULL,
  `tax` decimal(10,2) NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `active` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `groupID` (`groupID`),
  KEY `countryID` (`countryID`),
  KEY `stateID` (`stateID`),
  KEY `areaID` (`areaID`),
  KEY `tax_rate_by_conditions` (`customer_groupID`,`areaID`,`countryID`,`stateID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_core_templates`;
CREATE TABLE `s_core_templates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `template` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `author` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `license` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `esi` tinyint unsigned NOT NULL,
  `style_support` tinyint unsigned NOT NULL,
  `emotion` tinyint unsigned NOT NULL,
  `version` int unsigned NOT NULL,
  `plugin_id` int unsigned DEFAULT NULL,
  `parent_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `basename` (`template`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_templates` (`id`, `template`, `name`, `description`, `author`, `license`, `esi`, `style_support`, `emotion`, `version`, `plugin_id`, `parent_id`) VALUES
(22,	'Responsive',	'__theme_name__',	'__theme_description__',	'__author__',	'__license__',	1,	1,	1,	3,	NULL,	23),
(23,	'Bare',	'__theme_name__',	'__theme_description__',	'__author__',	'__license__',	1,	1,	1,	3,	NULL,	NULL);

DROP TABLE IF EXISTS `s_core_templates_config_elements`;
CREATE TABLE `s_core_templates_config_elements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `template_id` int NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `position` int NOT NULL DEFAULT '0',
  `default_value` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `selection` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `field_label` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `support_text` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `allow_blank` int NOT NULL DEFAULT '1',
  `container_id` int NOT NULL,
  `attributes` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `less_compatible` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `template_id_name` (`template_id`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_templates_config_elements` (`id`, `template_id`, `type`, `name`, `position`, `default_value`, `selection`, `field_label`, `support_text`, `allow_blank`, `container_id`, `attributes`, `less_compatible`) VALUES
(1,	22,	'theme-media-selection',	'mobileLogo',	0,	's:47:\"frontend/_public/src/img/logos/logo--mobile.png\";',	'N;',	'__smartphone__',	NULL,	1,	3,	'a:1:{s:14:\"lessCompatible\";b:0;}',	0),
(2,	22,	'theme-media-selection',	'tabletLogo',	0,	's:47:\"frontend/_public/src/img/logos/logo--tablet.png\";',	'N;',	'__tablet__',	NULL,	1,	3,	'a:1:{s:14:\"lessCompatible\";b:0;}',	0),
(3,	22,	'theme-media-selection',	'tabletLandscapeLogo',	0,	's:47:\"frontend/_public/src/img/logos/logo--tablet.png\";',	'N;',	'__tablet_landscape__',	NULL,	1,	3,	'a:1:{s:14:\"lessCompatible\";b:0;}',	0),
(4,	22,	'theme-media-selection',	'desktopLogo',	0,	's:47:\"frontend/_public/src/img/logos/logo--tablet.png\";',	'N;',	'__desktop__',	NULL,	1,	3,	'a:1:{s:14:\"lessCompatible\";b:0;}',	0),
(5,	22,	'theme-media-selection',	'appleTouchIcon',	0,	's:57:\"frontend/_public/src/img/apple-touch-icon-precomposed.png\";',	'N;',	'__apple_touch_icon__',	NULL,	1,	4,	'a:1:{s:14:\"lessCompatible\";b:0;}',	0),
(6,	22,	'theme-checkbox-field',	'setPrecomposed',	0,	'b:1;',	'N;',	'Precomposed Icon',	NULL,	1,	4,	'a:1:{s:8:\"boxLabel\";s:73:\"Wenn aktiv, wird iOS keine Glaseffekte auf das Apple Touch Icon anwenden.\";}',	1),
(7,	22,	'theme-media-selection',	'win8TileImage',	0,	's:43:\"frontend/_public/src/img/win-tile-image.png\";',	'N;',	'__win8_tile_image__',	NULL,	1,	4,	'a:1:{s:14:\"lessCompatible\";b:0;}',	0),
(8,	22,	'theme-media-selection',	'favicon',	0,	's:36:\"frontend/_public/src/img/favicon.ico\";',	'N;',	'__favicon__',	NULL,	1,	4,	'a:1:{s:14:\"lessCompatible\";b:0;}',	0),
(9,	22,	'theme-checkbox-field',	'offcanvasCart',	0,	'b:1;',	'N;',	'__offcanvas_cart__',	NULL,	1,	6,	'a:1:{s:8:\"boxLabel\";s:51:\"Wenn aktiv, wird der Offcanvas Warenkorb verwendet.\";}',	1),
(10,	22,	'theme-checkbox-field',	'offcanvasOverlayPage',	0,	'b:1;',	'N;',	'__offcanvas_move_method__',	NULL,	1,	6,	'a:1:{s:8:\"boxLabel\";s:106:\"Wenn aktiv, überlagern die Offcanvas Menüs die Shopseite. Andernfalls wird die Shopseite mit verschoben.\";}',	1),
(11,	22,	'theme-checkbox-field',	'focusSearch',	0,	'b:0;',	'N;',	'__focus_search__',	NULL,	1,	6,	'a:1:{s:8:\"boxLabel\";s:93:\"Wenn aktiv, wird die Suche in der mobilen Ansicht auf der Startseite automatisch ausgeklappt.\";}',	1),
(12,	22,	'theme-checkbox-field',	'displaySidebar',	0,	'b:1;',	'N;',	'__display_sidebar__',	NULL,	1,	6,	'a:1:{s:8:\"boxLabel\";s:99:\"Wenn aktiv, wird die Navigation im Produkt-Listing auf der linken Seite in einer Sidebar angezeigt.\";}',	1),
(13,	22,	'theme-checkbox-field',	'sidebarFilter',	0,	'b:0;',	'N;',	'__show_filter_in_sidebar__',	NULL,	1,	6,	'a:1:{s:8:\"boxLabel\";s:132:\"Zeigt die Listing Filter in der Sidebar unter dem Menü an. Wenn die Sidebar nicht aktiv ist werden die Filter auch nicht angezeigt.\";}',	1),
(14,	22,	'theme-checkbox-field',	'checkoutHeader',	0,	'b:1;',	'N;',	'__checkout_header__',	NULL,	1,	6,	'a:1:{s:8:\"boxLabel\";s:142:\"Wenn aktiv, wird ein minimaler Header angezeigt, der den Kunden durch den Checkout führt. Andernfalls wird der normale Shop-Header angezeigt.\";}',	1),
(15,	22,	'theme-checkbox-field',	'checkoutFooter',	0,	'b:1;',	'N;',	'__checkout_footer__',	NULL,	1,	6,	'a:1:{s:8:\"boxLabel\";s:95:\"Wenn aktiv, wird ein minimaler Footer angezeigt, der nur die wichtigsten Informationen anzeigt.\";}',	1),
(16,	22,	'theme-checkbox-field',	'infiniteScrolling',	0,	'b:1;',	'N;',	'__enable_infinite_scrolling__',	NULL,	1,	6,	'a:1:{s:8:\"boxLabel\";s:86:\"Wenn aktiv, wird das Listing live nachgeladen, sobald der Besucher nach unten scrollt.\";}',	1),
(17,	22,	'numberfield',	'infiniteThreshold',	0,	'i:4;',	'N;',	'__infinite_threshold__',	NULL,	1,	6,	'a:1:{s:11:\"supportText\";s:86:\"Anzahl der Seiten, die durch Infinite Scrolling automatisch nachgeladen werden sollen.\";}',	1),
(18,	22,	'theme-select-field',	'lightboxZoomFactor',	0,	'i:0;',	'a:5:{i:0;a:2:{s:5:\"value\";i:0;s:4:\"text\";s:29:\"__lightbox_zoom_factor_auto__\";}i:1;a:2:{s:5:\"value\";i:1;s:4:\"text\";s:29:\"__lightbox_zoom_factor_none__\";}i:2;a:2:{s:5:\"value\";i:2;s:4:\"text\";s:27:\"__lightbox_zoom_factor_2x__\";}i:3;a:2:{s:5:\"value\";i:3;s:4:\"text\";s:27:\"__lightbox_zoom_factor_3x__\";}i:4;a:2:{s:5:\"value\";i:5;s:4:\"text\";s:27:\"__lightbox_zoom_factor_5x__\";}}',	'__lightbox_zoom_factor__',	NULL,	1,	6,	'a:1:{s:11:\"supportText\";s:151:\"Maximaler Zoomfaktor in der Fullscreen-Bildbox (Lightbox). Bei der automatischen Skalierung kann das Bild bis zu seiner Originalgröße gezoomt werden.\";}',	1),
(19,	22,	'theme-text-field',	'appleWebAppTitle',	0,	's:0:\"\";',	'N;',	'__apple_web_app_title__',	NULL,	1,	6,	'a:1:{s:14:\"lessCompatible\";b:0;}',	0),
(20,	22,	'theme-checkbox-field',	'ajaxVariantSwitch',	0,	'b:1;',	'N;',	'__ajax_variant_switch__',	NULL,	1,	6,	'a:2:{s:14:\"lessCompatible\";b:0;s:8:\"boxLabel\";s:93:\"Wenn aktiv, dann werden Varianten per AJAX nachgeladen, statt die komplette Seite neuzuladen.\";}',	0),
(21,	22,	'theme-checkbox-field',	'asyncJavascriptLoading',	0,	'b:1;',	'N;',	'__async_javascript_loading__',	NULL,	1,	6,	'a:2:{s:14:\"lessCompatible\";b:0;s:8:\"boxLabel\";s:60:\"Wenn aktiv, werden die JavaScript Dateien asynchron geladen.\";}',	0),
(22,	22,	'theme-checkbox-field',	'ajaxEmotionLoading',	0,	'b:1;',	'N;',	'__ajax_emotion_loading__',	NULL,	1,	6,	'a:2:{s:14:\"lessCompatible\";b:0;s:8:\"boxLabel\";s:133:\"Wenn aktiv, werden die Einkaufswelten-Inhalte via Ajax asynchron nachgeladen. Wenn inaktiv, werden die Einkaufswelten direkt geladen.\";}',	0),
(23,	22,	'theme-text-area-field',	'additionalCssData',	0,	's:0:\"\";',	'N;',	'__additional_css_data__',	'__additional_css_data_description__',	1,	7,	'a:2:{s:5:\"xtype\";s:8:\"textarea\";s:14:\"lessCompatible\";b:0;}',	0),
(24,	22,	'theme-text-area-field',	'additionalJsLibraries',	0,	's:0:\"\";',	'N;',	'__additional_js_libraries__',	'__additional_js_libraries_description__',	1,	7,	'a:2:{s:5:\"xtype\";s:8:\"textarea\";s:14:\"lessCompatible\";b:0;}',	0),
(25,	22,	'theme-color-picker',	'brand-primary',	0,	's:7:\"#D9400B\";',	'N;',	'@brand-primary',	NULL,	1,	11,	'a:0:{}',	1),
(26,	22,	'theme-color-picker',	'brand-primary-light',	0,	's:41:\"saturate(lighten(@brand-primary,12%), 5%)\";',	'N;',	'@brand-primary-light',	NULL,	1,	11,	'a:0:{}',	1),
(27,	22,	'theme-color-picker',	'brand-secondary',	0,	's:7:\"#5F7285\";',	'N;',	'@brand-secondary',	NULL,	1,	11,	'a:0:{}',	1),
(28,	22,	'theme-color-picker',	'brand-secondary-dark',	0,	's:29:\"darken(@brand-secondary, 15%)\";',	'N;',	'@brand-secondary-dark',	NULL,	1,	11,	'a:0:{}',	1),
(29,	22,	'theme-color-picker',	'gray',	0,	's:7:\"#F5F5F8\";',	'N;',	'@gray',	NULL,	1,	12,	'a:0:{}',	1),
(30,	22,	'theme-color-picker',	'gray-light',	0,	's:18:\"lighten(@gray, 1%)\";',	'N;',	'@gray-light',	NULL,	1,	12,	'a:0:{}',	1),
(31,	22,	'theme-color-picker',	'gray-dark',	0,	's:24:\"darken(@gray-light, 10%)\";',	'N;',	'@gray-dark',	NULL,	1,	12,	'a:0:{}',	1),
(32,	22,	'theme-color-picker',	'border-color',	0,	's:10:\"@gray-dark\";',	'N;',	'@border-color',	NULL,	1,	12,	'a:0:{}',	1),
(33,	22,	'theme-color-picker',	'highlight-success',	0,	's:7:\"#2ECC71\";',	'N;',	'@highlight-success',	NULL,	1,	13,	'a:0:{}',	1),
(34,	22,	'theme-color-picker',	'highlight-error',	0,	's:7:\"#E74C3C\";',	'N;',	'@highlight-error',	NULL,	1,	13,	'a:0:{}',	1),
(35,	22,	'theme-color-picker',	'highlight-notice',	0,	's:7:\"#F1C40F\";',	'N;',	'@highlight-notice',	NULL,	1,	13,	'a:0:{}',	1),
(36,	22,	'theme-color-picker',	'highlight-info',	0,	's:7:\"#4AA3DF\";',	'N;',	'@highlight-info',	NULL,	1,	13,	'a:0:{}',	1),
(37,	22,	'theme-color-picker',	'body-bg',	0,	's:23:\"darken(@gray-light, 5%)\";',	'N;',	'@body-bg',	NULL,	1,	14,	'a:0:{}',	1),
(38,	22,	'theme-color-picker',	'text-color',	0,	's:16:\"@brand-secondary\";',	'N;',	'@text-color',	NULL,	1,	14,	'a:0:{}',	1),
(39,	22,	'theme-color-picker',	'text-color-dark',	0,	's:21:\"@brand-secondary-dark\";',	'N;',	'@text-color-dark',	NULL,	1,	14,	'a:0:{}',	1),
(40,	22,	'theme-color-picker',	'link-color',	0,	's:14:\"@brand-primary\";',	'N;',	'@link-color',	NULL,	1,	14,	'a:0:{}',	1),
(41,	22,	'theme-color-picker',	'link-hover-color',	0,	's:24:\"darken(@link-color, 10%)\";',	'N;',	'@link-hover-color',	NULL,	1,	14,	'a:0:{}',	1),
(42,	22,	'theme-color-picker',	'rating-star-color',	0,	's:17:\"@highlight-notice\";',	'N;',	'@rating-star-color',	NULL,	1,	14,	'a:0:{}',	1),
(43,	22,	'theme-color-picker',	'overlay-bg',	0,	's:7:\"#000000\";',	'N;',	'@overlay-bg',	NULL,	1,	14,	'a:0:{}',	1),
(44,	22,	'theme-color-picker',	'overlay-theme-dark-bg',	0,	's:11:\"@overlay-bg\";',	'N;',	'@overlay-theme-dark-bg',	NULL,	1,	14,	'a:0:{}',	1),
(45,	22,	'theme-color-picker',	'overlay-theme-light-bg',	0,	's:7:\"#FFFFFF\";',	'N;',	'@overlay-theme-light-bg',	NULL,	1,	14,	'a:0:{}',	1),
(46,	22,	'theme-color-picker',	'overlay-opacity',	0,	's:3:\"0.7\";',	'N;',	'@overlay-opacity',	NULL,	1,	14,	'a:0:{}',	1),
(47,	22,	'theme-text-field',	'font-base-stack',	0,	's:77:\"\"Open Sans\", \"Helvetica Neue\", Helvetica, Arial, \"Lucida Grande\", sans-serif;\";',	'N;',	'@font-base-stack',	NULL,	1,	16,	'a:0:{}',	1),
(48,	22,	'theme-text-field',	'font-headline-stack',	0,	's:16:\"@font-base-stack\";',	'N;',	'@font-headline-stack',	NULL,	1,	16,	'a:0:{}',	1),
(49,	22,	'theme-text-field',	'font-size-base',	0,	'i:14;',	'N;',	'@font-size-base',	NULL,	1,	16,	'a:0:{}',	1),
(50,	22,	'theme-text-field',	'font-base-weight',	0,	'i:500;',	'N;',	'@font-base-weight',	NULL,	1,	16,	'a:0:{}',	1),
(51,	22,	'theme-text-field',	'font-light-weight',	0,	'i:300;',	'N;',	'@font-light-weight',	NULL,	1,	16,	'a:0:{}',	1),
(52,	22,	'theme-text-field',	'font-bold-weight',	0,	'i:700;',	'N;',	'@font-bold-weight',	NULL,	1,	16,	'a:0:{}',	1),
(53,	22,	'theme-text-field',	'font-size-h1',	0,	'i:26;',	'N;',	'@font-size-h1',	NULL,	1,	17,	'a:0:{}',	1),
(54,	22,	'theme-text-field',	'font-size-h2',	0,	'i:21;',	'N;',	'@font-size-h2',	NULL,	1,	17,	'a:0:{}',	1),
(55,	22,	'theme-text-field',	'font-size-h3',	0,	'i:18;',	'N;',	'@font-size-h3',	NULL,	1,	17,	'a:0:{}',	1),
(56,	22,	'theme-text-field',	'font-size-h4',	0,	'i:16;',	'N;',	'@font-size-h4',	NULL,	1,	17,	'a:0:{}',	1),
(57,	22,	'theme-text-field',	'font-size-h5',	0,	's:15:\"@font-size-base\";',	'N;',	'@font-size-h5',	NULL,	1,	17,	'a:0:{}',	1),
(58,	22,	'theme-text-field',	'font-size-h6',	0,	'i:12;',	'N;',	'@font-size-h6',	NULL,	1,	17,	'a:0:{}',	1),
(59,	22,	'theme-text-field',	'btn-font-size',	0,	'i:14;',	'N;',	'@btn-font-size',	NULL,	1,	19,	'a:0:{}',	1),
(60,	22,	'theme-text-field',	'btn-icon-size',	0,	'i:10;',	'N;',	'@btn-icon-size',	NULL,	1,	19,	'a:0:{}',	1),
(61,	22,	'theme-color-picker',	'btn-default-top-bg',	0,	's:7:\"#FFFFFF\";',	'N;',	'@btn-default-top-bg',	NULL,	1,	20,	'a:0:{}',	1),
(62,	22,	'theme-color-picker',	'btn-default-bottom-bg',	0,	's:11:\"@gray-light\";',	'N;',	'@btn-default-bottom-bg',	NULL,	1,	20,	'a:0:{}',	1),
(63,	22,	'theme-color-picker',	'btn-default-hover-bg',	0,	's:7:\"#FFFFFF\";',	'N;',	'@btn-default-hover-bg',	NULL,	1,	20,	'a:0:{}',	1),
(64,	22,	'theme-color-picker',	'btn-default-text-color',	0,	's:11:\"@text-color\";',	'N;',	'@btn-default-text-color',	NULL,	1,	20,	'a:0:{}',	1),
(65,	22,	'theme-color-picker',	'btn-default-hover-text-color',	0,	's:14:\"@brand-primary\";',	'N;',	'@btn-default-hover-text-color',	NULL,	1,	20,	'a:0:{}',	1),
(66,	22,	'theme-color-picker',	'btn-default-border-color',	0,	's:13:\"@border-color\";',	'N;',	'@btn-default-border-color',	NULL,	1,	20,	'a:0:{}',	1),
(67,	22,	'theme-color-picker',	'btn-default-hover-border-color',	0,	's:14:\"@brand-primary\";',	'N;',	'@btn-default-hover-border-color',	NULL,	1,	20,	'a:0:{}',	1),
(68,	22,	'theme-color-picker',	'btn-primary-top-bg',	0,	's:20:\"@brand-primary-light\";',	'N;',	'@btn-primary-top-bg',	NULL,	1,	21,	'a:0:{}',	1),
(69,	22,	'theme-color-picker',	'btn-primary-bottom-bg',	0,	's:14:\"@brand-primary\";',	'N;',	'@btn-primary-bottom-bg',	NULL,	1,	21,	'a:0:{}',	1),
(70,	22,	'theme-color-picker',	'btn-primary-hover-bg',	0,	's:14:\"@brand-primary\";',	'N;',	'@btn-primary-hover-bg',	NULL,	1,	21,	'a:0:{}',	1),
(71,	22,	'theme-color-picker',	'btn-primary-text-color',	0,	's:7:\"#FFFFFF\";',	'N;',	'@btn-primary-text-color',	NULL,	1,	21,	'a:0:{}',	1),
(72,	22,	'theme-color-picker',	'btn-primary-hover-text-color',	0,	's:23:\"@btn-primary-text-color\";',	'N;',	'@btn-primary-hover-text-color',	NULL,	1,	21,	'a:0:{}',	1),
(73,	22,	'theme-color-picker',	'btn-secondary-top-bg',	0,	's:16:\"@brand-secondary\";',	'N;',	'@btn-secondary-top-bg',	NULL,	1,	22,	'a:0:{}',	1),
(74,	22,	'theme-color-picker',	'btn-secondary-bottom-bg',	0,	's:21:\"@brand-secondary-dark\";',	'N;',	'@btn-secondary-bottom-bg',	NULL,	1,	22,	'a:0:{}',	1),
(75,	22,	'theme-color-picker',	'btn-secondary-hover-bg',	0,	's:21:\"@brand-secondary-dark\";',	'N;',	'@btn-secondary-hover-bg',	NULL,	1,	22,	'a:0:{}',	1),
(76,	22,	'theme-color-picker',	'btn-secondary-text-color',	0,	's:7:\"#FFFFFF\";',	'N;',	'@btn-secondary-text-color',	NULL,	1,	22,	'a:0:{}',	1),
(77,	22,	'theme-color-picker',	'btn-secondary-hover-text-color',	0,	's:25:\"@btn-secondary-text-color\";',	'N;',	'@btn-secondary-hover-text-color',	NULL,	1,	22,	'a:0:{}',	1),
(78,	22,	'theme-color-picker',	'panel-header-bg',	0,	's:11:\"@gray-light\";',	'N;',	'@panel-header-bg',	NULL,	1,	23,	'a:0:{}',	1),
(79,	22,	'theme-text-field',	'panel-header-font-size',	0,	'i:14;',	'N;',	'@panel-header-font-size',	NULL,	1,	23,	'a:0:{}',	1),
(80,	22,	'theme-color-picker',	'panel-header-color',	0,	's:11:\"@text-color\";',	'N;',	'@panel-header-color',	NULL,	1,	23,	'a:0:{}',	1),
(81,	22,	'theme-color-picker',	'panel-border',	0,	's:13:\"@border-color\";',	'N;',	'@panel-border',	NULL,	1,	23,	'a:0:{}',	1),
(82,	22,	'theme-color-picker',	'panel-bg',	0,	's:7:\"#FFFFFF\";',	'N;',	'@panel-bg',	NULL,	1,	23,	'a:0:{}',	1),
(83,	22,	'theme-text-field',	'label-font-size',	0,	'i:14;',	'N;',	'@label-font-size',	NULL,	1,	25,	'a:0:{}',	1),
(84,	22,	'theme-color-picker',	'label-color',	0,	's:11:\"@text-color\";',	'N;',	'@label-color',	NULL,	1,	25,	'a:0:{}',	1),
(85,	22,	'theme-text-field',	'input-font-size',	0,	'i:14;',	'N;',	'@input-font-size',	NULL,	1,	26,	'a:0:{}',	1),
(86,	22,	'theme-color-picker',	'input-bg',	0,	's:11:\"@gray-light\";',	'N;',	'@input-bg',	NULL,	1,	26,	'a:0:{}',	1),
(87,	22,	'theme-color-picker',	'input-color',	0,	's:16:\"@brand-secondary\";',	'N;',	'@input-color',	NULL,	1,	26,	'a:0:{}',	1),
(88,	22,	'theme-color-picker',	'input-placeholder-color',	0,	's:25:\"lighten(@text-color, 15%)\";',	'N;',	'@input-placeholder-color',	NULL,	1,	26,	'a:0:{}',	1),
(89,	22,	'theme-color-picker',	'input-border',	0,	's:13:\"@border-color\";',	'N;',	'@input-border',	NULL,	1,	26,	'a:0:{}',	1),
(90,	22,	'theme-color-picker',	'input-focus-bg',	0,	's:7:\"#FFFFFF\";',	'N;',	'@input-focus-bg',	NULL,	1,	27,	'a:0:{}',	1),
(91,	22,	'theme-color-picker',	'input-focus-border',	0,	's:14:\"@brand-primary\";',	'N;',	'@input-focus-border',	NULL,	1,	27,	'a:0:{}',	1),
(92,	22,	'theme-color-picker',	'input-focus-color',	0,	's:16:\"@brand-secondary\";',	'N;',	'@input-focus-color',	NULL,	1,	27,	'a:0:{}',	1),
(93,	22,	'theme-color-picker',	'input-error-bg',	0,	's:47:\"desaturate(lighten(@highlight-error, 38%), 20%)\";',	'N;',	'@input-error-bg',	NULL,	1,	27,	'a:0:{}',	1),
(94,	22,	'theme-color-picker',	'input-error-border',	0,	's:16:\"@highlight-error\";',	'N;',	'@input-error-border',	NULL,	1,	27,	'a:0:{}',	1),
(95,	22,	'theme-color-picker',	'input-error-color',	0,	's:16:\"@highlight-error\";',	'N;',	'@input-error-color',	NULL,	1,	27,	'a:0:{}',	1),
(96,	22,	'theme-color-picker',	'input-success-bg',	0,	's:7:\"#FFFFFF\";',	'N;',	'@input-success-bg',	NULL,	1,	27,	'a:0:{}',	1),
(97,	22,	'theme-color-picker',	'input-success-border',	0,	's:18:\"@highlight-success\";',	'N;',	'@input-success-border',	NULL,	1,	27,	'a:0:{}',	1),
(98,	22,	'theme-color-picker',	'input-success-color',	0,	's:21:\"@brand-secondary-dark\";',	'N;',	'@input-success-color',	NULL,	1,	27,	'a:0:{}',	1),
(99,	22,	'theme-color-picker',	'panel-table-header-bg',	0,	's:9:\"@panel-bg\";',	'N;',	'@panel-table-header-bg',	NULL,	1,	29,	'a:0:{}',	1),
(100,	22,	'theme-color-picker',	'panel-table-header-color',	0,	's:16:\"@text-color-dark\";',	'N;',	'@panel-table-header-color',	NULL,	1,	29,	'a:0:{}',	1),
(101,	22,	'theme-color-picker',	'table-row-bg',	0,	's:7:\"#FFFFFF\";',	'N;',	'@table-row-bg',	NULL,	1,	29,	'a:0:{}',	1),
(102,	22,	'theme-color-picker',	'table-row-color',	0,	's:16:\"@brand-secondary\";',	'N;',	'@table-row-color',	NULL,	1,	29,	'a:0:{}',	1),
(103,	22,	'theme-color-picker',	'table-row-highlight-bg',	0,	's:25:\"darken(@table-row-bg, 4%)\";',	'N;',	'@table-row-highlight-bg',	NULL,	1,	29,	'a:0:{}',	1),
(104,	22,	'theme-color-picker',	'table-header-bg',	0,	's:16:\"@brand-secondary\";',	'N;',	'@table-header-bg',	NULL,	1,	29,	'a:0:{}',	1),
(105,	22,	'theme-color-picker',	'table-header-color',	0,	's:7:\"#FFFFFF\";',	'N;',	'@table-header-color',	NULL,	1,	29,	'a:0:{}',	1),
(106,	22,	'theme-color-picker',	'badge-discount-bg',	0,	's:16:\"@highlight-error\";',	'N;',	'@badge-discount-bg',	NULL,	1,	30,	'a:0:{}',	1),
(107,	22,	'theme-color-picker',	'badge-discount-color',	0,	's:7:\"#FFFFFF\";',	'N;',	'@badge-discount-color',	NULL,	1,	30,	'a:0:{}',	1),
(108,	22,	'theme-color-picker',	'badge-newcomer-bg',	0,	's:17:\"@highlight-notice\";',	'N;',	'@badge-newcomer-bg',	NULL,	1,	30,	'a:0:{}',	1),
(109,	22,	'theme-color-picker',	'badge-newcomer-color',	0,	's:7:\"#FFFFFF\";',	'N;',	'@badge-newcomer-color',	NULL,	1,	30,	'a:0:{}',	1),
(110,	22,	'theme-color-picker',	'badge-recommendation-bg',	0,	's:18:\"@highlight-success\";',	'N;',	'@badge-recommendation-bg',	NULL,	1,	30,	'a:0:{}',	1),
(111,	22,	'theme-color-picker',	'badge-recommendation-color',	0,	's:7:\"#FFFFFF\";',	'N;',	'@badge-recommendation-color',	NULL,	1,	30,	'a:0:{}',	1),
(112,	22,	'theme-color-picker',	'badge-download-bg',	0,	's:15:\"@highlight-info\";',	'N;',	'@badge-download-bg',	NULL,	1,	30,	'a:0:{}',	1),
(113,	22,	'theme-color-picker',	'badge-download-color',	0,	's:7:\"#FFFFFF\";',	'N;',	'@badge-download-color',	NULL,	1,	30,	'a:0:{}',	1),
(114,	23,	'theme-media-selection',	'mobileLogo',	0,	's:47:\"frontend/_public/src/img/logos/logo--mobile.png\";',	'N;',	'__smartphone__',	NULL,	1,	33,	'a:1:{s:14:\"lessCompatible\";b:0;}',	0),
(115,	23,	'theme-media-selection',	'tabletLogo',	0,	's:47:\"frontend/_public/src/img/logos/logo--tablet.png\";',	'N;',	'__tablet__',	NULL,	1,	33,	'a:1:{s:14:\"lessCompatible\";b:0;}',	0),
(116,	23,	'theme-media-selection',	'tabletLandscapeLogo',	0,	's:47:\"frontend/_public/src/img/logos/logo--tablet.png\";',	'N;',	'__tablet_landscape__',	NULL,	1,	33,	'a:1:{s:14:\"lessCompatible\";b:0;}',	0),
(117,	23,	'theme-media-selection',	'desktopLogo',	0,	's:47:\"frontend/_public/src/img/logos/logo--tablet.png\";',	'N;',	'__desktop__',	NULL,	1,	33,	'a:1:{s:14:\"lessCompatible\";b:0;}',	0),
(118,	23,	'theme-media-selection',	'appleTouchIcon',	0,	's:57:\"frontend/_public/src/img/apple-touch-icon-precomposed.png\";',	'N;',	'__apple_touch_icon__',	NULL,	1,	34,	'a:1:{s:14:\"lessCompatible\";b:0;}',	0),
(119,	23,	'theme-checkbox-field',	'setPrecomposed',	0,	'b:1;',	'N;',	'Precomposed Icon',	NULL,	1,	34,	'a:1:{s:8:\"boxLabel\";s:73:\"Wenn aktiv, wird iOS keine Glaseffekte auf das Apple Touch Icon anwenden.\";}',	1),
(120,	23,	'theme-media-selection',	'win8TileImage',	0,	's:43:\"frontend/_public/src/img/win-tile-image.png\";',	'N;',	'__win8_tile_image__',	NULL,	1,	34,	'a:1:{s:14:\"lessCompatible\";b:0;}',	0),
(121,	23,	'theme-media-selection',	'favicon',	0,	's:36:\"frontend/_public/src/img/favicon.ico\";',	'N;',	'__favicon__',	NULL,	1,	34,	'a:1:{s:14:\"lessCompatible\";b:0;}',	0);

DROP TABLE IF EXISTS `s_core_templates_config_layout`;
CREATE TABLE `s_core_templates_config_layout` (
  `id` int NOT NULL AUTO_INCREMENT,
  `parent_id` int DEFAULT NULL,
  `template_id` int NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `attributes` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_templates_config_layout` (`id`, `parent_id`, `template_id`, `type`, `name`, `title`, `attributes`) VALUES
(1,	NULL,	22,	'theme-tab-panel',	'main_container',	NULL,	'a:0:{}'),
(2,	1,	22,	'theme-tab',	'bareMain',	'__bare_tab_header__',	'a:3:{s:6:\"layout\";s:6:\"anchor\";s:10:\"autoScroll\";b:1;s:7:\"padding\";s:1:\"0\";}'),
(3,	2,	22,	'theme-field-set',	'bareLogos',	'__logos__',	'a:4:{s:7:\"padding\";s:2:\"10\";s:6:\"margin\";s:1:\"5\";s:6:\"layout\";s:6:\"anchor\";s:8:\"defaults\";a:2:{s:10:\"labelWidth\";i:155;s:6:\"anchor\";s:4:\"100%\";}}'),
(4,	2,	22,	'theme-field-set',	'Icons',	'__icons__',	'a:4:{s:7:\"padding\";s:2:\"10\";s:6:\"margin\";s:1:\"5\";s:6:\"layout\";s:6:\"anchor\";s:8:\"defaults\";a:2:{s:10:\"labelWidth\";i:155;s:6:\"anchor\";s:4:\"100%\";}}'),
(5,	1,	22,	'theme-tab',	'responsiveMain',	'__responsive_tab_header__',	'a:4:{s:6:\"layout\";s:6:\"anchor\";s:10:\"autoScroll\";b:1;s:7:\"padding\";s:1:\"0\";s:8:\"defaults\";a:1:{s:6:\"anchor\";s:4:\"100%\";}}'),
(6,	5,	22,	'theme-field-set',	'bareGlobal',	'__global_configuration__',	'a:4:{s:7:\"padding\";s:2:\"10\";s:6:\"margin\";s:1:\"5\";s:6:\"layout\";s:6:\"anchor\";s:8:\"defaults\";a:2:{s:10:\"labelWidth\";i:155;s:6:\"anchor\";s:4:\"100%\";}}'),
(7,	5,	22,	'theme-field-set',	'responsiveGlobal',	'__advanced_settings__',	'a:4:{s:7:\"padding\";s:2:\"10\";s:6:\"margin\";s:1:\"5\";s:6:\"layout\";s:6:\"anchor\";s:8:\"defaults\";a:2:{s:6:\"anchor\";s:4:\"100%\";s:10:\"labelWidth\";i:155;}}'),
(8,	1,	22,	'theme-tab',	'responsive_tab',	'__responsive_colors__',	'a:0:{}'),
(9,	8,	22,	'theme-tab-panel',	'bottom_tab_panel',	NULL,	'a:1:{s:5:\"plain\";b:1;}'),
(10,	9,	22,	'theme-tab',	'general_tab',	'__responsive_tab_general__',	'a:1:{s:10:\"autoScroll\";b:1;}'),
(11,	10,	22,	'theme-field-set',	'basic_field_set',	'__responsive_tab_general_fieldset_base__',	'a:4:{s:6:\"layout\";s:6:\"column\";s:6:\"height\";i:130;s:4:\"flex\";i:0;s:8:\"defaults\";a:3:{s:11:\"columnWidth\";d:0.5;s:10:\"labelWidth\";i:180;s:6:\"margin\";s:8:\"3 16 3 0\";}}'),
(12,	10,	22,	'theme-field-set',	'grey_tones',	'__responsive_tab_general_fieldset_grey__',	'a:4:{s:6:\"layout\";s:6:\"column\";s:6:\"height\";i:130;s:4:\"flex\";i:0;s:8:\"defaults\";a:3:{s:11:\"columnWidth\";d:0.5;s:10:\"labelWidth\";i:180;s:6:\"margin\";s:8:\"3 16 3 0\";}}'),
(13,	10,	22,	'theme-field-set',	'highlight_colors',	'__responsive_tab_general_fieldset_highlight__',	'a:4:{s:6:\"layout\";s:6:\"column\";s:6:\"height\";i:130;s:4:\"flex\";i:0;s:8:\"defaults\";a:3:{s:11:\"columnWidth\";d:0.5;s:10:\"labelWidth\";i:180;s:6:\"margin\";s:8:\"3 16 3 0\";}}'),
(14,	10,	22,	'theme-field-set',	'scaffolding',	'__responsive_tab_general_fieldset_scaffolding__',	'a:4:{s:6:\"layout\";s:6:\"column\";s:6:\"height\";i:220;s:4:\"flex\";i:0;s:8:\"defaults\";a:3:{s:11:\"columnWidth\";d:0.5;s:10:\"labelWidth\";i:180;s:6:\"margin\";s:8:\"3 16 3 0\";}}'),
(15,	9,	22,	'theme-tab',	'typo_tab',	'__responsive_tab_typo__',	'a:1:{s:10:\"autoScroll\";b:1;}'),
(16,	15,	22,	'theme-field-set',	'typo_base',	'__responsive_tab_typo_fieldset_base__',	'a:4:{s:6:\"layout\";s:6:\"column\";s:6:\"height\";i:170;s:4:\"flex\";i:0;s:8:\"defaults\";a:3:{s:11:\"columnWidth\";d:0.5;s:10:\"labelWidth\";i:180;s:6:\"margin\";s:8:\"3 16 3 0\";}}'),
(17,	15,	22,	'theme-field-set',	'typo_headlines',	'__responsive_tab_typo_fieldset_headlines__',	'a:4:{s:6:\"layout\";s:6:\"column\";s:6:\"height\";i:170;s:4:\"flex\";i:0;s:8:\"defaults\";a:3:{s:11:\"columnWidth\";d:0.5;s:10:\"labelWidth\";i:180;s:6:\"margin\";s:8:\"3 16 3 0\";}}'),
(18,	9,	22,	'theme-tab',	'buttons_tab',	'__responsive_tab_buttons__',	'a:1:{s:10:\"autoScroll\";b:1;}'),
(19,	18,	22,	'theme-field-set',	'buttons_fieldset',	'__responsive_tab_buttons_fieldset_global__',	'a:4:{s:6:\"layout\";s:6:\"column\";s:6:\"height\";i:90;s:4:\"flex\";i:0;s:8:\"defaults\";a:3:{s:11:\"columnWidth\";d:0.5;s:10:\"labelWidth\";i:180;s:6:\"margin\";s:8:\"3 16 3 0\";}}'),
(20,	18,	22,	'theme-field-set',	'buttons_default_fieldset',	'__responsive_tab_buttons_fieldset_default__',	'a:4:{s:6:\"layout\";s:6:\"column\";s:6:\"height\";i:200;s:4:\"flex\";i:0;s:8:\"defaults\";a:3:{s:11:\"columnWidth\";d:0.5;s:10:\"labelWidth\";i:180;s:6:\"margin\";s:8:\"3 16 3 0\";}}'),
(21,	18,	22,	'theme-field-set',	'buttons_primary_fieldset',	'__responsive_tab_buttons_fieldset_primary__',	'a:4:{s:6:\"layout\";s:6:\"column\";s:6:\"height\";i:170;s:4:\"flex\";i:0;s:8:\"defaults\";a:3:{s:11:\"columnWidth\";d:0.5;s:10:\"labelWidth\";i:180;s:6:\"margin\";s:8:\"3 16 3 0\";}}'),
(22,	18,	22,	'theme-field-set',	'buttons_secondary_fieldset',	'__responsive_tab_buttons_fieldset_secondary__',	'a:4:{s:6:\"layout\";s:6:\"column\";s:6:\"height\";i:170;s:4:\"flex\";i:0;s:8:\"defaults\";a:3:{s:11:\"columnWidth\";d:0.5;s:10:\"labelWidth\";i:180;s:6:\"margin\";s:8:\"3 16 3 0\";}}'),
(23,	18,	22,	'theme-field-set',	'panels_fieldset',	'__responsive_tab_buttons_fieldset_panels__',	'a:4:{s:6:\"layout\";s:6:\"column\";s:6:\"height\";i:170;s:4:\"flex\";i:0;s:8:\"defaults\";a:3:{s:11:\"columnWidth\";d:0.5;s:10:\"labelWidth\";i:180;s:6:\"margin\";s:8:\"3 16 3 0\";}}'),
(24,	9,	22,	'theme-tab',	'forms_tab',	'__responsive_tab_forms__',	'a:1:{s:10:\"autoScroll\";b:1;}'),
(25,	24,	22,	'theme-field-set',	'labels_fieldset',	'__responsive_tab_forms_fieldset_labels__',	'a:4:{s:6:\"layout\";s:6:\"column\";s:6:\"height\";i:90;s:4:\"flex\";i:0;s:8:\"defaults\";a:3:{s:11:\"columnWidth\";d:0.5;s:10:\"labelWidth\";i:180;s:6:\"margin\";s:8:\"3 16 3 0\";}}'),
(26,	24,	22,	'theme-field-set',	'form_base_fieldset',	'__responsive_tab_forms_fieldset_global__',	'a:4:{s:6:\"layout\";s:6:\"column\";s:6:\"height\";i:160;s:4:\"flex\";i:0;s:8:\"defaults\";a:3:{s:11:\"columnWidth\";d:0.5;s:10:\"labelWidth\";i:180;s:6:\"margin\";s:8:\"3 16 3 0\";}}'),
(27,	24,	22,	'theme-field-set',	'form_states_fieldset',	'__responsive_tab_forms_fieldset_states__',	'a:4:{s:6:\"layout\";s:6:\"column\";s:6:\"height\";i:240;s:4:\"flex\";i:0;s:8:\"defaults\";a:3:{s:11:\"columnWidth\";d:0.5;s:10:\"labelWidth\";i:180;s:6:\"margin\";s:8:\"3 16 3 0\";}}'),
(28,	9,	22,	'theme-tab',	'tables_tab',	'__responsive_tab_tables__',	'a:1:{s:10:\"autoScroll\";b:1;}'),
(29,	28,	22,	'theme-field-set',	'tables_fieldset',	'__responsive_tab_tables_fieldset_tables__',	'a:4:{s:6:\"layout\";s:6:\"column\";s:6:\"height\";i:200;s:4:\"flex\";i:0;s:8:\"defaults\";a:3:{s:11:\"columnWidth\";d:0.5;s:10:\"labelWidth\";i:180;s:6:\"margin\";s:8:\"3 16 3 0\";}}'),
(30,	28,	22,	'theme-field-set',	'badges_fieldset',	'__responsive_tab_tables_fieldset_badges__',	'a:4:{s:6:\"layout\";s:6:\"column\";s:6:\"height\";i:200;s:4:\"flex\";i:0;s:8:\"defaults\";a:3:{s:11:\"columnWidth\";d:0.5;s:10:\"labelWidth\";i:180;s:6:\"margin\";s:8:\"3 16 3 0\";}}'),
(31,	NULL,	23,	'theme-tab-panel',	'main_container',	NULL,	'a:0:{}'),
(32,	31,	23,	'theme-tab',	'bareMain',	'__bare_tab_header__',	'a:3:{s:6:\"layout\";s:6:\"anchor\";s:10:\"autoScroll\";b:1;s:7:\"padding\";s:1:\"0\";}'),
(33,	32,	23,	'theme-field-set',	'bareLogos',	'__logos__',	'a:4:{s:7:\"padding\";s:2:\"10\";s:6:\"margin\";s:1:\"5\";s:6:\"layout\";s:6:\"anchor\";s:8:\"defaults\";a:2:{s:10:\"labelWidth\";i:155;s:6:\"anchor\";s:4:\"100%\";}}'),
(34,	32,	23,	'theme-field-set',	'Icons',	'__icons__',	'a:4:{s:7:\"padding\";s:2:\"10\";s:6:\"margin\";s:1:\"5\";s:6:\"layout\";s:6:\"anchor\";s:8:\"defaults\";a:2:{s:10:\"labelWidth\";i:155;s:6:\"anchor\";s:4:\"100%\";}}');

DROP TABLE IF EXISTS `s_core_templates_config_set`;
CREATE TABLE `s_core_templates_config_set` (
  `id` int NOT NULL AUTO_INCREMENT,
  `template_id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `element_values` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `template_id` (`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_templates_config_set` (`id`, `template_id`, `name`, `description`, `element_values`) VALUES
(1,	22,	'__color_scheme_turquoise__',	'__color_scheme_turquoise_description__',	'a:72:{s:13:\"brand-primary\";s:7:\"#1db3b8\";s:19:\"brand-primary-light\";s:27:\"lighten(@brand-primary, 5%)\";s:15:\"brand-secondary\";s:7:\"#5F7285\";s:20:\"brand-secondary-dark\";s:29:\"darken(@brand-secondary, 15%)\";s:4:\"gray\";s:7:\"#F5F5F8\";s:10:\"gray-light\";s:18:\"lighten(@gray, 1%)\";s:9:\"gray-dark\";s:24:\"darken(@gray-light, 10%)\";s:12:\"border-color\";s:10:\"@gray-dark\";s:17:\"highlight-success\";s:7:\"#2ECC71\";s:15:\"highlight-error\";s:7:\"#E74C3C\";s:16:\"highlight-notice\";s:7:\"#F1C40F\";s:14:\"highlight-info\";s:7:\"#4AA3DF\";s:7:\"body-bg\";s:23:\"darken(@gray-light, 5%)\";s:10:\"overlay-bg\";s:7:\"#000000\";s:15:\"overlay-dark-bg\";s:11:\"@overlay-bg\";s:16:\"overlay-light-bg\";s:7:\"#FFFFFF\";s:15:\"overlay-opacity\";s:3:\"0.7\";s:10:\"text-color\";s:16:\"@brand-secondary\";s:15:\"text-color-dark\";s:21:\"@brand-secondary-dark\";s:10:\"link-color\";s:14:\"@brand-primary\";s:16:\"link-hover-color\";s:24:\"darken(@link-color, 10%)\";s:17:\"rating-star-color\";s:17:\"@highlight-notice\";s:18:\"btn-default-top-bg\";s:7:\"#FFFFFF\";s:21:\"btn-default-bottom-bg\";s:11:\"@gray-light\";s:20:\"btn-default-hover-bg\";s:7:\"#FFFFFF\";s:22:\"btn-default-text-color\";s:11:\"@text-color\";s:28:\"btn-default-hover-text-color\";s:14:\"@brand-primary\";s:24:\"btn-default-border-color\";s:13:\"@border-color\";s:30:\"btn-default-hover-border-color\";s:14:\"@brand-primary\";s:18:\"btn-primary-top-bg\";s:20:\"@brand-primary-light\";s:21:\"btn-primary-bottom-bg\";s:14:\"@brand-primary\";s:20:\"btn-primary-hover-bg\";s:14:\"@brand-primary\";s:22:\"btn-primary-text-color\";s:7:\"#FFFFFF\";s:28:\"btn-primary-hover-text-color\";s:23:\"@btn-primary-text-color\";s:20:\"btn-secondary-top-bg\";s:16:\"@brand-secondary\";s:23:\"btn-secondary-bottom-bg\";s:21:\"@brand-secondary-dark\";s:22:\"btn-secondary-hover-bg\";s:21:\"@brand-secondary-dark\";s:24:\"btn-secondary-text-color\";s:7:\"#FFFFFF\";s:30:\"btn-secondary-hover-text-color\";s:25:\"@btn-secondary-text-color\";s:15:\"panel-header-bg\";s:11:\"@gray-light\";s:18:\"panel-header-color\";s:11:\"@text-color\";s:12:\"panel-border\";s:13:\"@border-color\";s:8:\"panel-bg\";s:7:\"#FFFFFF\";s:11:\"label-color\";s:11:\"@text-color\";s:8:\"input-bg\";s:11:\"@gray-light\";s:11:\"input-color\";s:16:\"@brand-secondary\";s:23:\"input-placeholder-color\";s:25:\"lighten(@text-color, 15%)\";s:12:\"input-border\";s:13:\"@border-color\";s:14:\"input-focus-bg\";s:7:\"#FFFFFF\";s:18:\"input-focus-border\";s:14:\"@brand-primary\";s:17:\"input-focus-color\";s:16:\"@brand-secondary\";s:14:\"input-error-bg\";s:47:\"desaturate(lighten(@highlight-error, 38%), 20%)\";s:18:\"input-error-border\";s:16:\"@highlight-error\";s:17:\"input-error-color\";s:16:\"@highlight-error\";s:16:\"input-success-bg\";s:7:\"#FFFFFF\";s:20:\"input-success-border\";s:18:\"@highlight-success\";s:19:\"input-success-color\";s:21:\"@brand-secondary-dark\";s:21:\"panel-table-header-bg\";s:9:\"@panel-bg\";s:24:\"panel-table-header-color\";s:16:\"@text-color-dark\";s:12:\"table-row-bg\";s:7:\"#FFFFFF\";s:15:\"table-row-color\";s:16:\"@brand-secondary\";s:22:\"table-row-highlight-bg\";s:25:\"darken(@table-row-bg, 4%)\";s:15:\"table-header-bg\";s:16:\"@brand-secondary\";s:18:\"table-header-color\";s:7:\"#FFFFFF\";s:17:\"badge-discount-bg\";s:16:\"@highlight-error\";s:20:\"badge-discount-color\";s:7:\"#FFFFFF\";s:17:\"badge-newcomer-bg\";s:17:\"@highlight-notice\";s:20:\"badge-newcomer-color\";s:7:\"#FFFFFF\";s:23:\"badge-recommendation-bg\";s:18:\"@highlight-success\";s:26:\"badge-recommendation-color\";s:7:\"#FFFFFF\";s:17:\"badge-download-bg\";s:15:\"@highlight-info\";s:20:\"badge-download-color\";s:7:\"#FFFFFF\";}'),
(2,	22,	'__color_scheme_green__',	'__color_scheme_green_description__',	'a:72:{s:13:\"brand-primary\";s:7:\"#72a425\";s:19:\"brand-primary-light\";s:41:\"saturate(lighten(@brand-primary, 5%), 5%)\";s:15:\"brand-secondary\";s:7:\"#5F7285\";s:20:\"brand-secondary-dark\";s:29:\"darken(@brand-secondary, 15%)\";s:4:\"gray\";s:7:\"#F5F5F8\";s:10:\"gray-light\";s:18:\"lighten(@gray, 1%)\";s:9:\"gray-dark\";s:24:\"darken(@gray-light, 10%)\";s:12:\"border-color\";s:10:\"@gray-dark\";s:17:\"highlight-success\";s:7:\"#2ECC71\";s:15:\"highlight-error\";s:7:\"#E74C3C\";s:16:\"highlight-notice\";s:7:\"#F1C40F\";s:14:\"highlight-info\";s:7:\"#4AA3DF\";s:7:\"body-bg\";s:23:\"darken(@gray-light, 5%)\";s:10:\"overlay-bg\";s:7:\"#000000\";s:15:\"overlay-dark-bg\";s:11:\"@overlay-bg\";s:16:\"overlay-light-bg\";s:7:\"#FFFFFF\";s:15:\"overlay-opacity\";s:3:\"0.7\";s:10:\"text-color\";s:16:\"@brand-secondary\";s:15:\"text-color-dark\";s:21:\"@brand-secondary-dark\";s:10:\"link-color\";s:14:\"@brand-primary\";s:16:\"link-hover-color\";s:24:\"darken(@link-color, 10%)\";s:17:\"rating-star-color\";s:17:\"@highlight-notice\";s:18:\"btn-default-top-bg\";s:7:\"#FFFFFF\";s:21:\"btn-default-bottom-bg\";s:11:\"@gray-light\";s:20:\"btn-default-hover-bg\";s:7:\"#FFFFFF\";s:22:\"btn-default-text-color\";s:11:\"@text-color\";s:28:\"btn-default-hover-text-color\";s:14:\"@brand-primary\";s:24:\"btn-default-border-color\";s:13:\"@border-color\";s:30:\"btn-default-hover-border-color\";s:14:\"@brand-primary\";s:18:\"btn-primary-top-bg\";s:20:\"@brand-primary-light\";s:21:\"btn-primary-bottom-bg\";s:14:\"@brand-primary\";s:20:\"btn-primary-hover-bg\";s:14:\"@brand-primary\";s:22:\"btn-primary-text-color\";s:7:\"#FFFFFF\";s:28:\"btn-primary-hover-text-color\";s:23:\"@btn-primary-text-color\";s:20:\"btn-secondary-top-bg\";s:16:\"@brand-secondary\";s:23:\"btn-secondary-bottom-bg\";s:21:\"@brand-secondary-dark\";s:22:\"btn-secondary-hover-bg\";s:21:\"@brand-secondary-dark\";s:24:\"btn-secondary-text-color\";s:7:\"#FFFFFF\";s:30:\"btn-secondary-hover-text-color\";s:25:\"@btn-secondary-text-color\";s:15:\"panel-header-bg\";s:11:\"@gray-light\";s:18:\"panel-header-color\";s:11:\"@text-color\";s:12:\"panel-border\";s:13:\"@border-color\";s:8:\"panel-bg\";s:7:\"#FFFFFF\";s:11:\"label-color\";s:11:\"@text-color\";s:8:\"input-bg\";s:11:\"@gray-light\";s:11:\"input-color\";s:16:\"@brand-secondary\";s:23:\"input-placeholder-color\";s:25:\"lighten(@text-color, 15%)\";s:12:\"input-border\";s:13:\"@border-color\";s:14:\"input-focus-bg\";s:7:\"#FFFFFF\";s:18:\"input-focus-border\";s:14:\"@brand-primary\";s:17:\"input-focus-color\";s:16:\"@brand-secondary\";s:14:\"input-error-bg\";s:47:\"desaturate(lighten(@highlight-error, 38%), 20%)\";s:18:\"input-error-border\";s:16:\"@highlight-error\";s:17:\"input-error-color\";s:16:\"@highlight-error\";s:16:\"input-success-bg\";s:7:\"#FFFFFF\";s:20:\"input-success-border\";s:18:\"@highlight-success\";s:19:\"input-success-color\";s:21:\"@brand-secondary-dark\";s:21:\"panel-table-header-bg\";s:9:\"@panel-bg\";s:24:\"panel-table-header-color\";s:16:\"@text-color-dark\";s:12:\"table-row-bg\";s:7:\"#FFFFFF\";s:15:\"table-row-color\";s:16:\"@brand-secondary\";s:22:\"table-row-highlight-bg\";s:25:\"darken(@table-row-bg, 4%)\";s:15:\"table-header-bg\";s:16:\"@brand-secondary\";s:18:\"table-header-color\";s:7:\"#FFFFFF\";s:17:\"badge-discount-bg\";s:16:\"@highlight-error\";s:20:\"badge-discount-color\";s:7:\"#FFFFFF\";s:17:\"badge-newcomer-bg\";s:17:\"@highlight-notice\";s:20:\"badge-newcomer-color\";s:7:\"#FFFFFF\";s:23:\"badge-recommendation-bg\";s:18:\"@highlight-success\";s:26:\"badge-recommendation-color\";s:7:\"#FFFFFF\";s:17:\"badge-download-bg\";s:15:\"@highlight-info\";s:20:\"badge-download-color\";s:7:\"#FFFFFF\";}'),
(3,	22,	'__color_scheme_red__',	'__color_scheme_red_description__',	'a:72:{s:13:\"brand-primary\";s:7:\"#be0a30\";s:19:\"brand-primary-light\";s:42:\"saturate(lighten(@brand-primary, 10%), 5%)\";s:15:\"brand-secondary\";s:7:\"#5F7285\";s:20:\"brand-secondary-dark\";s:29:\"darken(@brand-secondary, 15%)\";s:4:\"gray\";s:7:\"#F5F5F8\";s:10:\"gray-light\";s:18:\"lighten(@gray, 1%)\";s:9:\"gray-dark\";s:24:\"darken(@gray-light, 10%)\";s:12:\"border-color\";s:10:\"@gray-dark\";s:17:\"highlight-success\";s:7:\"#2ECC71\";s:15:\"highlight-error\";s:7:\"#E74C3C\";s:16:\"highlight-notice\";s:7:\"#F1C40F\";s:14:\"highlight-info\";s:7:\"#4AA3DF\";s:7:\"body-bg\";s:23:\"darken(@gray-light, 5%)\";s:10:\"overlay-bg\";s:7:\"#000000\";s:15:\"overlay-dark-bg\";s:11:\"@overlay-bg\";s:16:\"overlay-light-bg\";s:7:\"#FFFFFF\";s:15:\"overlay-opacity\";s:3:\"0.7\";s:10:\"text-color\";s:16:\"@brand-secondary\";s:15:\"text-color-dark\";s:21:\"@brand-secondary-dark\";s:10:\"link-color\";s:14:\"@brand-primary\";s:16:\"link-hover-color\";s:24:\"darken(@link-color, 10%)\";s:17:\"rating-star-color\";s:17:\"@highlight-notice\";s:18:\"btn-default-top-bg\";s:7:\"#FFFFFF\";s:21:\"btn-default-bottom-bg\";s:11:\"@gray-light\";s:20:\"btn-default-hover-bg\";s:7:\"#FFFFFF\";s:22:\"btn-default-text-color\";s:11:\"@text-color\";s:28:\"btn-default-hover-text-color\";s:14:\"@brand-primary\";s:24:\"btn-default-border-color\";s:13:\"@border-color\";s:30:\"btn-default-hover-border-color\";s:14:\"@brand-primary\";s:18:\"btn-primary-top-bg\";s:20:\"@brand-primary-light\";s:21:\"btn-primary-bottom-bg\";s:14:\"@brand-primary\";s:20:\"btn-primary-hover-bg\";s:14:\"@brand-primary\";s:22:\"btn-primary-text-color\";s:7:\"#FFFFFF\";s:28:\"btn-primary-hover-text-color\";s:23:\"@btn-primary-text-color\";s:20:\"btn-secondary-top-bg\";s:16:\"@brand-secondary\";s:23:\"btn-secondary-bottom-bg\";s:21:\"@brand-secondary-dark\";s:22:\"btn-secondary-hover-bg\";s:21:\"@brand-secondary-dark\";s:24:\"btn-secondary-text-color\";s:7:\"#FFFFFF\";s:30:\"btn-secondary-hover-text-color\";s:25:\"@btn-secondary-text-color\";s:15:\"panel-header-bg\";s:11:\"@gray-light\";s:18:\"panel-header-color\";s:11:\"@text-color\";s:12:\"panel-border\";s:13:\"@border-color\";s:8:\"panel-bg\";s:7:\"#FFFFFF\";s:11:\"label-color\";s:11:\"@text-color\";s:8:\"input-bg\";s:11:\"@gray-light\";s:11:\"input-color\";s:16:\"@brand-secondary\";s:23:\"input-placeholder-color\";s:25:\"lighten(@text-color, 15%)\";s:12:\"input-border\";s:13:\"@border-color\";s:14:\"input-focus-bg\";s:7:\"#FFFFFF\";s:18:\"input-focus-border\";s:14:\"@brand-primary\";s:17:\"input-focus-color\";s:16:\"@brand-secondary\";s:14:\"input-error-bg\";s:47:\"desaturate(lighten(@highlight-error, 38%), 20%)\";s:18:\"input-error-border\";s:16:\"@highlight-error\";s:17:\"input-error-color\";s:16:\"@highlight-error\";s:16:\"input-success-bg\";s:7:\"#FFFFFF\";s:20:\"input-success-border\";s:18:\"@highlight-success\";s:19:\"input-success-color\";s:21:\"@brand-secondary-dark\";s:21:\"panel-table-header-bg\";s:9:\"@panel-bg\";s:24:\"panel-table-header-color\";s:16:\"@text-color-dark\";s:12:\"table-row-bg\";s:7:\"#FFFFFF\";s:15:\"table-row-color\";s:16:\"@brand-secondary\";s:22:\"table-row-highlight-bg\";s:25:\"darken(@table-row-bg, 4%)\";s:15:\"table-header-bg\";s:16:\"@brand-secondary\";s:18:\"table-header-color\";s:7:\"#FFFFFF\";s:17:\"badge-discount-bg\";s:16:\"@highlight-error\";s:20:\"badge-discount-color\";s:7:\"#FFFFFF\";s:17:\"badge-newcomer-bg\";s:17:\"@highlight-notice\";s:20:\"badge-newcomer-color\";s:7:\"#FFFFFF\";s:23:\"badge-recommendation-bg\";s:18:\"@highlight-success\";s:26:\"badge-recommendation-color\";s:7:\"#FFFFFF\";s:17:\"badge-download-bg\";s:15:\"@highlight-info\";s:20:\"badge-download-color\";s:7:\"#FFFFFF\";}'),
(4,	22,	'__color_scheme_pink__',	'__color_scheme_pink_description__',	'a:72:{s:13:\"brand-primary\";s:7:\"#d31e81\";s:19:\"brand-primary-light\";s:41:\"saturate(lighten(@brand-primary,12%), 5%)\";s:15:\"brand-secondary\";s:7:\"#5F7285\";s:20:\"brand-secondary-dark\";s:29:\"darken(@brand-secondary, 15%)\";s:4:\"gray\";s:7:\"#F5F5F8\";s:10:\"gray-light\";s:18:\"lighten(@gray, 1%)\";s:9:\"gray-dark\";s:24:\"darken(@gray-light, 10%)\";s:12:\"border-color\";s:10:\"@gray-dark\";s:17:\"highlight-success\";s:7:\"#2ECC71\";s:15:\"highlight-error\";s:7:\"#E74C3C\";s:16:\"highlight-notice\";s:7:\"#F1C40F\";s:14:\"highlight-info\";s:7:\"#4AA3DF\";s:7:\"body-bg\";s:23:\"darken(@gray-light, 5%)\";s:10:\"overlay-bg\";s:7:\"#000000\";s:15:\"overlay-dark-bg\";s:11:\"@overlay-bg\";s:16:\"overlay-light-bg\";s:7:\"#FFFFFF\";s:15:\"overlay-opacity\";s:3:\"0.7\";s:10:\"text-color\";s:16:\"@brand-secondary\";s:15:\"text-color-dark\";s:21:\"@brand-secondary-dark\";s:10:\"link-color\";s:14:\"@brand-primary\";s:16:\"link-hover-color\";s:24:\"darken(@link-color, 10%)\";s:17:\"rating-star-color\";s:17:\"@highlight-notice\";s:18:\"btn-default-top-bg\";s:7:\"#FFFFFF\";s:21:\"btn-default-bottom-bg\";s:11:\"@gray-light\";s:20:\"btn-default-hover-bg\";s:7:\"#FFFFFF\";s:22:\"btn-default-text-color\";s:11:\"@text-color\";s:28:\"btn-default-hover-text-color\";s:14:\"@brand-primary\";s:24:\"btn-default-border-color\";s:13:\"@border-color\";s:30:\"btn-default-hover-border-color\";s:14:\"@brand-primary\";s:18:\"btn-primary-top-bg\";s:20:\"@brand-primary-light\";s:21:\"btn-primary-bottom-bg\";s:14:\"@brand-primary\";s:20:\"btn-primary-hover-bg\";s:14:\"@brand-primary\";s:22:\"btn-primary-text-color\";s:7:\"#FFFFFF\";s:28:\"btn-primary-hover-text-color\";s:23:\"@btn-primary-text-color\";s:20:\"btn-secondary-top-bg\";s:16:\"@brand-secondary\";s:23:\"btn-secondary-bottom-bg\";s:21:\"@brand-secondary-dark\";s:22:\"btn-secondary-hover-bg\";s:21:\"@brand-secondary-dark\";s:24:\"btn-secondary-text-color\";s:7:\"#FFFFFF\";s:30:\"btn-secondary-hover-text-color\";s:25:\"@btn-secondary-text-color\";s:15:\"panel-header-bg\";s:11:\"@gray-light\";s:18:\"panel-header-color\";s:11:\"@text-color\";s:12:\"panel-border\";s:13:\"@border-color\";s:8:\"panel-bg\";s:7:\"#FFFFFF\";s:11:\"label-color\";s:11:\"@text-color\";s:8:\"input-bg\";s:11:\"@gray-light\";s:11:\"input-color\";s:16:\"@brand-secondary\";s:23:\"input-placeholder-color\";s:25:\"lighten(@text-color, 15%)\";s:12:\"input-border\";s:13:\"@border-color\";s:14:\"input-focus-bg\";s:7:\"#FFFFFF\";s:18:\"input-focus-border\";s:14:\"@brand-primary\";s:17:\"input-focus-color\";s:16:\"@brand-secondary\";s:14:\"input-error-bg\";s:47:\"desaturate(lighten(@highlight-error, 38%), 20%)\";s:18:\"input-error-border\";s:16:\"@highlight-error\";s:17:\"input-error-color\";s:16:\"@highlight-error\";s:16:\"input-success-bg\";s:7:\"#FFFFFF\";s:20:\"input-success-border\";s:18:\"@highlight-success\";s:19:\"input-success-color\";s:21:\"@brand-secondary-dark\";s:21:\"panel-table-header-bg\";s:9:\"@panel-bg\";s:24:\"panel-table-header-color\";s:16:\"@text-color-dark\";s:12:\"table-row-bg\";s:7:\"#FFFFFF\";s:15:\"table-row-color\";s:16:\"@brand-secondary\";s:22:\"table-row-highlight-bg\";s:25:\"darken(@table-row-bg, 4%)\";s:15:\"table-header-bg\";s:16:\"@brand-secondary\";s:18:\"table-header-color\";s:7:\"#FFFFFF\";s:17:\"badge-discount-bg\";s:16:\"@highlight-error\";s:20:\"badge-discount-color\";s:7:\"#FFFFFF\";s:17:\"badge-newcomer-bg\";s:17:\"@highlight-notice\";s:20:\"badge-newcomer-color\";s:7:\"#FFFFFF\";s:23:\"badge-recommendation-bg\";s:18:\"@highlight-success\";s:26:\"badge-recommendation-color\";s:7:\"#FFFFFF\";s:17:\"badge-download-bg\";s:15:\"@highlight-info\";s:20:\"badge-download-color\";s:7:\"#FFFFFF\";}'),
(5,	22,	'__color_scheme_gray__',	'__color_scheme_gray_description__',	'a:72:{s:13:\"brand-primary\";s:7:\"#555555\";s:19:\"brand-primary-light\";s:28:\"lighten(@brand-primary, 10%)\";s:15:\"brand-secondary\";s:7:\"#999999\";s:20:\"brand-secondary-dark\";s:28:\"darken(@brand-secondary, 8%)\";s:4:\"gray\";s:7:\"#F5F5F8\";s:10:\"gray-light\";s:18:\"lighten(@gray, 1%)\";s:9:\"gray-dark\";s:24:\"darken(@gray-light, 10%)\";s:12:\"border-color\";s:10:\"@gray-dark\";s:17:\"highlight-success\";s:7:\"#2ECC71\";s:15:\"highlight-error\";s:7:\"#E74C3C\";s:16:\"highlight-notice\";s:7:\"#F1C40F\";s:14:\"highlight-info\";s:7:\"#4AA3DF\";s:7:\"body-bg\";s:23:\"darken(@gray-light, 5%)\";s:10:\"overlay-bg\";s:7:\"#000000\";s:15:\"overlay-dark-bg\";s:11:\"@overlay-bg\";s:16:\"overlay-light-bg\";s:7:\"#FFFFFF\";s:15:\"overlay-opacity\";s:3:\"0.7\";s:10:\"text-color\";s:20:\"@brand-primary-light\";s:15:\"text-color-dark\";s:14:\"@brand-primary\";s:10:\"link-color\";s:16:\"@brand-secondary\";s:16:\"link-hover-color\";s:24:\"darken(@link-color, 10%)\";s:17:\"rating-star-color\";s:17:\"@highlight-notice\";s:18:\"btn-default-top-bg\";s:7:\"#FFFFFF\";s:21:\"btn-default-bottom-bg\";s:11:\"@gray-light\";s:20:\"btn-default-hover-bg\";s:7:\"#FFFFFF\";s:22:\"btn-default-text-color\";s:11:\"@text-color\";s:28:\"btn-default-hover-text-color\";s:14:\"@brand-primary\";s:24:\"btn-default-border-color\";s:13:\"@border-color\";s:30:\"btn-default-hover-border-color\";s:14:\"@brand-primary\";s:18:\"btn-primary-top-bg\";s:20:\"@brand-primary-light\";s:21:\"btn-primary-bottom-bg\";s:14:\"@brand-primary\";s:20:\"btn-primary-hover-bg\";s:14:\"@brand-primary\";s:22:\"btn-primary-text-color\";s:7:\"#FFFFFF\";s:28:\"btn-primary-hover-text-color\";s:23:\"@btn-primary-text-color\";s:20:\"btn-secondary-top-bg\";s:16:\"@brand-secondary\";s:23:\"btn-secondary-bottom-bg\";s:21:\"@brand-secondary-dark\";s:22:\"btn-secondary-hover-bg\";s:21:\"@brand-secondary-dark\";s:24:\"btn-secondary-text-color\";s:7:\"#FFFFFF\";s:30:\"btn-secondary-hover-text-color\";s:25:\"@btn-secondary-text-color\";s:15:\"panel-header-bg\";s:11:\"@gray-light\";s:18:\"panel-header-color\";s:11:\"@text-color\";s:12:\"panel-border\";s:13:\"@border-color\";s:8:\"panel-bg\";s:7:\"#FFFFFF\";s:11:\"label-color\";s:11:\"@text-color\";s:8:\"input-bg\";s:11:\"@gray-light\";s:11:\"input-color\";s:16:\"@brand-secondary\";s:23:\"input-placeholder-color\";s:25:\"lighten(@text-color, 15%)\";s:12:\"input-border\";s:13:\"@border-color\";s:14:\"input-focus-bg\";s:7:\"#FFFFFF\";s:18:\"input-focus-border\";s:14:\"@brand-primary\";s:17:\"input-focus-color\";s:16:\"@brand-secondary\";s:14:\"input-error-bg\";s:47:\"desaturate(lighten(@highlight-error, 38%), 20%)\";s:18:\"input-error-border\";s:16:\"@highlight-error\";s:17:\"input-error-color\";s:16:\"@highlight-error\";s:16:\"input-success-bg\";s:7:\"#FFFFFF\";s:20:\"input-success-border\";s:18:\"@highlight-success\";s:19:\"input-success-color\";s:21:\"@brand-secondary-dark\";s:21:\"panel-table-header-bg\";s:9:\"@panel-bg\";s:24:\"panel-table-header-color\";s:16:\"@text-color-dark\";s:12:\"table-row-bg\";s:7:\"#FFFFFF\";s:15:\"table-row-color\";s:16:\"@brand-secondary\";s:22:\"table-row-highlight-bg\";s:25:\"darken(@table-row-bg, 4%)\";s:15:\"table-header-bg\";s:16:\"@brand-secondary\";s:18:\"table-header-color\";s:7:\"#FFFFFF\";s:17:\"badge-discount-bg\";s:16:\"@highlight-error\";s:20:\"badge-discount-color\";s:7:\"#FFFFFF\";s:17:\"badge-newcomer-bg\";s:17:\"@highlight-notice\";s:20:\"badge-newcomer-color\";s:7:\"#FFFFFF\";s:23:\"badge-recommendation-bg\";s:18:\"@highlight-success\";s:26:\"badge-recommendation-color\";s:7:\"#FFFFFF\";s:17:\"badge-download-bg\";s:15:\"@highlight-info\";s:20:\"badge-download-color\";s:7:\"#FFFFFF\";}'),
(6,	22,	'__color_scheme_brown__',	'__color_scheme_brown_description__',	'a:72:{s:13:\"brand-primary\";s:7:\"#613400\";s:19:\"brand-primary-light\";s:40:\"saturate(lighten(@brand-primary,5%), 5%)\";s:15:\"brand-secondary\";s:7:\"#5F7285\";s:20:\"brand-secondary-dark\";s:29:\"darken(@brand-secondary, 15%)\";s:4:\"gray\";s:7:\"#F5F5F8\";s:10:\"gray-light\";s:18:\"lighten(@gray, 1%)\";s:9:\"gray-dark\";s:24:\"darken(@gray-light, 10%)\";s:12:\"border-color\";s:10:\"@gray-dark\";s:17:\"highlight-success\";s:7:\"#2ECC71\";s:15:\"highlight-error\";s:7:\"#E74C3C\";s:16:\"highlight-notice\";s:7:\"#F1C40F\";s:14:\"highlight-info\";s:7:\"#4AA3DF\";s:7:\"body-bg\";s:23:\"darken(@gray-light, 5%)\";s:10:\"overlay-bg\";s:7:\"#000000\";s:15:\"overlay-dark-bg\";s:11:\"@overlay-bg\";s:16:\"overlay-light-bg\";s:7:\"#FFFFFF\";s:15:\"overlay-opacity\";s:3:\"0.7\";s:10:\"text-color\";s:16:\"@brand-secondary\";s:15:\"text-color-dark\";s:21:\"@brand-secondary-dark\";s:10:\"link-color\";s:14:\"@brand-primary\";s:16:\"link-hover-color\";s:24:\"darken(@link-color, 10%)\";s:17:\"rating-star-color\";s:17:\"@highlight-notice\";s:18:\"btn-default-top-bg\";s:7:\"#FFFFFF\";s:21:\"btn-default-bottom-bg\";s:11:\"@gray-light\";s:20:\"btn-default-hover-bg\";s:7:\"#FFFFFF\";s:22:\"btn-default-text-color\";s:11:\"@text-color\";s:28:\"btn-default-hover-text-color\";s:14:\"@brand-primary\";s:24:\"btn-default-border-color\";s:13:\"@border-color\";s:30:\"btn-default-hover-border-color\";s:14:\"@brand-primary\";s:18:\"btn-primary-top-bg\";s:20:\"@brand-primary-light\";s:21:\"btn-primary-bottom-bg\";s:14:\"@brand-primary\";s:20:\"btn-primary-hover-bg\";s:14:\"@brand-primary\";s:22:\"btn-primary-text-color\";s:7:\"#FFFFFF\";s:28:\"btn-primary-hover-text-color\";s:23:\"@btn-primary-text-color\";s:20:\"btn-secondary-top-bg\";s:16:\"@brand-secondary\";s:23:\"btn-secondary-bottom-bg\";s:21:\"@brand-secondary-dark\";s:22:\"btn-secondary-hover-bg\";s:21:\"@brand-secondary-dark\";s:24:\"btn-secondary-text-color\";s:7:\"#FFFFFF\";s:30:\"btn-secondary-hover-text-color\";s:25:\"@btn-secondary-text-color\";s:15:\"panel-header-bg\";s:11:\"@gray-light\";s:18:\"panel-header-color\";s:11:\"@text-color\";s:12:\"panel-border\";s:13:\"@border-color\";s:8:\"panel-bg\";s:7:\"#FFFFFF\";s:11:\"label-color\";s:11:\"@text-color\";s:8:\"input-bg\";s:11:\"@gray-light\";s:11:\"input-color\";s:16:\"@brand-secondary\";s:23:\"input-placeholder-color\";s:25:\"lighten(@text-color, 15%)\";s:12:\"input-border\";s:13:\"@border-color\";s:14:\"input-focus-bg\";s:7:\"#FFFFFF\";s:18:\"input-focus-border\";s:14:\"@brand-primary\";s:17:\"input-focus-color\";s:16:\"@brand-secondary\";s:14:\"input-error-bg\";s:47:\"desaturate(lighten(@highlight-error, 38%), 20%)\";s:18:\"input-error-border\";s:16:\"@highlight-error\";s:17:\"input-error-color\";s:16:\"@highlight-error\";s:16:\"input-success-bg\";s:7:\"#FFFFFF\";s:20:\"input-success-border\";s:18:\"@highlight-success\";s:19:\"input-success-color\";s:21:\"@brand-secondary-dark\";s:21:\"panel-table-header-bg\";s:9:\"@panel-bg\";s:24:\"panel-table-header-color\";s:16:\"@text-color-dark\";s:12:\"table-row-bg\";s:7:\"#FFFFFF\";s:15:\"table-row-color\";s:16:\"@brand-secondary\";s:22:\"table-row-highlight-bg\";s:25:\"darken(@table-row-bg, 4%)\";s:15:\"table-header-bg\";s:16:\"@brand-secondary\";s:18:\"table-header-color\";s:7:\"#FFFFFF\";s:17:\"badge-discount-bg\";s:16:\"@highlight-error\";s:20:\"badge-discount-color\";s:7:\"#FFFFFF\";s:17:\"badge-newcomer-bg\";s:17:\"@highlight-notice\";s:20:\"badge-newcomer-color\";s:7:\"#FFFFFF\";s:23:\"badge-recommendation-bg\";s:18:\"@highlight-success\";s:26:\"badge-recommendation-color\";s:7:\"#FFFFFF\";s:17:\"badge-download-bg\";s:15:\"@highlight-info\";s:20:\"badge-download-color\";s:7:\"#FFFFFF\";}'),
(7,	22,	'__color_scheme_blue__',	'__color_scheme_blue_description__',	'a:72:{s:13:\"brand-primary\";s:7:\"#009ee0\";s:19:\"brand-primary-light\";s:41:\"saturate(lighten(@brand-primary,12%), 5%)\";s:15:\"brand-secondary\";s:7:\"#5F7285\";s:20:\"brand-secondary-dark\";s:29:\"darken(@brand-secondary, 15%)\";s:4:\"gray\";s:7:\"#F5F5F8\";s:10:\"gray-light\";s:18:\"lighten(@gray, 1%)\";s:9:\"gray-dark\";s:24:\"darken(@gray-light, 10%)\";s:12:\"border-color\";s:10:\"@gray-dark\";s:17:\"highlight-success\";s:7:\"#2ECC71\";s:15:\"highlight-error\";s:7:\"#E74C3C\";s:16:\"highlight-notice\";s:7:\"#F1C40F\";s:14:\"highlight-info\";s:7:\"#4AA3DF\";s:7:\"body-bg\";s:23:\"darken(@gray-light, 5%)\";s:10:\"overlay-bg\";s:7:\"#000000\";s:15:\"overlay-dark-bg\";s:11:\"@overlay-bg\";s:16:\"overlay-light-bg\";s:7:\"#FFFFFF\";s:15:\"overlay-opacity\";s:3:\"0.7\";s:10:\"text-color\";s:16:\"@brand-secondary\";s:15:\"text-color-dark\";s:21:\"@brand-secondary-dark\";s:10:\"link-color\";s:14:\"@brand-primary\";s:16:\"link-hover-color\";s:24:\"darken(@link-color, 10%)\";s:17:\"rating-star-color\";s:17:\"@highlight-notice\";s:18:\"btn-default-top-bg\";s:7:\"#FFFFFF\";s:21:\"btn-default-bottom-bg\";s:11:\"@gray-light\";s:20:\"btn-default-hover-bg\";s:7:\"#FFFFFF\";s:22:\"btn-default-text-color\";s:11:\"@text-color\";s:28:\"btn-default-hover-text-color\";s:14:\"@brand-primary\";s:24:\"btn-default-border-color\";s:13:\"@border-color\";s:30:\"btn-default-hover-border-color\";s:14:\"@brand-primary\";s:18:\"btn-primary-top-bg\";s:20:\"@brand-primary-light\";s:21:\"btn-primary-bottom-bg\";s:14:\"@brand-primary\";s:20:\"btn-primary-hover-bg\";s:14:\"@brand-primary\";s:22:\"btn-primary-text-color\";s:7:\"#FFFFFF\";s:28:\"btn-primary-hover-text-color\";s:23:\"@btn-primary-text-color\";s:20:\"btn-secondary-top-bg\";s:16:\"@brand-secondary\";s:23:\"btn-secondary-bottom-bg\";s:21:\"@brand-secondary-dark\";s:22:\"btn-secondary-hover-bg\";s:21:\"@brand-secondary-dark\";s:24:\"btn-secondary-text-color\";s:7:\"#FFFFFF\";s:30:\"btn-secondary-hover-text-color\";s:25:\"@btn-secondary-text-color\";s:15:\"panel-header-bg\";s:11:\"@gray-light\";s:18:\"panel-header-color\";s:11:\"@text-color\";s:12:\"panel-border\";s:13:\"@border-color\";s:8:\"panel-bg\";s:7:\"#FFFFFF\";s:11:\"label-color\";s:11:\"@text-color\";s:8:\"input-bg\";s:11:\"@gray-light\";s:11:\"input-color\";s:16:\"@brand-secondary\";s:23:\"input-placeholder-color\";s:25:\"lighten(@text-color, 15%)\";s:12:\"input-border\";s:13:\"@border-color\";s:14:\"input-focus-bg\";s:7:\"#FFFFFF\";s:18:\"input-focus-border\";s:14:\"@brand-primary\";s:17:\"input-focus-color\";s:16:\"@brand-secondary\";s:14:\"input-error-bg\";s:47:\"desaturate(lighten(@highlight-error, 38%), 20%)\";s:18:\"input-error-border\";s:16:\"@highlight-error\";s:17:\"input-error-color\";s:16:\"@highlight-error\";s:16:\"input-success-bg\";s:7:\"#FFFFFF\";s:20:\"input-success-border\";s:18:\"@highlight-success\";s:19:\"input-success-color\";s:21:\"@brand-secondary-dark\";s:21:\"panel-table-header-bg\";s:9:\"@panel-bg\";s:24:\"panel-table-header-color\";s:16:\"@text-color-dark\";s:12:\"table-row-bg\";s:7:\"#FFFFFF\";s:15:\"table-row-color\";s:16:\"@brand-secondary\";s:22:\"table-row-highlight-bg\";s:25:\"darken(@table-row-bg, 4%)\";s:15:\"table-header-bg\";s:16:\"@brand-secondary\";s:18:\"table-header-color\";s:7:\"#FFFFFF\";s:17:\"badge-discount-bg\";s:16:\"@highlight-error\";s:20:\"badge-discount-color\";s:7:\"#FFFFFF\";s:17:\"badge-newcomer-bg\";s:17:\"@highlight-notice\";s:20:\"badge-newcomer-color\";s:7:\"#FFFFFF\";s:23:\"badge-recommendation-bg\";s:18:\"@highlight-success\";s:26:\"badge-recommendation-color\";s:7:\"#FFFFFF\";s:17:\"badge-download-bg\";s:15:\"@highlight-info\";s:20:\"badge-download-color\";s:7:\"#FFFFFF\";}'),
(8,	22,	'__color_scheme_black__',	'__color_scheme_black_description__',	'a:72:{s:13:\"brand-primary\";s:7:\"#000000\";s:19:\"brand-primary-light\";s:28:\"lighten(@brand-primary, 20%)\";s:15:\"brand-secondary\";s:7:\"#555555\";s:20:\"brand-secondary-dark\";s:29:\"darken(@brand-secondary, 10%)\";s:4:\"gray\";s:7:\"#F5F5F8\";s:10:\"gray-light\";s:18:\"lighten(@gray, 1%)\";s:9:\"gray-dark\";s:24:\"darken(@gray-light, 10%)\";s:12:\"border-color\";s:10:\"@gray-dark\";s:17:\"highlight-success\";s:7:\"#2ECC71\";s:15:\"highlight-error\";s:7:\"#E74C3C\";s:16:\"highlight-notice\";s:7:\"#F1C40F\";s:14:\"highlight-info\";s:7:\"#4AA3DF\";s:7:\"body-bg\";s:23:\"darken(@gray-light, 5%)\";s:10:\"overlay-bg\";s:7:\"#000000\";s:15:\"overlay-dark-bg\";s:11:\"@overlay-bg\";s:16:\"overlay-light-bg\";s:7:\"#FFFFFF\";s:15:\"overlay-opacity\";s:3:\"0.7\";s:10:\"text-color\";s:16:\"@brand-secondary\";s:15:\"text-color-dark\";s:21:\"@brand-secondary-dark\";s:10:\"link-color\";s:14:\"@brand-primary\";s:16:\"link-hover-color\";s:24:\"darken(@link-color, 10%)\";s:17:\"rating-star-color\";s:17:\"@highlight-notice\";s:18:\"btn-default-top-bg\";s:7:\"#FFFFFF\";s:21:\"btn-default-bottom-bg\";s:11:\"@gray-light\";s:20:\"btn-default-hover-bg\";s:7:\"#FFFFFF\";s:22:\"btn-default-text-color\";s:11:\"@text-color\";s:28:\"btn-default-hover-text-color\";s:14:\"@brand-primary\";s:24:\"btn-default-border-color\";s:13:\"@border-color\";s:30:\"btn-default-hover-border-color\";s:14:\"@brand-primary\";s:18:\"btn-primary-top-bg\";s:20:\"@brand-primary-light\";s:21:\"btn-primary-bottom-bg\";s:14:\"@brand-primary\";s:20:\"btn-primary-hover-bg\";s:14:\"@brand-primary\";s:22:\"btn-primary-text-color\";s:7:\"#FFFFFF\";s:28:\"btn-primary-hover-text-color\";s:23:\"@btn-primary-text-color\";s:20:\"btn-secondary-top-bg\";s:16:\"@brand-secondary\";s:23:\"btn-secondary-bottom-bg\";s:21:\"@brand-secondary-dark\";s:22:\"btn-secondary-hover-bg\";s:21:\"@brand-secondary-dark\";s:24:\"btn-secondary-text-color\";s:7:\"#FFFFFF\";s:30:\"btn-secondary-hover-text-color\";s:25:\"@btn-secondary-text-color\";s:15:\"panel-header-bg\";s:11:\"@gray-light\";s:18:\"panel-header-color\";s:11:\"@text-color\";s:12:\"panel-border\";s:13:\"@border-color\";s:8:\"panel-bg\";s:7:\"#FFFFFF\";s:11:\"label-color\";s:11:\"@text-color\";s:8:\"input-bg\";s:11:\"@gray-light\";s:11:\"input-color\";s:16:\"@brand-secondary\";s:23:\"input-placeholder-color\";s:25:\"lighten(@text-color, 15%)\";s:12:\"input-border\";s:13:\"@border-color\";s:14:\"input-focus-bg\";s:7:\"#FFFFFF\";s:18:\"input-focus-border\";s:14:\"@brand-primary\";s:17:\"input-focus-color\";s:16:\"@brand-secondary\";s:14:\"input-error-bg\";s:47:\"desaturate(lighten(@highlight-error, 38%), 20%)\";s:18:\"input-error-border\";s:16:\"@highlight-error\";s:17:\"input-error-color\";s:16:\"@highlight-error\";s:16:\"input-success-bg\";s:7:\"#FFFFFF\";s:20:\"input-success-border\";s:18:\"@highlight-success\";s:19:\"input-success-color\";s:21:\"@brand-secondary-dark\";s:21:\"panel-table-header-bg\";s:9:\"@panel-bg\";s:24:\"panel-table-header-color\";s:16:\"@text-color-dark\";s:12:\"table-row-bg\";s:7:\"#FFFFFF\";s:15:\"table-row-color\";s:16:\"@brand-secondary\";s:22:\"table-row-highlight-bg\";s:25:\"darken(@table-row-bg, 4%)\";s:15:\"table-header-bg\";s:16:\"@brand-secondary\";s:18:\"table-header-color\";s:7:\"#FFFFFF\";s:17:\"badge-discount-bg\";s:16:\"@highlight-error\";s:20:\"badge-discount-color\";s:7:\"#FFFFFF\";s:17:\"badge-newcomer-bg\";s:17:\"@highlight-notice\";s:20:\"badge-newcomer-color\";s:7:\"#FFFFFF\";s:23:\"badge-recommendation-bg\";s:18:\"@highlight-success\";s:26:\"badge-recommendation-color\";s:7:\"#FFFFFF\";s:17:\"badge-download-bg\";s:15:\"@highlight-info\";s:20:\"badge-download-color\";s:7:\"#FFFFFF\";}'),
(9,	22,	'__color_scheme_orange__',	'__color_scheme_orange_description__',	'a:72:{s:13:\"brand-primary\";s:7:\"#D9400B\";s:19:\"brand-primary-light\";s:41:\"saturate(lighten(@brand-primary,12%), 5%)\";s:15:\"brand-secondary\";s:7:\"#5F7285\";s:20:\"brand-secondary-dark\";s:29:\"darken(@brand-secondary, 15%)\";s:4:\"gray\";s:7:\"#F5F5F8\";s:10:\"gray-light\";s:18:\"lighten(@gray, 1%)\";s:9:\"gray-dark\";s:24:\"darken(@gray-light, 10%)\";s:12:\"border-color\";s:10:\"@gray-dark\";s:17:\"highlight-success\";s:7:\"#2ECC71\";s:15:\"highlight-error\";s:7:\"#E74C3C\";s:16:\"highlight-notice\";s:7:\"#F1C40F\";s:14:\"highlight-info\";s:7:\"#4AA3DF\";s:7:\"body-bg\";s:23:\"darken(@gray-light, 5%)\";s:10:\"overlay-bg\";s:7:\"#000000\";s:15:\"overlay-dark-bg\";s:11:\"@overlay-bg\";s:16:\"overlay-light-bg\";s:7:\"#FFFFFF\";s:15:\"overlay-opacity\";s:3:\"0.7\";s:10:\"text-color\";s:16:\"@brand-secondary\";s:15:\"text-color-dark\";s:21:\"@brand-secondary-dark\";s:10:\"link-color\";s:14:\"@brand-primary\";s:16:\"link-hover-color\";s:24:\"darken(@link-color, 10%)\";s:17:\"rating-star-color\";s:17:\"@highlight-notice\";s:18:\"btn-default-top-bg\";s:7:\"#FFFFFF\";s:21:\"btn-default-bottom-bg\";s:11:\"@gray-light\";s:20:\"btn-default-hover-bg\";s:7:\"#FFFFFF\";s:22:\"btn-default-text-color\";s:11:\"@text-color\";s:28:\"btn-default-hover-text-color\";s:14:\"@brand-primary\";s:24:\"btn-default-border-color\";s:13:\"@border-color\";s:30:\"btn-default-hover-border-color\";s:14:\"@brand-primary\";s:18:\"btn-primary-top-bg\";s:20:\"@brand-primary-light\";s:21:\"btn-primary-bottom-bg\";s:14:\"@brand-primary\";s:20:\"btn-primary-hover-bg\";s:14:\"@brand-primary\";s:22:\"btn-primary-text-color\";s:7:\"#FFFFFF\";s:28:\"btn-primary-hover-text-color\";s:23:\"@btn-primary-text-color\";s:20:\"btn-secondary-top-bg\";s:16:\"@brand-secondary\";s:23:\"btn-secondary-bottom-bg\";s:21:\"@brand-secondary-dark\";s:22:\"btn-secondary-hover-bg\";s:21:\"@brand-secondary-dark\";s:24:\"btn-secondary-text-color\";s:7:\"#FFFFFF\";s:30:\"btn-secondary-hover-text-color\";s:25:\"@btn-secondary-text-color\";s:15:\"panel-header-bg\";s:11:\"@gray-light\";s:18:\"panel-header-color\";s:11:\"@text-color\";s:12:\"panel-border\";s:13:\"@border-color\";s:8:\"panel-bg\";s:7:\"#FFFFFF\";s:11:\"label-color\";s:11:\"@text-color\";s:8:\"input-bg\";s:11:\"@gray-light\";s:11:\"input-color\";s:16:\"@brand-secondary\";s:23:\"input-placeholder-color\";s:25:\"lighten(@text-color, 15%)\";s:12:\"input-border\";s:13:\"@border-color\";s:14:\"input-focus-bg\";s:7:\"#FFFFFF\";s:18:\"input-focus-border\";s:14:\"@brand-primary\";s:17:\"input-focus-color\";s:16:\"@brand-secondary\";s:14:\"input-error-bg\";s:47:\"desaturate(lighten(@highlight-error, 38%), 20%)\";s:18:\"input-error-border\";s:16:\"@highlight-error\";s:17:\"input-error-color\";s:16:\"@highlight-error\";s:16:\"input-success-bg\";s:7:\"#FFFFFF\";s:20:\"input-success-border\";s:18:\"@highlight-success\";s:19:\"input-success-color\";s:21:\"@brand-secondary-dark\";s:21:\"panel-table-header-bg\";s:9:\"@panel-bg\";s:24:\"panel-table-header-color\";s:16:\"@text-color-dark\";s:12:\"table-row-bg\";s:7:\"#FFFFFF\";s:15:\"table-row-color\";s:16:\"@brand-secondary\";s:22:\"table-row-highlight-bg\";s:25:\"darken(@table-row-bg, 4%)\";s:15:\"table-header-bg\";s:16:\"@brand-secondary\";s:18:\"table-header-color\";s:7:\"#FFFFFF\";s:17:\"badge-discount-bg\";s:16:\"@highlight-error\";s:20:\"badge-discount-color\";s:7:\"#FFFFFF\";s:17:\"badge-newcomer-bg\";s:17:\"@highlight-notice\";s:20:\"badge-newcomer-color\";s:7:\"#FFFFFF\";s:23:\"badge-recommendation-bg\";s:18:\"@highlight-success\";s:26:\"badge-recommendation-color\";s:7:\"#FFFFFF\";s:17:\"badge-download-bg\";s:15:\"@highlight-info\";s:20:\"badge-download-color\";s:7:\"#FFFFFF\";}'),
(10,	23,	'__bare_min_appearance__',	'__bare_min_appearance_description__',	'a:1:{s:5:\"color\";s:4:\"#fff\";}'),
(11,	23,	'__bare_max_appearance__',	'__bare_max_appearance_description__',	'a:1:{s:5:\"color\";s:4:\"#fff\";}');

DROP TABLE IF EXISTS `s_core_templates_config_values`;
CREATE TABLE `s_core_templates_config_values` (
  `id` int NOT NULL AUTO_INCREMENT,
  `element_id` int NOT NULL,
  `shop_id` int NOT NULL,
  `value` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `element_id_shop_id` (`element_id`,`shop_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_core_theme_settings`;
CREATE TABLE `s_core_theme_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `compiler_force` int NOT NULL,
  `compiler_create_source_map` int NOT NULL,
  `compiler_compress_css` int NOT NULL,
  `compiler_compress_js` int NOT NULL,
  `force_reload_snippets` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_theme_settings` (`id`, `compiler_force`, `compiler_create_source_map`, `compiler_compress_css`, `compiler_compress_js`, `force_reload_snippets`) VALUES
(2,	0,	0,	1,	1,	0);

DROP TABLE IF EXISTS `s_core_translations`;
CREATE TABLE `s_core_translations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `objecttype` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `objectdata` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `objectkey` int unsigned NOT NULL,
  `objectlanguage` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `dirty` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `objecttype` (`objecttype`,`objectkey`,`objectlanguage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_translations` (`id`, `objecttype`, `objectdata`, `objectkey`, `objectlanguage`, `dirty`) VALUES
(1,	'config_mails',	'a:4:{s:8:\"fromMail\";s:18:\"{config name=mail}\";s:8:\"fromName\";s:22:\"{config name=shopName}\";s:7:\"subject\";s:38:\"Documents to your order {$orderNumber}\";s:7:\"content\";s:331:\"{include file=\"string:{config name=emailheaderplain}\"}\n\nHello {$sUser.salutation|salutation} {$sUser.firstname} {$sUser.lastname},\n\nThank you for your order at {config name=shopName}. In the attachement you will find documents about your order as PDF.\nWe wish you a nice day.\n\n{include file=\"string:{config name=emailfooterplain}\"}\";}',	64,	'2',	0),
(2,	'documents',	'a:4:{i:1;a:1:{s:4:\"name\";s:7:\"Invoice\";}i:2;a:1:{s:4:\"name\";s:18:\"Notice of delivery\";}i:3;a:1:{s:4:\"name\";s:6:\"Credit\";}i:4;a:1:{s:4:\"name\";s:12:\"Cancellation\";}}',	1,	'2',	0),
(3,	'custom_facet',	'a:1:{i:0;a:1:{s:5:\"label\";s:9:\"Varianten\";}}',	1,	'1',	0),
(4,	'config_dispatch',	'a:5:{i:14;a:2:{s:13:\"dispatch_name\";s:16:\"Express Delivery\";s:20:\"dispatch_description\";s:22:\"Delivery within 2 days\";}i:9;a:1:{s:13:\"dispatch_name\";s:17:\"Standard delivery\";}i:16;a:2:{s:13:\"dispatch_name\";s:31:\"Standard international delivery\";s:20:\"dispatch_description\";s:52:\"Standard delivery into countries other than Germany.\";}i:10;a:2:{s:13:\"dispatch_name\";s:29:\"Shipping cost based on weight\";s:20:\"dispatch_description\";s:128:\"If the weight of a product exceeds 1 kilogram, the shipping method will switch automatically to \"Shipping cost based on weight\".\";}i:12;a:1:{s:13:\"dispatch_name\";s:10:\"Discounted\";}}',	1,	'2',	NULL),
(5,	'page',	'a:1:{s:11:\"description\";s:18:\"Cookie preferences\";}',	46,	'2',	1);

DROP TABLE IF EXISTS `s_core_units`;
CREATE TABLE `s_core_units` (
  `id` int NOT NULL AUTO_INCREMENT,
  `unit` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_units` (`id`, `unit`, `description`) VALUES
(1,	'l',	'Liter'),
(2,	'g',	'Gramm'),
(5,	'lfm',	'Laufende(r) Meter'),
(6,	'kg',	'Kilogramm'),
(8,	'Paket(e)',	'Paket(e)'),
(9,	'Stck.',	'Stück');

DROP TABLE IF EXISTS `s_core_widget_views`;
CREATE TABLE `s_core_widget_views` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `widget_id` int unsigned NOT NULL,
  `auth_id` int unsigned NOT NULL,
  `column` int unsigned NOT NULL,
  `position` int unsigned NOT NULL,
  `data` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `widget_id` (`widget_id`,`auth_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_widget_views` (`id`, `widget_id`, `auth_id`, `column`, `position`, `data`) VALUES
(1,	7,	50,	0,	0,	NULL);

DROP TABLE IF EXISTS `s_core_widgets`;
CREATE TABLE `s_core_widgets` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `label` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `plugin_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_core_widgets` (`id`, `name`, `label`, `plugin_id`) VALUES
(1,	'swag-sales-widget',	'Umsatz Heute und Gestern',	NULL),
(2,	'swag-upload-widget',	'Drag and Drop Upload',	NULL),
(3,	'swag-visitors-customers-widget',	'Besucher online',	NULL),
(4,	'swag-last-orders-widget',	'Letzte Bestellungen',	NULL),
(5,	'swag-notice-widget',	'Notizzettel',	NULL),
(6,	'swag-merchant-widget',	'Händlerfreischaltung',	NULL),
(7,	'swag-shopware-news-widget',	'shopware News',	NULL),
(9,	'swag-rating-widget',	NULL,	NULL);

DROP TABLE IF EXISTS `s_crontab`;
CREATE TABLE `s_crontab` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `action` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `elementID` int DEFAULT NULL,
  `data` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `next` datetime DEFAULT NULL,
  `start` datetime DEFAULT NULL,
  `interval` int NOT NULL,
  `active` int NOT NULL,
  `disable_on_error` tinyint(1) NOT NULL DEFAULT '1',
  `end` datetime DEFAULT NULL,
  `inform_template` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `inform_mail` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `pluginID` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_crontab` (`id`, `name`, `action`, `elementID`, `data`, `next`, `start`, `interval`, `active`, `disable_on_error`, `end`, `inform_template`, `inform_mail`, `pluginID`) VALUES
(1,	'Geburtstagsgruß',	'birthday',	NULL,	'',	'2010-10-16 23:42:58',	'2010-10-16 12:26:44',	86400,	1,	1,	'2010-10-16 12:26:44',	'',	'',	NULL),
(2,	'Aufräumen',	'clearing',	NULL,	'',	'2010-10-16 12:34:38',	'2010-10-16 12:34:32',	86400,	1,	1,	'2010-10-16 12:34:32',	'',	'',	NULL),
(3,	'Lagerbestand Warnung',	'article_stock',	NULL,	'',	'2010-10-16 12:34:33',	'2010-10-16 12:34:31',	86400,	1,	1,	'2010-10-16 12:34:32',	'sARTICLESTOCK',	'{$sConfig.sMAIL}',	NULL),
(5,	'Suche',	'search',	NULL,	'',	'2010-10-16 12:34:38',	'2010-10-16 12:34:32',	86400,	1,	1,	'2010-10-16 12:34:32',	'',	'',	NULL),
(6,	'eMail-Benachrichtigung',	'notification',	NULL,	'',	'2010-10-17 00:20:28',	'2010-10-16 12:26:44',	86400,	1,	1,	'2010-10-16 12:26:44',	'',	'',	NULL),
(7,	'Artikelbewertung per eMail',	'article_comment',	NULL,	'',	'2010-10-16 12:35:18',	'2010-10-16 12:34:32',	86400,	1,	1,	'2010-10-16 12:34:32',	'',	'',	NULL),
(8,	'Topseller Refresh',	'RefreshTopSeller',	NULL,	'',	'2013-05-21 14:29:44',	NULL,	86400,	1,	1,	'2013-05-21 14:29:44',	'',	'',	50),
(9,	'Similar shown article refresh',	'RefreshSimilarShown',	NULL,	'',	'2013-05-21 14:29:44',	NULL,	86400,	1,	1,	'2013-05-21 14:29:44',	'',	'',	50),
(10,	'Refresh seo index',	'RefreshSeoIndex',	NULL,	'',	'2013-05-21 13:28:04',	NULL,	86400,	1,	1,	'2013-05-21 13:28:04',	'',	'',	51),
(11,	'Refresh search index',	'RefreshSearchIndex',	NULL,	'',	'2013-05-21 13:28:04',	NULL,	86400,	1,	1,	'2013-05-21 13:28:04',	'',	'',	51),
(12,	'HTTP Cache löschen',	'ClearHttpCache',	NULL,	'',	'2019-12-07 03:00:00',	NULL,	86400,	1,	1,	'2019-12-07 03:00:00',	'',	'',	52),
(13,	'Media Garbage Collector',	'MediaCrawler',	NULL,	'',	'2019-12-06 09:19:53',	NULL,	86400,	0,	1,	'2019-12-06 09:19:53',	'',	'',	NULL),
(14,	'Basket Signature cleanup',	'CleanupSignatures',	NULL,	'',	'2016-10-11 08:34:13',	NULL,	86400,	1,	1,	'2016-10-11 08:34:13',	'',	'',	NULL),
(15,	'Customer Stream refresh',	'RefreshCustomerStreams',	NULL,	'',	'2016-01-01 01:00:00',	NULL,	7200,	1,	0,	'2016-01-01 01:00:01',	'',	'',	NULL),
(16,	'Cancelled baskets cleanup',	'CleanupCancelledBaskets',	NULL,	'',	NULL,	NULL,	86400,	1,	0,	NULL,	'',	'',	0),
(17,	'Guest customer cleanup',	'CleanupGuestCustomers',	NULL,	'',	NULL,	NULL,	86400,	1,	0,	NULL,	'',	'',	0),
(18,	'Opt-In table cleanup',	'OptinCleanup',	NULL,	'',	'2019-12-06 03:00:00',	NULL,	86400,	1,	0,	'2016-01-01 01:00:00',	'',	'',	NULL),
(19,	'Lösche nicht aktivierte Benutzer',	'RegistrationCleanup',	NULL,	'',	'2019-12-06 03:00:00',	NULL,	86400,	1,	0,	'2016-01-01 01:00:00',	'',	'',	NULL),
(20,	'Sitemap generation',	'SitemapGeneration',	NULL,	'',	'2019-12-06 00:00:00',	NULL,	86400,	0,	0,	'2016-01-01 01:00:00',	'',	'',	NULL),
(21,	'Remove old mail log entries',	'MailLogCleanup',	NULL,	'',	'2023-06-27 00:00:00',	NULL,	86400,	0,	1,	'2016-01-01 01:00:00',	'',	'',	NULL);

DROP TABLE IF EXISTS `s_customer_search_index`;
CREATE TABLE `s_customer_search_index` (
  `id` int NOT NULL,
  `email` varchar(70) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `active` int DEFAULT NULL,
  `accountmode` int DEFAULT NULL,
  `firstlogin` date DEFAULT NULL,
  `newsletter` int DEFAULT NULL,
  `shop_id` int DEFAULT NULL,
  `default_billing_address_id` int DEFAULT NULL,
  `title` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `salutation` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `firstname` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `lastname` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `customernumber` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `customer_group_id` int DEFAULT NULL,
  `customer_group_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `payment_id` int DEFAULT NULL,
  `company` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `department` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `street` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `zipcode` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `city` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `phone` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `additional_address_line1` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `additional_address_line2` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `country_id` int DEFAULT NULL,
  `country_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `state_id` int DEFAULT NULL,
  `age` int DEFAULT NULL,
  `count_orders` int DEFAULT NULL,
  `invoice_amount_sum` double DEFAULT NULL,
  `invoice_amount_avg` double DEFAULT NULL,
  `invoice_amount_min` double DEFAULT NULL,
  `invoice_amount_max` double DEFAULT NULL,
  `first_order_time` date DEFAULT NULL,
  `last_order_time` date DEFAULT NULL,
  `has_canceled_orders` int DEFAULT NULL,
  `product_avg` double DEFAULT NULL,
  `ordered_at_weekdays` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `ordered_in_shops` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `ordered_on_devices` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `ordered_with_deliveries` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `ordered_with_payments` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `ordered_products` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `ordered_products_of_categories` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `ordered_products_of_manufacturer` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `index_time` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_customer_streams`;
CREATE TABLE `s_customer_streams` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `conditions` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `description` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `freeze_up` datetime DEFAULT NULL,
  `static` int DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_customer_streams_attributes`;
CREATE TABLE `s_customer_streams_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `streamID` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `streamID` (`streamID`),
  CONSTRAINT `s_customer_streams_attributes_ibfk_1` FOREIGN KEY (`streamID`) REFERENCES `s_customer_streams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_customer_streams_mapping`;
CREATE TABLE `s_customer_streams_mapping` (
  `stream_id` int NOT NULL,
  `customer_id` int NOT NULL,
  PRIMARY KEY (`stream_id`,`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_emarketing_banners`;
CREATE TABLE `s_emarketing_banners` (
  `id` int NOT NULL AUTO_INCREMENT,
  `description` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `valid_from` datetime DEFAULT NULL,
  `valid_to` datetime DEFAULT NULL,
  `img` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `link` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `link_target` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `categoryID` int NOT NULL DEFAULT '0',
  `extension` varchar(25) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_emarketing_banners_attributes`;
CREATE TABLE `s_emarketing_banners_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `bannerID` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bannerID` (`bannerID`),
  CONSTRAINT `s_emarketing_banners_attributes_ibfk_1` FOREIGN KEY (`bannerID`) REFERENCES `s_emarketing_banners` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_emarketing_banners_statistics`;
CREATE TABLE `s_emarketing_banners_statistics` (
  `id` int NOT NULL AUTO_INCREMENT,
  `bannerID` int NOT NULL,
  `display_date` date NOT NULL,
  `clicks` int NOT NULL,
  `views` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `display_date` (`bannerID`,`display_date`),
  KEY `bannerID` (`bannerID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_emarketing_lastarticles`;
CREATE TABLE `s_emarketing_lastarticles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `articleID` int unsigned NOT NULL,
  `sessionID` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `userID` int unsigned NOT NULL,
  `shopID` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `articleID` (`articleID`,`sessionID`,`shopID`),
  KEY `userID` (`userID`),
  KEY `time` (`time`),
  KEY `sessionID` (`sessionID`),
  KEY `get_last_articles` (`sessionID`,`time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_emarketing_partner`;
CREATE TABLE `s_emarketing_partner` (
  `id` int NOT NULL AUTO_INCREMENT,
  `idcode` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `datum` date NOT NULL,
  `company` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `contact` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `street` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `zipcode` varchar(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `city` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `phone` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `fax` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `country` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `web` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `profil` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `fix` double NOT NULL DEFAULT '0',
  `percent` double NOT NULL DEFAULT '0',
  `cookielifetime` int NOT NULL DEFAULT '0',
  `active` int NOT NULL DEFAULT '0',
  `userID` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idcode` (`idcode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_emarketing_partner_attributes`;
CREATE TABLE `s_emarketing_partner_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `partnerID` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `partnerID` (`partnerID`),
  CONSTRAINT `FK__s_emarketing_partner` FOREIGN KEY (`partnerID`) REFERENCES `s_emarketing_partner` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_emarketing_referer`;
CREATE TABLE `s_emarketing_referer` (
  `id` int NOT NULL AUTO_INCREMENT,
  `userID` int NOT NULL,
  `referer` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `date` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_emarketing_referer` (`id`, `userID`, `referer`, `date`) VALUES
(1,	1,	'https://shopware.build/',	'2026-02-05');

DROP TABLE IF EXISTS `s_emarketing_tellafriend`;
CREATE TABLE `s_emarketing_tellafriend` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` date DEFAULT NULL,
  `recipient` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `sender` int NOT NULL DEFAULT '0',
  `confirmed` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_emarketing_voucher_codes`;
CREATE TABLE `s_emarketing_voucher_codes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `voucherID` int NOT NULL DEFAULT '0',
  `userID` int DEFAULT NULL,
  `code` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `cashed` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `voucherID_cashed` (`voucherID`,`cashed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_emarketing_vouchers`;
CREATE TABLE `s_emarketing_vouchers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `description` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `vouchercode` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `numberofunits` int NOT NULL DEFAULT '0',
  `value` double NOT NULL DEFAULT '0',
  `minimumcharge` double NOT NULL DEFAULT '0',
  `shippingfree` int NOT NULL DEFAULT '0',
  `bindtosupplier` int DEFAULT NULL,
  `valid_from` date DEFAULT NULL,
  `valid_to` date DEFAULT NULL,
  `ordercode` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `modus` int NOT NULL DEFAULT '0',
  `percental` int NOT NULL,
  `numorder` int NOT NULL,
  `customergroup` int DEFAULT NULL,
  `restrictarticles` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `strict` int NOT NULL,
  `subshopID` int DEFAULT NULL,
  `taxconfig` varchar(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `customer_stream_ids` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `modus` (`modus`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_emarketing_vouchers_attributes`;
CREATE TABLE `s_emarketing_vouchers_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `voucherID` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `voucherID` (`voucherID`),
  CONSTRAINT `s_emarketing_vouchers_attributes_ibfk_1` FOREIGN KEY (`voucherID`) REFERENCES `s_emarketing_vouchers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_emotion`;
CREATE TABLE `s_emotion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `active` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `cols` int DEFAULT NULL,
  `cell_spacing` int NOT NULL,
  `cell_height` int NOT NULL,
  `article_height` int NOT NULL,
  `rows` int NOT NULL,
  `valid_from` datetime DEFAULT NULL,
  `valid_to` datetime DEFAULT NULL,
  `userID` int DEFAULT NULL,
  `show_listing` int NOT NULL,
  `is_landingpage` int NOT NULL,
  `seo_title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `seo_keywords` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `seo_description` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `create_date` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `template_id` int DEFAULT NULL,
  `device` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT '0,1,2,3,4',
  `fullscreen` int NOT NULL DEFAULT '0',
  `mode` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'masonry',
  `position` int DEFAULT '1',
  `parent_id` int DEFAULT NULL,
  `preview_id` int DEFAULT NULL,
  `preview_secret` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `customer_stream_ids` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `replacement` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `listing_visibility` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'only_start',
  PRIMARY KEY (`id`),
  UNIQUE KEY `preview_id` (`preview_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_emotion` (`id`, `active`, `name`, `cols`, `cell_spacing`, `cell_height`, `article_height`, `rows`, `valid_from`, `valid_to`, `userID`, `show_listing`, `is_landingpage`, `seo_title`, `seo_keywords`, `seo_description`, `create_date`, `modified`, `template_id`, `device`, `fullscreen`, `mode`, `position`, `parent_id`, `preview_id`, `preview_secret`, `customer_stream_ids`, `replacement`, `listing_visibility`) VALUES
(4,	1,	'Startseite',	4,	10,	185,	2,	20,	NULL,	NULL,	50,	0,	0,	'',	'',	'',	'2017-11-07 09:57:28',	'2017-11-07 09:57:29',	1,	'0,1,2,3,4',	0,	'fluid',	1,	NULL,	NULL,	NULL,	NULL,	NULL,	'only_start');

DROP TABLE IF EXISTS `s_emotion_attributes`;
CREATE TABLE `s_emotion_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `emotionID` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `emotionID` (`emotionID`),
  CONSTRAINT `s_emotion_attributes_ibfk_1` FOREIGN KEY (`emotionID`) REFERENCES `s_emotion` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_emotion_categories`;
CREATE TABLE `s_emotion_categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `emotion_id` int NOT NULL,
  `category_id` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_emotion_categories` (`id`, `emotion_id`, `category_id`) VALUES
(5,	4,	3);

DROP TABLE IF EXISTS `s_emotion_element`;
CREATE TABLE `s_emotion_element` (
  `id` int NOT NULL AUTO_INCREMENT,
  `emotionID` int NOT NULL,
  `componentID` int NOT NULL,
  `start_row` int NOT NULL,
  `start_col` int NOT NULL,
  `end_row` int NOT NULL,
  `end_col` int NOT NULL,
  `css_class` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `get_emotion_elements` (`emotionID`,`start_row`,`start_col`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_emotion_element` (`id`, `emotionID`, `componentID`, `start_row`, `start_col`, `end_row`, `end_col`, `css_class`) VALUES
(1,	4,	3,	1,	1,	1,	1,	'');

DROP TABLE IF EXISTS `s_emotion_element_value`;
CREATE TABLE `s_emotion_element_value` (
  `id` int NOT NULL AUTO_INCREMENT,
  `emotionID` int NOT NULL,
  `elementID` int NOT NULL,
  `componentID` int NOT NULL,
  `fieldID` int NOT NULL,
  `value` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `emotionID` (`elementID`),
  KEY `fieldID` (`fieldID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_emotion_element_value` (`id`, `emotionID`, `elementID`, `componentID`, `fieldID`, `value`) VALUES
(1,	4,	1,	3,	65,	'center'),
(2,	4,	1,	3,	3,	'media/image/hq.jpg'),
(3,	4,	1,	3,	7,	'null'),
(4,	4,	1,	3,	47,	''),
(5,	4,	1,	3,	89,	''),
(6,	4,	1,	3,	85,	'');

DROP TABLE IF EXISTS `s_emotion_element_viewports`;
CREATE TABLE `s_emotion_element_viewports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `elementID` int NOT NULL,
  `emotionID` int NOT NULL,
  `alias` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `start_row` int NOT NULL,
  `start_col` int NOT NULL,
  `end_row` int NOT NULL,
  `end_col` int NOT NULL,
  `visible` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_emotion_element_viewports` (`id`, `elementID`, `emotionID`, `alias`, `start_row`, `start_col`, `end_row`, `end_col`, `visible`) VALUES
(1,	1,	4,	'xs',	1,	1,	3,	4,	1),
(2,	1,	4,	's',	1,	1,	3,	4,	1),
(3,	1,	4,	'm',	1,	1,	3,	4,	1),
(4,	1,	4,	'l',	1,	1,	3,	4,	1),
(5,	1,	4,	'xl',	1,	1,	3,	4,	1);

DROP TABLE IF EXISTS `s_emotion_preset_translations`;
CREATE TABLE `s_emotion_preset_translations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `presetID` int unsigned NOT NULL,
  `label` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `locale` varchar(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'de_DE',
  PRIMARY KEY (`id`),
  UNIQUE KEY `presetID` (`presetID`,`locale`),
  CONSTRAINT `s_emotion_preset_translations_preset_fk` FOREIGN KEY (`presetID`) REFERENCES `s_emotion_presets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_emotion_presets`;
CREATE TABLE `s_emotion_presets` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `premium` tinyint(1) NOT NULL DEFAULT '0',
  `custom` tinyint(1) NOT NULL DEFAULT '1',
  `thumbnail` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `preview` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `preset_data` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `required_plugins` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `emotion_translations` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `assets_imported` tinyint(1) NOT NULL DEFAULT '1',
  `hidden` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_emotion_shops`;
CREATE TABLE `s_emotion_shops` (
  `id` int NOT NULL AUTO_INCREMENT,
  `emotion_id` int NOT NULL,
  `shop_id` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_emotion_templates`;
CREATE TABLE `s_emotion_templates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `file` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_emotion_templates` (`id`, `name`, `file`) VALUES
(1,	'Standard',	'index.tpl');

DROP TABLE IF EXISTS `s_es_backend_backlog`;
CREATE TABLE `s_es_backend_backlog` (
  `id` int NOT NULL AUTO_INCREMENT,
  `entity` varchar(500) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `entity_id` int NOT NULL,
  `time` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_es_backlog`;
CREATE TABLE `s_es_backlog` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `payload` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_export`;
CREATE TABLE `s_export` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `last_export` datetime NOT NULL,
  `active` int NOT NULL DEFAULT '0',
  `hash` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `show` int NOT NULL DEFAULT '1',
  `count_articles` int NOT NULL,
  `expiry` datetime NOT NULL,
  `interval` int NOT NULL,
  `formatID` int NOT NULL DEFAULT '1',
  `last_change` datetime NOT NULL,
  `filename` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `encodingID` int NOT NULL DEFAULT '1',
  `categoryID` int DEFAULT NULL,
  `currencyID` int DEFAULT NULL,
  `customergroupID` int DEFAULT NULL,
  `partnerID` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `languageID` int DEFAULT NULL,
  `active_filter` int NOT NULL DEFAULT '1',
  `image_filter` int NOT NULL DEFAULT '0',
  `stockmin_filter` int NOT NULL DEFAULT '0',
  `instock_filter` int NOT NULL,
  `price_filter` double NOT NULL,
  `own_filter` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `header` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `body` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `footer` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `count_filter` int NOT NULL,
  `multishopID` int DEFAULT NULL,
  `variant_export` int unsigned NOT NULL DEFAULT '1',
  `cache_refreshed` datetime DEFAULT NULL,
  `dirty` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_export` (`id`, `name`, `last_export`, `active`, `hash`, `show`, `count_articles`, `expiry`, `interval`, `formatID`, `last_change`, `filename`, `encodingID`, `categoryID`, `currencyID`, `customergroupID`, `partnerID`, `languageID`, `active_filter`, `image_filter`, `stockmin_filter`, `instock_filter`, `price_filter`, `own_filter`, `header`, `body`, `footer`, `count_filter`, `multishopID`, `variant_export`, `cache_refreshed`, `dirty`) VALUES
(1,	'Google Produktsuche',	'2000-01-01 00:00:00',	0,	'4ebfa063359a73c356913df45b3fbe7f',	1,	0,	'2000-01-01 00:00:00',	0,	2,	'0000-00-00 00:00:00',	'export.txt',	1,	NULL,	1,	1,	'',	NULL,	0,	0,	0,	0,	0,	'',	'{strip}\nid{#S#}\ntitel{#S#}\nbeschreibung{#S#}\nlink{#S#}\nbild_url{#S#}\nean{#S#}\ngewicht{#S#}\nmarke{#S#}\nmpn{#S#}\nzustand{#S#}\nproduktart{#S#}\npreis{#S#}\nversand{#S#}\nstandort{#S#}\nwährung\n{/strip}{#L#}',	'{strip}\n{$sArticle.ordernumber|escape}{#S#}\n{$sArticle.name|strip_tags|strip|truncate:80:\"...\":true|escape|htmlentities}{#S#}\n{$sArticle.description_long|strip_tags|html_entity_decode|trim|regex_replace:\"#[^\\wöäüÖÄÜß\\.%&-+ ]#i\":\"\"|strip|truncate:500:\"...\":true|htmlentities|escape}{#S#}\n{$sArticle.articleID|link:$sArticle.name|escape}{#S#}\n{$sArticle.image|image:1}{#S#}\n{$sArticle.ean|escape}{#S#}\n{if $sArticle.weight}{$sArticle.weight|escape:\"number\"}{\" kg\"}{/if}{#S#}\n{$sArticle.supplier|escape}{#S#}\n{$sArticle.suppliernumber|escape}{#S#}\nNeu{#S#}\n{$sArticle.articleID|category:\" > \"|escape}{#S#}\n{$sArticle.price|escape:\"number\"}{#S#}\nDE::DHL:{$sArticle|@shippingcost:\"prepayment\":\"de\"}{#S#}\n{#S#}\n{$sCurrency.currency}\n{/strip}{#L#}',	'',	0,	1,	1,	NULL,	0),
(2,	'Kelkoo',	'2000-01-01 00:00:00',	0,	'f2d27fbba2dabc03789f0ac25f82d93f',	1,	0,	'2000-01-01 00:00:00',	0,	1,	'0000-00-00 00:00:00',	'kelkoo.csv',	1,	NULL,	1,	1,	'',	NULL,	0,	0,	0,	0,	0,	'',	'{strip}\nurl{#S#}\ntitle{#S#}\ndescription{#S#}\nprice{#S#}\nofferid{#S#}\nimage{#S#}\navailability{#S#}\ndeliverycost\n{/strip}{#L#}',	'{strip}\n{$sArticle.articleID|link:$sArticle.name|escape}{#S#}\n{$sArticle.name|escape|truncate:70}{#S#}\n{$sArticle.description_long|strip_tags|strip|trim|truncate:150:\"...\":true|html_entity_decode|escape}{#S#}\n{$sArticle.price|escape:\"number\"}{#S#}\n{$sArticle.ordernumber}{#S#}\n{$sArticle.image|image:2|escape}{#S#}\n{if $sArticle.instock}001{else}002{/if}{#S#}\n{$sArticle|@shippingcost:\"prepayment\":\"de\":\"Deutsche Post Standard\"|escape:\"number\"}\n{/strip}{#L#}',	'',	0,	1,	1,	NULL,	0),
(3,	'billiger.de',	'2000-01-01 00:00:00',	0,	'9ca7fd14bc772898bf01d9904d72c1ea',	1,	0,	'2000-01-01 00:00:00',	0,	1,	'0000-00-00 00:00:00',	'billiger.csv',	1,	NULL,	1,	1,	'',	NULL,	0,	0,	0,	0,	0,	'',	'{strip}\naid{#S#}\nbrand{#S#}\nmpnr{#S#}\nean{#S#}\nname{#S#}\ndesc{#S#}\nshop_cat{#S#}\nprice{#S#}\nppu{#S#}\nlink{#S#}\nimage{#S#}\ndlv_time{#S#}\ndlv_cost{#S#}\npzn\n{/strip}{#L#}',	'{strip}\n{$sArticle.ordernumber}{#S#}\n{$sArticle.supplier|escape}{#S#}\n{$sArticle.suppliernumber|escape}{#S#}\n{$sArticle.ean|escape}{#S#}\n{$sArticle.name|strip_tags|strip|truncate:80:\"...\":true|escape}{#S#}\n{$sArticle.description_long|strip_tags|strip|trim|truncate:900:\"...\":true|html_entity_decode|escape}{#S#}\n{$sArticle.articleID|category:\">\"|escape}{#S#}\n{$sArticle.price|escape:number}{#S#}\n{if $sArticle.purchaseunit}{$sArticle.price/$sArticle.purchaseunit*$sArticle.referenceunit|escape:number} {\"\\x80\"} / {$sArticle.referenceunit} {$sArticle.unit}{/if}{#S#}\n{$sArticle.articleID|link:$sArticle.name|escape}{#S#}\n{$sArticle.image|image:2}{#S#}\n{if $sArticle.instock}2 Tage{elseif $sArticle.shippingtime}{$sArticle.shippingtime} Tage{else}10 Tage{/if}{#S#}\n{$sArticle|@shippingcost:\"prepayment\":\"de\"|escape:number}{#S#}\n\n{/strip}{#L#}',	'',	0,	1,	1,	NULL,	0),
(4,	'Idealo',	'2000-01-01 00:00:00',	0,	'2648057f0020fbeb7e69c238036b25e8',	1,	0,	'2000-01-01 00:00:00',	0,	1,	'0000-00-00 00:00:00',	'idealo.csv',	1,	NULL,	1,	1,	'',	NULL,	0,	0,	0,	0,	0,	'',	'{strip}\nKategorie{#S#}\nHersteller{#S#}\nProduktbezeichnung{#S#}\nPreis{#S#}\nHersteller-Artikelnummer{#S#}\nEAN{#S#}\nPZN{#S#}\nISBN{#S#}\nVersandkosten Nachnahme{#S#}\nVersandkosten Vorkasse{#S#}\nVersandkosten Bankeinzug{#S#}\nDeeplink{#S#}\nLieferzeit{#S#}\nArtikelnummer{#S#}\nLink Produktbild{#S#}\nProdukt Kurztext\n{/strip}{#L#}',	'{strip}\n{$sArticle.articleID|category:\">\"|escape|replace:\"|\":\"\"}{#S#}\n{$sArticle.supplier|replace:\"|\":\"\"}{#S#}\n{$sArticle.name|strip_tags|strip|trim|html_entity_decode|escape}{#S#}\n{$sArticle.price|escape:\"number\"}{#S#}\n{#S#}\n{#S#}\n{#S#}\n{#S#}\n{$sArticle|@shippingcost:\"cash\":\"de\":\"Deutsche Post Standard\"|escape:\"number\"}{#S#}\n{$sArticle|@shippingcost:\"prepayment\":\"de\":\"Deutsche Post Standard\"|escape:\"number\"}{#S#}\n{$sArticle|@shippingcost:\"debit\":\"de\":\"Deutsche Post Standard\"|escape:\"number\"}{#S#}\n{$sArticle.articleID|link:$sArticle.name|replace:\"|\":\"\"}{#S#}\n{if $sArticle.instock}2 Tage{elseif $sArticle.shippingtime}{$sArticle.shippingtime|escape} Tage{else}10 Tage{/if}{#S#}\n{$sArticle.ordernumber|escape}{#S#}\n{$sArticle.image|image:2}{#S#}\n{$sArticle.description_long|strip_tags|strip|trim|truncate:300:\"...\":true|escape}\n{/strip}{#L#}',	'',	0,	1,	1,	NULL,	0),
(5,	'Yatego',	'2000-01-01 00:00:00',	0,	'75838aee39eab65375b5241544035f42',	1,	0,	'2000-01-01 00:00:00',	0,	1,	'0000-00-00 00:00:00',	'yatego.csv',	1,	NULL,	1,	1,	'',	NULL,	0,	0,	0,	0,	0,	'',	'{strip}foreign_id{#S#}\narticle_nr{#S#}\ntitle{#S#}\ntax{#S#}\ncategories{#S#}\nunits{#S#}\nshort_desc{#S#}\nlong_desc{#S#}\npicture{#S#}\nurl{#S#}\nprice{#S#}\nprice_uvp{#S#}\ndelivery_date{#S#}\ntop_offer{#S#}\nstock{#S#}\npackage_size{#S#}\nquantity_unit{#S#}\nmpn{#S#}\nmanufacturer{#S#}\nstatus{#S#}\nvariants\n{/strip}{#L#}',	'{strip}\n{$sArticle.articleID|escape}{#S#}\n{$sArticle.ordernumber|escape}{#S#}\n{$sArticle.name|strip_tags|strip|truncate:80:\"...\":true|replace:\"|\":\"\"} {#S#}\n{$sArticle.tax}{#S#}\n{$sArticle.articleID|category:\">\"|escape},{$sArticle.supplier}{#S#}\n{$sArticle.weight}{#S#}\n{$sArticle.description|strip_tags|strip|trim|truncate:900:\"...\":true|html_entity_decode|replace:\"|\":\"\"|escape}{#S#}\n\"{$sArticle.description_long|trim|html_entity_decode|replace:\"|\":\"|\"|replace:\'\"\':\'\"\"\'}<p>{$sArticle.attr1|regex_replace:\"/^(\\d)$/\":\"\"|regex_replace:\"/^0000-00-00$/\":\"\"|strip}<p>{$sArticle.attr2|regex_replace:\"/^(\\d)$/\":\"\"|regex_replace:\"/^0000-00-00$/\":\"\"|strip}<p>{$sArticle.attr3|regex_replace:\"/^(\\d)$/\":\"\"|regex_replace:\"/^0000-00-00$/\":\"\"|strip}<p>{$sArticle.attr4|regex_replace:\"/^(\\d)$/\":\"\"|regex_replace:\"/^0000-00-00$/\":\"\"|strip}<p>{$sArticle.attr5|regex_replace:\"/^(\\d)$/\":\"\"|regex_replace:\"/^0000-00-00$/\":\"\"|strip}<p>{$sArticle.attr6|regex_replace:\"/^(\\d)$/\":\"\"|regex_replace:\"/^0000-00-00$/\":\"\"|strip}<p>{$sArticle.attr7|regex_replace:\"/^(\\d)$/\":\"\"|regex_replace:\"/^0000-00-00$/\":\"\"|strip}<p>{$sArticle.attr8|regex_replace:\"/^(\\d)$/\":\"\"|regex_replace:\"/^0000-00-00$/\":\"\"|strip}<p>{$sArticle.attr9|regex_replace:\"/^(\\d)$/\":\"\"|regex_replace:\"/^0000-00-00$/\":\"\"|strip}<p>{$sArticle.attr10|regex_replace:\"/^(\\d)$/\":\"\"|regex_replace:\"/^0000-00-00$/\":\"\"|strip}<p>{$sArticle.attr11|regex_replace:\"/^(\\d)$/\":\"\"|regex_replace:\"/^0000-00-00$/\":\"\"|strip}<p>{$sArticle.attr12|regex_replace:\"/^(\\d)$/\":\"\"|regex_replace:\"/^0000-00-00$/\":\"\"|strip}<p>{$sArticle.attr13|regex_replace:\"/^(\\d)$/\":\"\"|regex_replace:\"/^0000-00-00$/\":\"\"|strip}<p>{$sArticle.attr14|regex_replace:\"/^(\\d)$/\":\"\"|regex_replace:\"/^0000-00-00$/\":\"\"|strip}<p>{$sArticle.attr15|regex_replace:\"/^(\\d)$/\":\"\"|regex_replace:\"/^0000-00-00$/\":\"\"|strip}<p>{$sArticle.attr16|regex_replace:\"/^(\\d)$/\":\"\"|regex_replace:\"/^0000-00-00$/\":\"\"|strip}<p>{$sArticle.attr17|regex_replace:\"/^(\\d)$/\":\"\"|regex_replace:\"/^0000-00-00$/\":\"\"|strip}<p>{$sArticle.attr18|regex_replace:\"/^(\\d)$/\":\"\"|regex_replace:\"/^0000-00-00$/\":\"\"|strip}<p>{$sArticle.attr19|regex_replace:\"/^(\\d)$/\":\"\"|regex_replace:\"/^0000-00-00$/\":\"\"|strip}<p>{$sArticle.attr20|regex_replace:\"/^(\\d)$/\":\"\"|regex_replace:\"/^0000-00-00$/\":\"\"|strip}\"{#S#}\n{$sArticle.image|image:2}{#S#}\n{$sArticle.articleID|link:$sArticle.name|replace:\"|\":\"\"}{#S#}\n{if $sArticle.configurator}0{else}{$sArticle.price|escape:\"number\"|escape}{/if}{#S#}\n{$sArticle.pseudoprice|escape}{#S#}\nLieferzeit in Tagen: {$sArticle.shippingtime|replace:\"0\":\"sofort\"}{#S#}\n{$sArticle.topseller}{#S#}\n{if $sArticle.configurator}\"-1\"{else}{$sArticle.instock}{/if}{#S#}\n{$sArticle.purchaseunit}{#S#}\n{$sArticle.unit_description}{#S#}\n{$sArticle.suppliernumber}{#S#}\n{$sArticle.supplier}{#S#}\n{$sArticle.active}{#S#}\n{if $sArticle.configurator}{$sArticle.articleID|escape}{else}{/if}\n{/strip}{#L#}',	'',	0,	1,	1,	NULL,	0),
(6,	'schottenland.de',	'2000-01-01 00:00:00',	0,	'ad16704bf9e58f1f66f99cca7864e63d',	1,	0,	'2000-01-01 00:00:00',	0,	1,	'0000-00-00 00:00:00',	'schottenland.csv',	1,	NULL,	1,	1,	'',	NULL,	0,	0,	0,	0,	0,	'',	'{strip}\nHersteller|\nProduktbezeichnung|\nProduktbeschreibung|\nPreis|\nVerfügbarkeit|\nEAN|\nHersteller AN|\nDeeplink|\nArtikelnummer|\nDAN_Ingram|\nVersandkosten Nachnahme|\nVersandkosten Vorkasse|\nVersandkosten Kreditkarte|\nVersandkosten Bankeinzug\n{/strip}{#L#}',	'{strip}\n{$sArticle.supplier|replace:\"|\":\"\"}|\n{$sArticle.name|strip_tags|strip|truncate:80:\"...\":true|replace:\"|\":\"\"}|\n{$sArticle.description_long|strip_tags|strip|trim|truncate:900:\"...\":true|html_entity_decode|replace:\"|\":\"\"}|\n{$sArticle.price|escape:\"number\"}|\n{if $sArticle.instock}2 Tage{elseif $sArticle.shippingtime}{$sArticle.shippingtime} Tage{else}10 Tage{/if}|\n{$sArticle.ean|replace:\"|\":\"\"}|\n{$sArticle.suppliernumber|replace:\"|\":\"\"}|\n{$sArticle.articleID|link:$sArticle.name|replace:\"|\":\"\"}|\n{$sArticle.ordernumber|replace:\"|\":\"\"}|\n|\n{$sArticle|@shippingcost:\"cash\":\"de\":\"Deutsche Post Standard\"|escape:\"number\"}|\n{$sArticle|@shippingcost:\"prepayment\":\"de\":\"Deutsche Post Standard\"|escape:\"number\"}|\n{$sArticle|@shippingcost:\"credituos\":\"de\":\"Deutsche Post Standard\"|escape:\"number\"}|\n{$sArticle|@shippingcost:\"debit\":\"de\":\"Deutsche Post Standard\"|escape:\"number\"}|\n{/strip}{#L#}',	'',	0,	1,	1,	NULL,	0),
(7,	'guenstiger.de',	'2000-01-01 00:00:00',	0,	'5428e68f168eae36c3882b4cf29730bb',	1,	0,	'2000-01-01 00:00:00',	0,	1,	'0000-00-00 00:00:00',	'guenstiger.csv',	1,	NULL,	1,	1,	'',	NULL,	0,	0,	0,	0,	0,	'',	'{strip}\nBestellnummer|\nHersteller|\nBezeichnung|\nPreis|\nLieferzeit|\nProduktLink|\nFotoLink|\nBeschreibung|\nVersandNachnahme|\nVersandKreditkarte|\nVersandLastschrift|\nVersandBankeinzug|\nVersandRechnung|\nVersandVorkasse|\nEANCode|\nGewicht\n{/strip}{#L#}',	'{strip}\n{$sArticle.ordernumber|replace:\"|\":\"\"}|\n{$sArticle.supplier|replace:\"|\":\"\"}|\n{$sArticle.name|strip_tags|strip|truncate:80:\"...\":true|replace:\"|\":\"\"}|\n{$sArticle.price|escape:\"number\"}|\n{if $sArticle.instock}2 Tage{elseif $sArticle.shippingtime}{$sArticle.shippingtime} Tage{else}10 Tage{/if}|\n{$sArticle.articleID|link:$sArticle.name|replace:\"|\":\"\"}|\n{$sArticle.image|image:0}|\n{$sArticle.description_long|strip_tags|strip|trim|truncate:900:\"...\":true|html_entity_decode|replace:\"|\":\"\"}|\n{$sArticle|@shippingcost:\"cash\":\"de\":\"Deutsche Post Standard\"|escape:\"number\"}|\n|\n{$sArticle|@shippingcost:\"debit\":\"de\":\"Deutsche Post Standard\"|escape:\"number\"}|\n|\n{$sArticle|@shippingcost:\"invoice\":\"de\":\"Deutsche Post Standard\"|escape:\"number\"}|\n{$sArticle|@shippingcost:\"prepayment\":\"de\":\"Deutsche Post Standard\"|escape:\"number\"}|\n{$sArticle.ean|replace:\"|\":\"\"}|\n{$sArticle.weight|replace:\"|\":\"\"}\n{/strip}{#L#}',	'',	0,	1,	1,	NULL,	0),
(8,	'geizhals.at',	'2000-01-01 00:00:00',	0,	'0102715b70fa7d60d61c15c8e025824a',	1,	0,	'2000-01-01 00:00:00',	0,	1,	'0000-00-00 00:00:00',	'geizhals.csv',	1,	NULL,	1,	1,	'',	NULL,	0,	0,	0,	0,	0,	'',	'{strip}\nID{#S#}\nHersteller{#S#}\nArtikelbezeichnung{#S#}\nKategorie{#S#}\nBeschreibungsfeld{#S#}\nBild{#S#}\nUrl{#S#}\nLagerstandl{#S#}\nVersandkosten{#S#}\nVersandkostenNachname{#S#}\nPreis{#S#}\nEAN{#S#}\n{/strip}{#L#}',	'{strip}\n{$sArticle.ordernumber|escape}{#S#}\n{$sArticle.supplier|escape}{#S#}\n{$sArticle.name|escape}{#S#}\n{$sArticle.articleID|category:\">\"|escape}{#S#}\n{$sArticle.description_long|strip_tags|strip|trim|truncate:900:\"...\":true|html_entity_decode|escape}{#S#}\n{$sArticle.image|image:0}{#S#}\n{$sArticle.articleID|link:$sArticle.name|escape}{#S#}\n{if $sArticle.instock}2 Tage{elseif $sArticle.shippingtime}{$sArticle.shippingtime} Tage{else}10 Tage{/if}{#S#}\n{$sArticle|@shippingcost:\"prepayment\":\"de\":\"Deutsche Post Standard\"|escape:\"number\"}{#S#}\n{$sArticle|@shippingcost:\"cash\":\"de\":\"Deutsche Post Standard\"|escape:\"number\"}{#S#}\n{$sArticle.price|escape:\"number\"}{#S#}\n{$sArticle.ean|escape}{#S#}\n{/strip}{#L#}',	'',	0,	1,	1,	NULL,	0),
(9,	'Ciao',	'2000-01-01 00:00:00',	0,	'b8728935bc62480971c0dfdf74eabf6f',	1,	0,	'2000-01-01 00:00:00',	0,	1,	'0000-00-00 00:00:00',	'ciao.csv',	1,	NULL,	1,	1,	'',	NULL,	0,	1,	0,	0,	0,	'',	'{strip}\nOffer ID{#S#}\nBrand{#S#}\nProduct Name{#S#}\nCategory{#S#}\nDescription{#S#}\nImage URL{#S#}\nProduct URL{#S#}\nDelivery{#S#}\nShippingCost{#S#}\nPrice{#S#}\nProduct ID{#S#}\n{/strip}{#L#}',	'{strip}\n{$sArticle.ordernumber|escape}{#S#}\n{$sArticle.supplier|escape}{#S#}\n{$sArticle.name|strip_tags|strip|truncate:80:\"...\":true|escape}{#S#}\n{$sArticle.articleID|category:\">\"|escape}{#S#}\n{$sArticle.description_long|strip_tags|strip|trim|truncate:900:\"...\":true|html_entity_decode|escape}{#S#}\n{$sArticle.image|image:0}{#S#}\n{$sArticle.articleID|link:$sArticle.name|escape}{#S#}\n{if $sArticle.instock}2 Tage{elseif $sArticle.shippingtime}{$sArticle.shippingtime} Tage{else}10 Tage{/if}{#S#}\n{$sArticle|@shippingcost:\"prepayment\":\"de\"|escape:\"number\"}{#S#}\n{$sArticle.price|escape:\"number\"}{#S#}\n{#S#}\n{/strip}{#L#}',	'',	0,	1,	1,	NULL,	0),
(10,	'Pangora',	'2000-01-01 00:00:00',	0,	'162a610b4a85c13fd448f9f5e2290fd5',	1,	0,	'2000-01-01 00:00:00',	0,	1,	'0000-00-00 00:00:00',	'pangora.csv',	1,	NULL,	1,	1,	'',	NULL,	0,	0,	0,	0,	0,	'',	'{strip}\noffer-id{#S#}\nmfname{#S#}\nlabel{#S#}\nmerchant-category{#S#}\ndescription{#S#}\nimage-url{#S#}\noffer-url{#S#}\nships-in{#S#}\nrelease-date{#S#}\ndelivery-charge{#S#}\nprices	old-prices{#S#}\nproduct-id{#S#}\n{/strip}{#L#}',	'{strip}\n{$sArticle.ordernumber|escape}{#S#}\n{$sArticle.supplier|escape}{#S#}\n{$sArticle.name|strip_tags|strip|truncate:80:\"...\":true|escape}{#S#}\n{$sArticle.articleID|category:\">\"|escape}{#S#}\n{$sArticle.description_long|strip_tags|strip|trim|truncate:900:\"...\":true|html_entity_decode|escape}{#S#}\n{$sArticle.image|image:0|escape}{#S#}\n{$sArticle.articleID|link:$sArticle.name|escape}{#S#}\n{if $sArticle.instock}2 Tage{elseif $sArticle.shippingtime}{$sArticle.shippingtime} Tage{else}10 Tage{/if}{#S#}\n{$sArticle.releasedate|escape}{#S#}\n{$sArticle|@shippingcost:\"prepayment\":\"de\":\"Deutsche Post Standard\"|escape:\"number\"}{#S#}\n{$sArticle.price|escape:\"number\"}{#S#}\n{#S#}\n{/strip}{#L#}\n\n',	'',	0,	1,	1,	NULL,	0),
(11,	'Shopping.com',	'2000-01-01 00:00:00',	0,	'cb29f40e760f11b9071d081b8ca8039c',	1,	0,	'2000-01-01 00:00:00',	0,	1,	'0000-00-00 00:00:00',	'shopping.csv',	1,	NULL,	1,	1,	'',	NULL,	0,	0,	0,	0,	0,	'',	'{strip}\nMPN|\nEAN|\nHersteller|\nProduktname|\nProduktbeschreibung|\nPreis|\nProdukt-URL|\nProduktbild-URL|\nKategorie|\nVerfügbar|\nVerfügbarkeitsdetails|\nVersandkosten\n{/strip}{#L#}',	'{strip}\n|\n{$sArticle.ean}|\n{$sArticle.supplier}|\n{$sArticle.name|strip_tags|strip|truncate:80:\"...\":true}|\n{$sArticle.description_long|strip_tags|strip|trim|truncate:900:\"...\":true|html_entity_decode}|\n{$sArticle.price|escape:\"number\"}|\n{$sArticle.articleID|link:$sArticle.name}|\n{$sArticle.image|image:1}|\n{$sArticle.articleID|category:\">\"}|\n{if $sArticle.instock}Ja{else}Nein{/if}|\n{if $sArticle.instock}1-3 Werktage{elseif $sArticle.shippingtime}{$sArticle.shippingtime} Tage{else}10 Tage{/if}|\n{$sArticle|@shippingcost:\"prepayment\":\"de\":\"Deutsche Post Standard\"|escape:\"number\"}\n{/strip}{#L#}',	'',	0,	1,	1,	NULL,	0),
(12,	'Hitmeister',	'2000-01-01 00:00:00',	0,	'76de62d0fd5ec76b483aa6529d36ee45',	1,	0,	'2000-01-01 00:00:00',	0,	1,	'0000-00-00 00:00:00',	'hitmeister.csv',	1,	NULL,	1,	1,	'',	NULL,	0,	0,	0,	0,	0,	'',	'{strip}\nean{#S#}\ncondition{#S#}\nprice{#S#}\ncomment{#S#}\noffer_id{#S#}\nlocation{#S#}\ncount{#S#}\ndelivery_time{#S#}\n{/strip}{#L#}',	'{strip}\n{$sArticle.ean|escape}{#S#}\n100{#S#}\n{$sArticle.price*100}{#S#}\n{#S#}\n{$sArticle.ordernumber|escape}{#S#}\n{#S#}\n{#S#}\n{if $sArticle.instock}b{else}d{/if}{#S#}\n{/strip}{#L#}',	'',	0,	1,	1,	NULL,	0),
(13,	'evendi.de',	'2000-01-01 00:00:00',	0,	'5ac98a759a6f392ea0065a500acf82e6',	1,	0,	'2000-01-01 00:00:00',	0,	1,	'0000-00-00 00:00:00',	'evendi.csv',	1,	NULL,	1,	1,	'',	NULL,	0,	0,	0,	0,	0,	'',	'{strip}\nEindeutige Händler-Artikelnummer{#S#}\nPreis in Euro{#S#}\nKategorie{#S#}\nProduktbezeichnung{#S#}\nProduktbeschreibung{#S#}\nLink auf Detailseite{#S#}\nLieferzeit{#S#}\nEAN-Nummer{#S#}\nHersteller-Artikelnummer{#S#}\nLink auf Produktbild{#S#}\nHersteller{#S#}\nVersandVorkasse{#S#}\nVersandNachnahme{#S#}\nVersandLastschrift{#S#}\nVersandKreditkarte{#S#}\nVersandRechnung{#S#}\nVersandPayPal\n{/strip}{#L#}',	'{strip}\n{$sArticle.ordernumber|escape}{#S#}\n{$sArticle.price|escape:\"number\"|escape}{#S#}\n{$sArticle.articleID|category:\">\"|escape}{#S#}\n{$sArticle.name|strip_tags|strip|truncate:80:\"...\":true|escape}{#S#}\n{$sArticle.description_long|strip_tags|strip|trim|truncate:900:\"...\":true|html_entity_decode|escape}{#S#}\n{$sArticle.articleID|link:$sArticle.name|escape}{#S#}\n{#F#}{if $sArticle.instock}1-3 Werktage{elseif $sArticle.shippingtime}{$sArticle.shippingtime} Tage{else}10 Tage{/if}{#F#}{#S#}\n{$sArticle.ean|escape}{#S#}\n{$sArticle.suppliernumber|escape}{#S#}\n{$sArticle.image|image:0|escape}{#S#}\n{$sArticle.supplier|escape}{#S#}\n{$sArticle|@shippingcost:\"prepayment\":\"de\"|escape:\"number\"|escape}{#S#}\n{$sArticle|@shippingcost:\"cash\":\"de\"|escape:\"number\"|escape}{#S#}\n{$sArticle|@shippingcost:\"debit\":\"de\"|escape:\"number\"|escape}{#S#}\n{\"\"|escape}{#S#}\n{$sArticle|@shippingcost:\"invoice\":\"de\"|escape:\"number\"|escape}{#S#}\n{$sArticle|@shippingcost:\"paypal\":\"de\"|escape:\"number\"|escape}{#S#}\n{/strip}{#L#}',	'',	0,	1,	1,	NULL,	0),
(14,	'affili.net',	'2000-01-01 00:00:00',	0,	'bc960c18cbeea9038314d040e7dc92f5',	1,	0,	'2000-01-01 00:00:00',	0,	1,	'0000-00-00 00:00:00',	'affilinet.csv',	1,	NULL,	1,	1,	'',	NULL,	0,	0,	0,	0,	0,	'',	'{strip}\nart_number{#S#}\ncategory{#S#}\ntitle{#S#}\ndescription{#S#}\nprice{#S#}\nimg_url{#S#}\ndeeplink1{#S#}\n{/strip}{#L#}',	'{strip}\n{$sArticle.ordernumber}{#S#}\n{$sArticle.articleID|category:\">\"|escape}{#S#}\n{$sArticle.name|strip_tags|strip|truncate:80:\"...\":true|escape}{#S#}\n{$sArticle.description_long|strip_tags|strip|trim|truncate:900:\"...\":true|html_entity_decode|escape}{#S#}\n{$sArticle.price|escape:\"number\"}{#S#}\n{$sArticle.image|image:2|escape}{#S#}\n{$sArticle.articleID|link:$sArticle.name|escape}{#S#}\n{/strip}{#L#}',	'',	0,	1,	1,	NULL,	0),
(15,	'Google Produktsuche XML',	'2000-01-01 00:00:00',	0,	'e8eca3b3bbbad77afddb67b8138900e1',	1,	0,	'2000-01-01 00:00:00',	0,	3,	'2008-09-27 09:52:17',	'export.xml',	2,	NULL,	1,	1,	'',	NULL,	0,	0,	0,	0,	0,	'',	'<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n<rss version=\"2.0\" xmlns:g=\"http://base.google.com/ns/1.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\">\n<channel>\n	<atom:link href=\"http://{$sConfig.sBASEPATH}/engine/connectors/export/{$sSettings.id}/{$sSettings.hash}/{$sSettings.filename}\" rel=\"self\" type=\"application/rss+xml\" />\n	<title>{$sConfig.sSHOPNAME}</title>\n	<description>Beschreibung im Header hinterlegen</description>\n	<link>http://{$sConfig.sBASEPATH}</link>\n	<language>DE</language>\n	<image>\n		<url>http://{$sConfig.sBASEPATH}/templates/_default/frontend/_resources/images/logo.jpg</url>\n		<title>{$sConfig.sSHOPNAME}</title>\n		<link>http://{$sConfig.sBASEPATH}</link>\n	</image>',	'<item> \n    <g:id>{$sArticle.articleID|escape}</g:id>\n	<title>{$sArticle.name|strip_tags|strip|truncate:80:\"...\":true|escape}</title>\n	<description>{$sArticle.description_long|strip_tags|strip|truncate:900:\"...\"|escape}</description>\n	<g:google_product_category>Wählen Sie hier Ihre Google Produkt-Kategorie</g:google_product_category>\n	<g:product_type>{$sArticle.articleID|category:\" > \"|escape}</g:product_type>\n	<link>{$sArticle.articleID|link:$sArticle.name|escape}</link>\n	<g:image_link>{$sArticle.image|image:1}</g:image_link>\n	<g:condition>neu</g:condition>\n	<g:availability>{if $sArticle.esd}bestellbar{elseif $sArticle.instock>0}bestellbar{elseif $sArticle.releasedate && $sArticle.releasedate|strtotime > $smarty.now}vorbestellt{elseif $sArticle.shippingtime}bestellbar{else}nicht auf lager{/if}</g:availability>\n	<g:price>{$sArticle.price|format:\"number\"}</g:price>\n	<g:brand>{$sArticle.supplier|escape}</g:brand>\n	<g:gtin>{$sArticle.suppliernumber|replace:\"|\":\"\"}</g:gtin>\n	<g:mpn>{$sArticle.suppliernumber|escape}</g:mpn>\n	<g:shipping>\n       <g:country>DE</g:country>\n       <g:service>Standard</g:service>\n       <g:price>{$sArticle|@shippingcost:\"prepayment\":\"de\"|escape:number}</g:price>\n    </g:shipping>\n  {if $sArticle.changed}<pubDate>{$sArticle.changed|date_format:\"%a, %d %b %Y %T %Z\"}</pubDate>{/if}		\n</item>',	'</channel>\n</rss>',	0,	1,	1,	NULL,	0),
(16,	'preissuchmaschine.de',	'2000-01-01 00:00:00',	0,	'67fbbab544165d9d4e5352f9a12054a0',	1,	0,	'2000-01-01 00:00:00',	0,	1,	'0000-00-00 00:00:00',	'preissuchmaschine.csv',	1,	NULL,	1,	1,	'',	NULL,	0,	0,	0,	0,	0,	'',	'{strip}\nBestellnummer|\nHersteller|\nBezeichnung|\nPreis|\nLieferzeit|\nProduktLink|\nFotoLink|\nBeschreibung|\nVersandNachnahme|\nVersandKreditkarte|\nVersandLastschrift|\nVersandBankeinzug|\nVersandRechnung|\nVersandVorkasse|\nEANCode|\nGewicht\n{/strip}{#L#}',	'{strip}\n{$sArticle.ordernumber|replace:\"|\":\"\"}|\n{$sArticle.supplier|replace:\"|\":\"\"}|\n{$sArticle.name|strip_tags|strip|truncate:80:\"...\":true|replace:\"|\":\"\"}|\n{$sArticle.price|escape:\"number\"}|\n{if $sArticle.instock}2 Tage{elseif $sArticle.shippingtime}{$sArticle.shippingtime} Tage{else}10 Tage{/if}|\n{$sArticle.articleID|link:$sArticle.name|replace:\"|\":\"\"}|\n{$sArticle.image|image:0}|\n{$sArticle.description_long|strip_tags|strip|trim|truncate:900:\"...\":true|html_entity_decode|replace:\"|\":\"\"}|\n{$sArticle|@shippingcost:\"cash\":\"de\":\"Deutsche Post Standard\"|escape:\"number\"}|\n|\n{$sArticle|@shippingcost:\"debit\":\"de\":\"Deutsche Post Standard\"|escape:\"number\"}|\n|\n{$sArticle|@shippingcost:\"invoice\":\"de\":\"Deutsche Post Standard\"|escape:\"number\"}|\n{$sArticle|@shippingcost:\"prepayment\":\"de\":\"Deutsche Post Standard\"|escape:\"number\"}|\n{$sArticle.ean|replace:\"|\":\"\"}|\n{$sArticle.weight|replace:\"|\":\"\"}\n{/strip}{#L#}',	'',	0,	1,	1,	NULL,	0),
(17,	'RSS Feed-Template',	'2000-01-01 00:00:00',	0,	'3a6ff2a4f921a10d33d9b9ec25529a5d',	1,	0,	'2000-01-01 00:00:00',	0,	3,	'0000-00-00 00:00:00',	'export.xml',	2,	NULL,	1,	1,	'',	NULL,	0,	0,	0,	0,	0,	'',	'<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\">\n<channel>\n	<atom:link href=\"http://{$sConfig.sBASEPATH}/engine/connectors/export/{$sSettings.id}/{$sSettings.hash}/{$sSettings.filename}\" rel=\"self\" type=\"application/rss+xml\" />\n	<title>{$sConfig.sSHOPNAME}</title>\n	<description>Shopbeschreibung ...</description>\n	<link>http://{$sConfig.sBASEPATH}</link>\n	<language>{$sLanguage.isocode}-{$sLanguage.isocode}</language>\n	<image>\n		<url>http://{$sConfig.sBASEPATH}/templates/0/de/media/img/default/store/logo.gif</url>\n		<title>{$sConfig.sSHOPNAME}</title>\n		<link>http://{$sConfig.sBASEPATH}</link>\n	</image>{#L#}',	'<item> \n	<title>{$sArticle.name|strip_tags|htmlspecialchars_decode|strip|escape}</title>\n	<guid>{$sArticle.articleID|link:$sArticle.name|escape}</guid>\n	<link>{$sArticle.articleID|link:$sArticle.name}</link>\n	<description>{if $sArticle.image}\n		<a href=\"{$sArticle.articleID|link:$sArticle.name}\" style=\"border:0 none;\">\n			<img src=\"{$sArticle.image|image:0}\" align=\"right\" style=\"padding: 0pt 0pt 12px 12px; float: right;\" />\n		</a>\n{/if}\n		{$sArticle.description_long|strip_tags|regex_replace:\"/[^\\wöäüÖÄÜß .?!,&:%;\\-\\\"\']/i\":\"\"|trim|truncate:900:\"...\"|escape}\n	</description>\n	<category>{$sArticle.articleID|category:\">\"|htmlspecialchars_decode|escape}</category>\n{if $sArticle.changed} 	{assign var=\"sArticleChanged\" value=$sArticle.changed|strtotime}<pubDate>{\"r\"|date:$sArticleChanged}</pubDate>{\"rn\"}{/if}\n</item>{#L#}',	'</channel>\n</rss>',	0,	1,	1,	NULL,	0);

DROP TABLE IF EXISTS `s_export_articles`;
CREATE TABLE `s_export_articles` (
  `feedID` int NOT NULL,
  `articleID` int NOT NULL,
  PRIMARY KEY (`feedID`,`articleID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_export_attributes`;
CREATE TABLE `s_export_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `exportID` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `exportID` (`exportID`),
  CONSTRAINT `s_export_attributes_ibfk_1` FOREIGN KEY (`exportID`) REFERENCES `s_export` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_export_categories`;
CREATE TABLE `s_export_categories` (
  `feedID` int NOT NULL,
  `categoryID` int NOT NULL,
  PRIMARY KEY (`feedID`,`categoryID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_export_suppliers`;
CREATE TABLE `s_export_suppliers` (
  `feedID` int NOT NULL,
  `supplierID` int NOT NULL,
  PRIMARY KEY (`feedID`,`supplierID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_filter`;
CREATE TABLE `s_filter` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `position` int NOT NULL,
  `comparable` int NOT NULL,
  `sortmode` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `get_sets_query` (`position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_filter` (`id`, `name`, `position`, `comparable`, `sortmode`) VALUES
(1,	'Kleidung',	0,	0,	0),
(2,	'Essen',	0,	0,	0),
(3,	'Elektronik',	0,	0,	0),
(4,	'Freizeit',	0,	0,	0);

DROP TABLE IF EXISTS `s_filter_articles`;
CREATE TABLE `s_filter_articles` (
  `articleID` int unsigned NOT NULL,
  `valueID` int unsigned NOT NULL,
  PRIMARY KEY (`articleID`,`valueID`),
  KEY `valueID` (`valueID`),
  KEY `articleID` (`articleID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_filter_articles` (`articleID`, `valueID`) VALUES
(7,	1),
(9,	1),
(14,	1),
(4,	2),
(5,	2),
(6,	2),
(7,	2),
(8,	2),
(9,	2),
(7,	3),
(9,	3),
(5,	4),
(7,	4),
(9,	4),
(4,	5),
(5,	5),
(7,	5),
(8,	5),
(9,	5),
(14,	5),
(4,	6),
(7,	6),
(9,	6),
(14,	6),
(10,	8),
(13,	8),
(11,	9),
(10,	10),
(11,	10),
(13,	10),
(10,	11),
(12,	12),
(11,	13),
(6,	14),
(14,	14),
(1,	16),
(4,	16),
(8,	16),
(1,	17),
(2,	17),
(4,	17),
(5,	17),
(8,	17),
(14,	17),
(1,	18),
(2,	18),
(4,	18),
(6,	18),
(7,	18),
(8,	18),
(9,	18),
(2,	19),
(1,	20),
(2,	20),
(10,	22),
(12,	22),
(7,	23),
(11,	23),
(12,	23),
(10,	24),
(4,	26),
(6,	27),
(2,	28),
(3,	29),
(6,	30),
(4,	31),
(9,	32),
(14,	33);

DROP TABLE IF EXISTS `s_filter_attributes`;
CREATE TABLE `s_filter_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `filterID` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `filterID` (`filterID`),
  CONSTRAINT `s_filter_attributes_ibfk_1` FOREIGN KEY (`filterID`) REFERENCES `s_filter` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_filter_options`;
CREATE TABLE `s_filter_options` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `filterable` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `get_options_query` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_filter_options` (`id`, `name`, `filterable`) VALUES
(1,	'Größe',	1),
(2,	'Material',	1),
(3,	'Zutaten',	1),
(4,	'Zielgruppe',	1),
(5,	'Artikeltypen',	1);

DROP TABLE IF EXISTS `s_filter_options_attributes`;
CREATE TABLE `s_filter_options_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `optionID` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `optionID` (`optionID`),
  CONSTRAINT `s_filter_options_attributes_ibfk_1` FOREIGN KEY (`optionID`) REFERENCES `s_filter_options` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_filter_relations`;
CREATE TABLE `s_filter_relations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `groupID` int NOT NULL,
  `optionID` int NOT NULL,
  `position` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `groupID` (`groupID`,`optionID`),
  KEY `get_set_assigns_query` (`groupID`,`position`),
  KEY `groupID_2` (`groupID`),
  KEY `optionID` (`optionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_filter_relations` (`id`, `groupID`, `optionID`, `position`) VALUES
(1,	1,	1,	0),
(7,	4,	4,	0),
(8,	1,	2,	0),
(9,	1,	4,	0),
(10,	2,	3,	0),
(11,	3,	2,	0),
(12,	3,	4,	0),
(13,	4,	2,	0),
(14,	4,	1,	0),
(15,	1,	5,	0),
(16,	2,	5,	0),
(17,	3,	5,	0),
(18,	4,	5,	0);

DROP TABLE IF EXISTS `s_filter_values`;
CREATE TABLE `s_filter_values` (
  `id` int NOT NULL AUTO_INCREMENT,
  `optionID` int NOT NULL,
  `value` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `position` int NOT NULL,
  `media_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `optionID` (`optionID`,`value`),
  KEY `get_property_value_by_option_id_query` (`optionID`,`position`),
  KEY `optionID_2` (`optionID`),
  KEY `filters_order_by_position` (`optionID`,`position`,`id`),
  KEY `filters_order_by_numeric` (`optionID`,`id`),
  KEY `filters_order_by_alphanumeric` (`optionID`,`value`,`id`),
  KEY `media_id` (`media_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_filter_values` (`id`, `optionID`, `value`, `position`, `media_id`) VALUES
(1,	1,	'S',	0,	NULL),
(2,	1,	'M',	1,	NULL),
(3,	1,	'L',	2,	NULL),
(4,	1,	'XL',	3,	NULL),
(5,	2,	'Baumwolle',	0,	NULL),
(6,	2,	'Polyester',	1,	NULL),
(7,	2,	'Seide',	2,	NULL),
(8,	3,	'Zucker',	0,	NULL),
(9,	3,	'Salz',	1,	NULL),
(10,	3,	'Milch',	2,	NULL),
(11,	3,	'Pfeffer',	3,	NULL),
(12,	3,	'Fisch',	4,	NULL),
(13,	3,	'Weizen',	5,	NULL),
(14,	2,	'Leder',	3,	NULL),
(15,	2,	'Nylon',	4,	NULL),
(16,	4,	'Kinder',	0,	NULL),
(17,	4,	'Frau',	1,	NULL),
(18,	4,	'Mann',	2,	NULL),
(19,	2,	'Edelstahl',	5,	NULL),
(20,	2,	'Kunststoff',	6,	NULL),
(21,	5,	'Beispiele für Lieferbarkeit',	0,	NULL),
(22,	5,	'Mit Zubehör-Artikel',	1,	NULL),
(23,	5,	'Mit ähnliche Artikel',	2,	NULL),
(24,	5,	'Grundpreis',	3,	NULL),
(25,	5,	'Staffelpreise',	4,	NULL),
(26,	5,	'Pseudopreis',	5,	NULL),
(27,	5,	'Hervorgehoben',	6,	NULL),
(28,	5,	'Benachrichtigung',	7,	NULL),
(29,	5,	'Download Artikel',	8,	NULL),
(30,	5,	'Versandkostenfrei',	9,	NULL),
(31,	5,	'Standardkonfigurator',	10,	NULL),
(32,	5,	'Auswahlkonfigurator',	11,	NULL),
(33,	5,	'Bildkonfigurator',	12,	NULL);

DROP TABLE IF EXISTS `s_filter_values_attributes`;
CREATE TABLE `s_filter_values_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `valueID` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `valueID` (`valueID`),
  CONSTRAINT `s_filter_values_attributes_ibfk_1` FOREIGN KEY (`valueID`) REFERENCES `s_filter_values` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_library_component`;
CREATE TABLE `s_library_component` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `x_type` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `convert_function` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `template` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `cls` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `pluginID` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_idx` (`name`,`pluginID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_library_component` (`id`, `name`, `x_type`, `convert_function`, `description`, `template`, `cls`, `pluginID`) VALUES
(2,	'Text Element',	'emotion-components-html-element',	NULL,	'',	'component_html',	'html-text-element',	NULL),
(3,	'banner',	'emotion-components-banner',	'getBannerMappingLinks',	'',	'component_banner',	'banner-element',	NULL),
(4,	'product',	'emotion-components-article',	'getArticle',	'',	'component_article',	'article-element',	NULL),
(5,	'category_teaser',	'emotion-components-category-teaser',	'getCategoryTeaser',	'',	'component_category_teaser',	'category-teaser-element',	NULL),
(6,	'blog_article',	'emotion-components-blog',	'getBlogEntry',	'',	'component_blog',	'blog-element',	NULL),
(7,	'banner_slider',	'emotion-components-banner-slider',	'getBannerSlider',	'',	'component_banner_slider',	'banner-slider-element',	NULL),
(8,	'youtube',	'emotion-components-youtube',	NULL,	'',	'component_youtube',	'youtube-element',	NULL),
(9,	'iframe',	'emotion-components-iframe',	NULL,	'',	'component_iframe',	'iframe-element',	NULL),
(10,	'manufacturer_slider',	'emotion-components-manufacturer-slider',	'getManufacturerSlider',	'',	'component_manufacturer_slider',	'manufacturer-slider-element',	NULL),
(11,	'product_slider',	'emotion-components-article-slider',	'getArticleSlider',	'',	'component_article_slider',	'article-slider-element',	NULL),
(12,	'html_video',	'emotion-components-html-video',	'getHtml5Video',	'',	'component_video',	'emotion--element-video',	NULL),
(13,	'code_element',	'emotion-components-html-code',	NULL,	'',	'component_html_code',	'html-code-element',	NULL),
(14,	'content_type',	'emotion-components-content-type',	NULL,	'',	'component_content_type',	'content-type-element',	NULL);

DROP TABLE IF EXISTS `s_library_component_field`;
CREATE TABLE `s_library_component_field` (
  `id` int NOT NULL AUTO_INCREMENT,
  `componentID` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `x_type` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `value_type` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `field_label` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `support_text` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `help_title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `help_text` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `store` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `display_field` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `value_field` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `default_value` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `allow_blank` int NOT NULL,
  `translatable` int NOT NULL DEFAULT '0',
  `position` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_library_component_field` (`id`, `componentID`, `name`, `x_type`, `value_type`, `field_label`, `support_text`, `help_title`, `help_text`, `store`, `display_field`, `value_field`, `default_value`, `allow_blank`, `translatable`, `position`) VALUES
(3,	3,	'file',	'mediaselectionfield',	'',	'Bild',	'',	'',	'',	'',	'',	'',	'',	0,	0,	3),
(4,	2,	'text',	'tinymce',	'',	'Text',	'Anzuzeigender Text',	'HTML-Text',	'Geben Sie hier den Text ein der im Element angezeigt werden soll.',	'',	'',	'',	'',	0,	1,	4),
(5,	4,	'article',	'emotion-components-fields-article',	'',	'Artikelsuche',	'Der anzuzeigende Artikel',	'Lorem ipsum dolor',	'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam',	'',	'',	'',	'',	0,	0,	9),
(6,	2,	'cms_title',	'textfield',	'',	'Titel',	'',	'',	'',	'',	'',	'',	'',	1,	1,	6),
(7,	3,	'bannerMapping',	'hidden',	'json',	'',	'',	'',	'',	'',	'',	'',	'',	0,	0,	7),
(8,	4,	'article_type',	'emotion-components-fields-article-type',	'',	'Typ des Artikels',	'',	'',	'',	'',	'',	'',	'',	0,	0,	8),
(9,	5,	'image_type',	'emotion-components-fields-category-image-type',	'',	'Typ des Bildes',	'',	'',	'',	'',	'',	'',	'',	0,	0,	9),
(10,	5,	'image',	'mediaselectionfield',	'',	'Bild',	'',	'',	'',	'',	'',	'',	'',	1,	0,	10),
(11,	5,	'category_selection',	'emotion-components-fields-category-selection',	'',	'',	'',	'',	'',	'',	'',	'',	'',	0,	0,	11),
(12,	6,	'entry_amount',	'numberfield',	'',	'Anzahl',	'',	'',	'',	'',	'',	'',	'',	0,	0,	12),
(13,	7,	'banner_slider_title',	'textfield',	'',	'Überschrift',	'',	'',	'',	'',	'',	'',	'',	1,	1,	13),
(15,	7,	'banner_slider_arrows',	'checkbox',	'',	'Pfeile anzeigen',	'',	'',	'',	'',	'',	'',	'',	0,	0,	15),
(16,	7,	'banner_slider_numbers',	'checkbox',	'',	'Nummern ausgeben',	'Bitte beachten Sie, dass diese Einstellung nur Auswirkungen auf das \"Emotion\"-Template hat.',	'',	'',	'',	'',	'',	'',	0,	0,	16),
(17,	7,	'banner_slider_scrollspeed',	'numberfield',	'',	'Scroll-Geschwindigkeit',	'',	'',	'',	'',	'',	'',	'',	0,	0,	17),
(18,	7,	'banner_slider_rotation',	'checkbox',	'',	'Automatisch rotieren',	'',	'',	'',	'',	'',	'',	'',	0,	0,	18),
(19,	7,	'banner_slider_rotatespeed',	'numberfield',	'',	'Rotations Geschwindigkeit',	'',	'',	'',	'',	'',	'',	'5000',	0,	0,	19),
(20,	7,	'banner_slider',	'hidden',	'json',	'',	'',	'',	'',	'',	'',	'',	'',	0,	0,	20),
(22,	8,	'video_id',	'textfield',	'',	'Youtube-Video ID',	'',	'',	'',	'',	'',	'',	'',	0,	1,	22),
(23,	8,	'video_hd',	'checkbox',	'',	'HD-Video verwenden',	'',	'',	'',	'',	'',	'',	'',	0,	0,	23),
(24,	9,	'iframe_url',	'textfield',	'',	'URL',	'',	'',	'',	'',	'',	'',	'',	0,	1,	24),
(25,	10,	'manufacturer_type',	'emotion-components-fields-manufacturer-type',	'',	'',	'',	'',	'',	'',	'',	'',	'',	0,	0,	25),
(26,	10,	'manufacturer_category',	'emotion-components-fields-category-selection',	'',	'',	'',	'',	'',	'',	'',	'',	'',	1,	0,	26),
(27,	10,	'selected_manufacturers',	'hidden',	'json',	'',	'',	'',	'',	'',	'',	'',	'',	0,	0,	27),
(28,	10,	'manufacturer_slider_title',	'textfield',	'',	'Überschrift',	'',	'',	'',	'',	'',	'',	'',	1,	1,	28),
(30,	10,	'manufacturer_slider_arrows',	'checkbox',	'',	'Pfeile anzeigen',	'',	'',	'',	'',	'',	'',	'',	0,	0,	30),
(32,	10,	'manufacturer_slider_scrollspeed',	'numberfield',	'',	'Scroll-Geschwindigkeit',	'',	'',	'',	'',	'',	'',	'',	0,	0,	32),
(33,	10,	'manufacturer_slider_rotation',	'checkbox',	'',	'Automatisch rotieren',	'',	'',	'',	'',	'',	'',	'',	0,	0,	33),
(34,	10,	'manufacturer_slider_rotatespeed',	'numberfield',	'',	'Rotations Geschwindigkeit',	'',	'',	'',	'',	'',	'',	'5000',	0,	0,	34),
(36,	11,	'article_slider_type',	'emotion-components-fields-article-slider-type',	'',	'',	'',	'',	'',	'',	'',	'',	'',	0,	0,	36),
(37,	11,	'selected_articles',	'hidden',	'',	'',	'',	'',	'',	'',	'',	'',	'',	0,	0,	100),
(38,	11,	'article_slider_max_number',	'numberfield',	'',	'max. Anzahl',	'',	'',	'',	'',	'',	'',	'',	0,	0,	39),
(39,	11,	'article_slider_title',	'textfield',	'',	'Überschrift',	'',	'',	'',	'',	'',	'',	'',	1,	1,	40),
(41,	11,	'article_slider_arrows',	'checkbox',	'',	'Pfeile anzeigen',	'',	'',	'',	'',	'',	'',	'',	0,	0,	42),
(43,	11,	'article_slider_scrollspeed',	'numberfield',	'',	'Scroll-Geschwindigkeit',	'',	'',	'',	'',	'',	'',	'',	0,	0,	44),
(44,	11,	'article_slider_rotation',	'checkbox',	'',	'Automatisch rotieren',	'',	'',	'',	'',	'',	'',	'',	0,	0,	45),
(45,	11,	'article_slider_rotatespeed',	'numberfield',	'',	'Rotations Geschwindigkeit',	'',	'',	'',	'',	'',	'',	'5000',	0,	0,	46),
(47,	3,	'link',	'textfield',	'',	'Link',	'',	'',	'',	'',	'',	'',	'',	1,	1,	47),
(48,	5,	'blog_category',	'checkboxfield',	'',	'Blog-Kategorie',	'Bei der ausgewählten Kategorie handelt es sich um eine Blog-Kategorie',	'',	'',	'',	'',	'',	'',	0,	0,	48),
(59,	11,	'article_slider_category',	'emotion-components-fields-category-selection',	'',	'',	'',	'',	'',	'',	'',	'',	'',	1,	0,	38),
(65,	3,	'bannerPosition',	'hidden',	'',	'',	'',	'',	'',	'',	'',	'',	'center',	0,	0,	NULL),
(66,	4,	'productImageOnly',	'checkboxfield',	'',	'Nur Produktbild',	'Bei aktivierter Einstellung wird nur das Produktbild dargestellt.',	'',	'',	'',	'label',	'key',	'',	0,	0,	10),
(68,	6,	'blog_entry_selection',	'emotion-components-fields-category-selection',	'',	'Kategorie',	'',	'',	'',	'',	'label',	'key',	'',	0,	0,	10),
(69,	12,	'videoMode',	'emotion-components-fields-video-mode',	'',	'Modus',	'Bestimmen Sie das Verhalten des Videos. Legen Sie fest, ob das Video skalierend, füllend oder gestreckt dargestellt werden soll.',	'',	'',	'',	'label',	'key',	'',	0,	0,	40),
(70,	12,	'overlay',	'textfield',	'',	'Overlay Farbe',	'Legen Sie eine Hintergrundfarbe für das Overlay fest. Ein RGBA-Wert wird empfohlen.',	'',	'',	'',	'',	'',	'rgba(0, 0, 0, .2)',	1,	0,	71),
(71,	12,	'originTop',	'numberfield',	'',	'Oberer Ausgangspunkt',	'Legt den oberen Ausgangspunkt für die Skalierung des Videos fest. Die Angabe erfolgt in Prozent.',	'',	'',	'',	'',	'',	'50',	1,	0,	69),
(72,	12,	'originLeft',	'numberfield',	'',	'Linker Ausgangspunkt',	'Legt den linken Ausgangspunkt für die Skalierung des Videos fest. Die Angabe erfolgt in Prozent.',	'',	'',	'',	'',	'',	'50',	1,	0,	68),
(73,	12,	'scale',	'numberfield',	'',	'Zoom-Faktor',	'Wenn Sie den Modus Füllen gewählt haben können Sie den Zoom-Faktor mit dieser Option ändern.',	'',	'',	'',	'',	'',	'1.0',	1,	0,	67),
(74,	12,	'muted',	'checkbox',	'',	'Video stumm schalten',	'Die Ton-Spur des Videos wird stumm geschaltet',	'',	'',	'',	'',	'',	'1',	1,	0,	60),
(75,	12,	'loop',	'checkbox',	'',	'Video schleifen',	'Das Video wird in einer Dauerschleife angezeigt',	'',	'',	'',	'',	'',	'1',	1,	0,	59),
(76,	12,	'controls',	'checkbox',	'',	'Video-Steuerung anzeigen',	'Nicht für den Modus Füllen oder Strecken empfohlen.',	'',	'',	'',	'',	'',	'1',	1,	0,	58),
(77,	12,	'autobuffer',	'checkbox',	'',	'Video automatisch vorladen',	'',	'',	'',	'',	'',	'',	'1',	1,	0,	57),
(78,	12,	'autoplay',	'checkbox',	'',	'Video automatisch abspielen',	'',	'',	'',	'',	'',	'',	'1',	1,	0,	56),
(79,	12,	'html_text',	'tinymce',	'',	'Overlay Text',	'Sie können ein Overlay mit einem Text über das Video legen.',	'',	'',	'',	'',	'',	'',	1,	0,	70),
(80,	12,	'fallback_picture',	'mediatextfield',	'',	'Vorschau-Bild',	'Das Vorschau-Bild wird gezeigt wenn das Video noch nicht abgespielt wird.',	'',	'',	'',	'',	'',	'',	0,	0,	44),
(81,	12,	'h264_video',	'mediatextfield',	'',	'.mp4 Video',	'Video für Browser mit MP4 Support. Auch externer Pfad möglich.',	'',	'',	'',	'',	'',	'',	0,	0,	43),
(82,	12,	'ogg_video',	'mediatextfield',	'',	'.ogv/.ogg Video',	'Video für Browser mit Ogg Support. Auch externer Pfad möglich.',	'',	'',	'',	'',	'',	'',	0,	0,	42),
(83,	12,	'webm_video',	'mediatextfield',	'',	'.webm Video',	'Video für Browser mit WebM Support. Auch externer Pfad möglich.',	'',	'',	'',	'',	'',	'',	0,	0,	41),
(84,	2,	'needsNoStyling',	'checkbox',	'',	'Kein Styling hinzufügen',	'Definiert, dass kein weiteres Layout-Styling hinzugefügt wird.',	'',	'',	'',	'',	'',	'0',	0,	0,	10),
(85,	3,	'title',	'textfield',	'',	'Title Text',	'',	'',	'',	'',	'',	'',	'',	1,	1,	50),
(86,	11,	'article_slider_stream',	'productstreamselection',	'',	'',	'',	'',	'',	'',	'name',	'id',	'',	0,	0,	38),
(87,	13,	'javascript',	'codemirrorfield',	'',	'JavaScript Code',	'',	'',	'',	'',	'',	'',	'',	1,	1,	0),
(88,	13,	'smarty',	'codemirrorfield',	'',	'HTML Code',	'',	'',	'',	'',	'',	'',	'',	1,	1,	1),
(89,	3,	'banner_link_target',	'emotion-components-fields-link-target',	'',	'Link-Ziel',	'',	'',	'',	'',	'',	'',	'',	1,	0,	48),
(90,	4,	'article_category',	'emotion-components-fields-category-selection',	'',	'Kategorie',	'',	'',	'',	'',	'',	'',	'',	1,	0,	9),
(91,	4,	'no_border',	'checkbox',	'',	'',	'',	'',	'',	'',	'',	'',	'',	1,	0,	90),
(92,	11,	'no_border',	'checkbox',	'',	'',	'',	'',	'',	'',	'',	'',	'',	1,	0,	90),
(93,	10,	'no_border',	'checkbox',	'',	'',	'',	'',	'',	'',	'',	'',	'',	1,	0,	90),
(94,	8,	'video_autoplay',	'checkbox',	'',	'Video automatisch starten',	'',	'',	'',	'',	'',	'',	'0',	0,	0,	24),
(95,	8,	'video_related',	'checkbox',	'',	'Empfehlungen ausblenden',	'',	'',	'',	'',	'',	'',	'0',	0,	0,	25),
(96,	8,	'video_controls',	'checkbox',	'',	'Steuerung ausblenden',	'',	'',	'',	'',	'',	'',	'0',	0,	0,	26),
(97,	8,	'video_start',	'numberfield',	'',	'Starten nach x-Sekunden',	'',	'',	'',	'',	'',	'',	'',	1,	0,	27),
(98,	8,	'video_end',	'numberfield',	'',	'Stoppen nach x-Sekunden',	'',	'',	'',	'',	'',	'',	'',	1,	0,	28),
(99,	8,	'video_info',	'checkbox',	'',	'Info ausblenden',	'',	'',	'',	'',	'',	'',	'0',	0,	0,	29),
(100,	8,	'video_branding',	'checkbox',	'',	'Branding ausblenden',	'',	'',	'',	'',	'',	'',	'0',	0,	0,	30),
(101,	8,	'video_loop',	'checkbox',	'',	'Loop aktivieren',	'',	'',	'Loop ist nicht mit Start- und Endzeiten kompatibel. Video wird wieder von Beginn abgespielt.',	'',	'',	'',	'0',	0,	0,	31),
(102,	11,	'selected_variants',	'hidden',	'',	'',	'',	'',	'',	'',	'',	'',	'',	0,	0,	100),
(103,	4,	'variant',	'emotion-components-fields-variant',	'',	'',	'',	'',	'',	'',	'',	'',	'',	0,	0,	9),
(104,	14,	'content_type',	'shopware-form-field-content-type-selection',	'',	'Content Type Selection',	'',	'',	'',	'',	'name',	'internalName',	'',	0,	0,	NULL),
(105,	14,	'ids',	'hidden',	'',	'',	'',	'',	'',	'',	'',	'',	'',	1,	0,	NULL),
(106,	14,	'mode',	'combobox',	'',	'Modus',	'',	'',	'',	'Shopware.apps.Emotion.store.ContentTypeMode',	'name',	'id',	'',	0,	0,	NULL),
(107,	8,	'load_video_on_confirmation',	'checkbox',	'',	'Video erst nach Bestätigung durch den Kunden laden',	'',	'',	'',	'',	'',	'',	'0',	0,	0,	32),
(108,	8,	'preview_image',	'mediaselectionfield',	'',	'Vorschaubild',	'',	'',	'',	'',	'',	'',	'0',	0,	0,	33);

DROP TABLE IF EXISTS `s_mail_log`;
CREATE TABLE `s_mail_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type_id` int DEFAULT NULL,
  `order_id` int DEFAULT NULL,
  `shop_id` int unsigned DEFAULT NULL,
  `subject` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `sender` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `sent_at` datetime NOT NULL,
  `content_html` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `content_text` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `s_mail_log_idx_type_id` (`type_id`),
  KEY `s_mail_log_idx_order_id` (`order_id`),
  KEY `s_mail_log_idx_shop_id` (`shop_id`),
  CONSTRAINT `s_mail_log_fk_order_id` FOREIGN KEY (`order_id`) REFERENCES `s_order` (`id`) ON DELETE SET NULL,
  CONSTRAINT `s_mail_log_fk_shop_id` FOREIGN KEY (`shop_id`) REFERENCES `s_core_shops` (`id`) ON DELETE SET NULL,
  CONSTRAINT `s_mail_log_fk_type_id` FOREIGN KEY (`type_id`) REFERENCES `s_core_config_mails` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_mail_log_contact`;
CREATE TABLE `s_mail_log_contact` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mail_address` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_mail_log_document`;
CREATE TABLE `s_mail_log_document` (
  `log_id` int NOT NULL,
  `document_id` int NOT NULL,
  PRIMARY KEY (`log_id`,`document_id`),
  KEY `s_mail_log_document_idx_log_id` (`log_id`),
  KEY `s_mail_log_document_idx_document_id` (`document_id`),
  CONSTRAINT `s_mail_log_document_fk_document_id` FOREIGN KEY (`document_id`) REFERENCES `s_order_documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `s_mail_log_document_fk_log_id` FOREIGN KEY (`log_id`) REFERENCES `s_mail_log` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_mail_log_recipient`;
CREATE TABLE `s_mail_log_recipient` (
  `log_id` int NOT NULL,
  `contact_id` int NOT NULL,
  PRIMARY KEY (`log_id`,`contact_id`),
  KEY `s_mail_log_recipient_idx_log_id` (`log_id`),
  KEY `s_mail_log_recipient_idx_contact_id` (`contact_id`),
  CONSTRAINT `s_mail_log_recipient_fk_contact_id` FOREIGN KEY (`contact_id`) REFERENCES `s_mail_log_contact` (`id`) ON DELETE CASCADE,
  CONSTRAINT `s_mail_log_recipient_fk_log_id` FOREIGN KEY (`log_id`) REFERENCES `s_mail_log` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_media`;
CREATE TABLE `s_media` (
  `id` int NOT NULL AUTO_INCREMENT,
  `albumID` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `path` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `type` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `extension` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `file_size` int unsigned NOT NULL,
  `width` int unsigned DEFAULT NULL,
  `height` int unsigned DEFAULT NULL,
  `userID` int NOT NULL,
  `created` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `Album` (`albumID`),
  KEY `path` (`path`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_media` (`id`, `albumID`, `name`, `description`, `path`, `type`, `extension`, `file_size`, `width`, `height`, `userID`, `created`) VALUES
(1,	-1,	'brot',	'',	'media/image/brot.jpg',	'IMAGE',	'jpg',	47974,	1280,	1280,	50,	'2017-10-05'),
(2,	-1,	'download',	'',	'media/image/download.jpg',	'IMAGE',	'jpg',	58453,	1280,	1280,	50,	'2017-10-05'),
(3,	-1,	'fisch',	'',	'media/image/fisch.jpg',	'IMAGE',	'jpg',	59532,	1280,	1280,	50,	'2017-10-05'),
(4,	-1,	'handschuh',	'',	'media/image/handschuh.jpg',	'IMAGE',	'jpg',	57069,	1280,	1280,	50,	'2017-10-05'),
(6,	-1,	'hemd',	'',	'media/image/hemd.jpg',	'IMAGE',	'jpg',	74664,	1280,	1280,	50,	'2017-10-05'),
(8,	-1,	'mobile',	'',	'media/image/mobile.jpg',	'IMAGE',	'jpg',	39833,	1280,	1280,	50,	'2017-10-05'),
(10,	-1,	'schokolade',	'',	'media/image/schokolade.jpg',	'IMAGE',	'jpg',	45970,	1280,	1280,	50,	'2017-10-05'),
(11,	-1,	'socken',	'',	'media/image/socken.jpg',	'IMAGE',	'jpg',	60730,	1280,	1280,	50,	'2017-10-05'),
(12,	-1,	'tube',	'',	'media/image/tube.jpg',	'IMAGE',	'jpg',	41772,	1280,	1280,	50,	'2017-10-05'),
(13,	-1,	'waschmaschine',	'',	'media/image/waschmaschine.jpg',	'IMAGE',	'jpg',	58081,	1280,	1280,	50,	'2017-10-05'),
(14,	-1,	'shirt',	'',	'media/image/shirt.jpg',	'IMAGE',	'jpg',	77860,	1280,	1280,	50,	'2017-10-05'),
(15,	-1,	'shirt_blue',	'',	'media/image/shirt_blue.jpg',	'IMAGE',	'jpg',	131187,	1280,	1280,	50,	'2017-10-06'),
(16,	-1,	'shirt_red',	'',	'media/image/shirt_red.jpg',	'IMAGE',	'jpg',	139879,	1280,	1280,	50,	'2017-10-06'),
(17,	-1,	'hose_blue',	'',	'media/image/hose_blue.jpg',	'IMAGE',	'jpg',	111501,	1280,	1280,	50,	'2017-10-06'),
(18,	-1,	'hose_red',	'',	'media/image/hose_red.jpg',	'IMAGE',	'jpg',	117438,	1280,	1280,	50,	'2017-10-06'),
(19,	-1,	'hose_white',	'',	'media/image/hose_white.jpg',	'IMAGE',	'jpg',	98328,	1280,	1280,	50,	'2017-10-06'),
(20,	-1,	'rucksack_0',	'',	'media/image/rucksack_0.jpg',	'IMAGE',	'jpg',	67514,	1280,	1280,	50,	'2017-10-06'),
(21,	-1,	'rucksack_1',	'',	'media/image/rucksack_1.jpg',	'IMAGE',	'jpg',	78494,	1280,	1280,	50,	'2017-10-06'),
(22,	-1,	'rucksack_2',	'',	'media/image/rucksack_2.jpg',	'IMAGE',	'jpg',	57510,	1280,	1280,	50,	'2017-10-06'),
(23,	-1,	'handtasche_0',	'',	'media/image/handtasche_0.jpg',	'IMAGE',	'jpg',	82493,	1280,	1280,	50,	'2017-10-06'),
(24,	-1,	'handtasche_1',	'',	'media/image/handtasche_1.jpg',	'IMAGE',	'jpg',	97823,	1280,	1280,	50,	'2017-10-06'),
(26,	-10,	'Datei',	'',	'media/unknown/Datei.txt',	'UNKNOWN',	'txt',	24,	NULL,	NULL,	50,	'2017-10-06'),
(27,	-3,	'hq',	'',	'media/image/hq.jpg',	'IMAGE',	'jpg',	1293131,	2500,	1032,	50,	'2017-11-07');

DROP TABLE IF EXISTS `s_media_album`;
CREATE TABLE `s_media_album` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `parentID` int DEFAULT NULL,
  `position` int NOT NULL,
  `garbage_collectable` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_media_album` (`id`, `name`, `parentID`, `position`, `garbage_collectable`) VALUES
(-13,	'Papierkorb',	NULL,	12,	1),
(-12,	'Hersteller',	NULL,	12,	1),
(-11,	'Blog',	NULL,	3,	1),
(-10,	'Unsortiert',	NULL,	7,	1),
(-9,	'Sonstiges',	-6,	3,	1),
(-8,	'Musik',	-6,	2,	1),
(-7,	'Video',	-6,	1,	1),
(-6,	'Dateien',	NULL,	6,	1),
(-5,	'Newsletter',	NULL,	4,	1),
(-4,	'Aktionen',	NULL,	5,	1),
(-3,	'Einkaufswelten',	NULL,	3,	1),
(-2,	'Banner',	NULL,	1,	1),
(-1,	'Artikel',	NULL,	2,	1);

DROP TABLE IF EXISTS `s_media_album_settings`;
CREATE TABLE `s_media_album_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `albumID` int NOT NULL,
  `create_thumbnails` int NOT NULL,
  `thumbnail_size` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `icon` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `thumbnail_high_dpi` int DEFAULT NULL,
  `thumbnail_quality` int DEFAULT NULL,
  `thumbnail_high_dpi_quality` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `albumID` (`albumID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_media_album_settings` (`id`, `albumID`, `create_thumbnails`, `thumbnail_size`, `icon`, `thumbnail_high_dpi`, `thumbnail_quality`, `thumbnail_high_dpi_quality`) VALUES
(1,	-10,	0,	'',	'sprite-blue-folder',	0,	90,	60),
(2,	-9,	0,	'',	'sprite-blue-folder',	0,	90,	60),
(3,	-8,	0,	'',	'sprite-blue-folder',	0,	90,	60),
(4,	-7,	0,	'',	'sprite-blue-folder',	0,	90,	60),
(5,	-6,	0,	'',	'sprite-blue-folder',	0,	90,	60),
(6,	-5,	0,	'',	'sprite-inbox-document-text',	0,	90,	60),
(7,	-4,	0,	'',	'sprite-target',	0,	90,	60),
(8,	-3,	1,	'800x800;1280x1280;1920x1920',	'sprite-target',	1,	90,	60),
(9,	-2,	1,	'800x800;1280x1280;1920x1920',	'sprite-pictures',	1,	90,	60),
(10,	-1,	1,	'200x200;600x600;1280x1280',	'sprite-inbox',	1,	90,	60),
(11,	-11,	1,	'200x200;600x600;1280x1280',	'sprite-leaf',	1,	90,	60),
(12,	-12,	0,	'',	'sprite-hard-hat',	0,	90,	60),
(13,	-13,	0,	'',	'sprite-bin-metal-full',	0,	90,	60);

DROP TABLE IF EXISTS `s_media_association`;
CREATE TABLE `s_media_association` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mediaID` int NOT NULL,
  `targetType` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `targetID` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `Media` (`mediaID`),
  KEY `Target` (`targetID`,`targetType`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_media_attributes`;
CREATE TABLE `s_media_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mediaID` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mediaID` (`mediaID`),
  CONSTRAINT `s_media_attributes_ibfk_1` FOREIGN KEY (`mediaID`) REFERENCES `s_media` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_multi_edit_backup`;
CREATE TABLE `s_multi_edit_backup` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `filter_string` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL COMMENT 'Filter string of the backed up change',
  `operation_string` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL COMMENT 'Operations applied after the backup',
  `items` int unsigned NOT NULL COMMENT 'Number of items affected by the backup',
  `date` datetime DEFAULT '0000-00-00 00:00:00' COMMENT 'Creation date',
  `size` int unsigned NOT NULL COMMENT 'Size of the backup file',
  `path` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL COMMENT 'Path of the backup file',
  `hash` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL COMMENT 'Hash of the backup file',
  PRIMARY KEY (`id`),
  KEY `date` (`date`),
  KEY `size` (`size`),
  KEY `items` (`items`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci COMMENT='Backups known to the system';


DROP TABLE IF EXISTS `s_multi_edit_filter`;
CREATE TABLE `s_multi_edit_filter` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL COMMENT 'Name of the filter',
  `filter_string` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL COMMENT 'The actual filter string',
  `description` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL COMMENT 'User description of the filter',
  `created` datetime DEFAULT '0000-00-00 00:00:00' COMMENT 'Creation date',
  `is_favorite` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Did the user mark this filter as favorite?',
  `is_simple` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Can the filter be loaded and modified with the simple editor?',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci COMMENT='Holds all multi edit filters';

INSERT INTO `s_multi_edit_filter` (`id`, `name`, `filter_string`, `description`, `created`, `is_favorite`, `is_simple`) VALUES
(1,	'<b>Abverkauf-Hauptartikel</b><br><small>nicht auf Lager</small>',	'DETAIL.LASTSTOCK  ISTRUE and DETAIL.INSTOCK <= \"0\" AND ISMAIN',	'Abverkauf-Hauptartikel ohne Lagerbestand',	NULL,	1,	0),
(2,	'Hauptartikel',	'ismain',	'Alle Hauptartikel (einfache Artikel und Standardvarianten)',	NULL,	0,	0),
(3,	'Mit Staffelpreisen',	'HASBLOCKPRICE',	'',	NULL,	0,	0),
(4,	'Highlight',	'ARTICLE.HIGHLIGHT ISTRUE ',	'Zeit alle Highlight-Artikel',	NULL,	0,	0),
(5,	'Konfigurator-Artikel',	'HASCONFIGURATOR  AND ISMAIN ',	'Artikel mit Konfiguratoren',	NULL,	0,	0),
(7,	'Varianten',	'HASCONFIGURATOR ',	'Alle Varianten',	NULL,	0,	0),
(8,	'Ohne Kategorie',	'CATEGORY.ID ISNULL  and ISMAIN ',	'Artikel ohne Kategoriezuordnung',	NULL,	1,	0),
(16,	'Artikel ohne Bilder',	'HASNOIMAGE ',	'Artikel ohne Bilder',	NULL,	1,	0),
(17,	'Komplexer Filter',	'ismain and CATEGORY.ACTIVE ISTRUE and SUPPLIER.NAME IN ( \"Teapavilion\" , \"Feinbrennerei Sasse\" ) ',	'',	NULL,	0,	0),
(18,	'Artikel mit Händlerpreisen',	'PRICE.CUSTOMERGROUPKEY IN (\"B2B\" , \"H\")',	'Alle Artikel, für die Händlerpreise gepflegt werden.',	NULL,	0,	0),
(20,	'Rote Artikel',	'CONFIGURATOROPTION.NAME = \"%Rot%\"  or PROPERTYOPTION.VALUE = \"rot\" ',	'Alle Artikel mit \"rot\" als Konfiguratoroption oder Eigenschaft',	NULL,	0,	0),
(21,	'Regulärer Ausdruck',	'DETAIL.NUMBER !~ \"^sw[0-9]*\" ',	'Findet alle Artikel, die <b>nicht</b> eine Bestellnummer nach dem Schema swZAHL haben.',	NULL,	0,	0),
(22,	'Artikel ohne Bewertung',	'  VOTE.ID ISNULL  and ismain',	'Zeigt alle Artikel ohne Bewertungen und Kommentar',	NULL,	0,	0),
(23,	'Artikel mit nicht-freigeschalteten Bewertungen',	'VOTE.ACTIVE = \"0\"',	'Zeigt alle Artikel, die mindestens eine inaktive Bewertung haben',	NULL,	0,	1),
(24,	'<b>Abverkauf-Variantenartikel</b><br><small>nicht auf Lager</small>',	'   DETAIL.LASTSTOCK  ISTRUE and DETAIL.INSTOCK <= 0',	'Abverkauf-Variantenartikel ohne Lagerbestand',	NULL,	1,	0);

DROP TABLE IF EXISTS `s_multi_edit_queue`;
CREATE TABLE `s_multi_edit_queue` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `resource` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL COMMENT 'Queued resource (e.g. product)',
  `filter_string` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL COMMENT 'The actual filter string',
  `operations` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL COMMENT 'Operations to apply',
  `items` int unsigned NOT NULL COMMENT 'Initial number of objects in the queue',
  `active` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'When active, the queue is allowed to be progressed by cronjob',
  `created` datetime DEFAULT '0000-00-00 00:00:00' COMMENT 'Creation date',
  PRIMARY KEY (`id`),
  KEY `filter_string` (`filter_string`(255)),
  KEY `created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci COMMENT='Holds the batch process queue';


DROP TABLE IF EXISTS `s_multi_edit_queue_articles`;
CREATE TABLE `s_multi_edit_queue_articles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `queue_id` int unsigned NOT NULL COMMENT 'Id of the queue this article belongs to',
  `detail_id` int unsigned NOT NULL COMMENT 'Id of the article detail',
  PRIMARY KEY (`id`),
  UNIQUE KEY `queue_id_2` (`queue_id`,`detail_id`),
  KEY `detail_id` (`detail_id`),
  KEY `queue_id` (`queue_id`),
  CONSTRAINT `s_multi_edit_queue_articles_ibfk_1` FOREIGN KEY (`detail_id`) REFERENCES `s_articles_details` (`id`) ON DELETE CASCADE,
  CONSTRAINT `s_multi_edit_queue_articles_ibfk_2` FOREIGN KEY (`queue_id`) REFERENCES `s_multi_edit_queue` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci COMMENT='Products belonging to a certain queue';


DROP TABLE IF EXISTS `s_order`;
CREATE TABLE `s_order` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ordernumber` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `userID` int DEFAULT NULL,
  `invoice_amount` double NOT NULL DEFAULT '0',
  `invoice_amount_net` double NOT NULL,
  `invoice_shipping` double NOT NULL DEFAULT '0',
  `invoice_shipping_net` double NOT NULL,
  `invoice_shipping_tax_rate` double DEFAULT NULL,
  `ordertime` datetime DEFAULT NULL,
  `status` int NOT NULL DEFAULT '0',
  `cleared` int NOT NULL DEFAULT '0',
  `paymentID` int NOT NULL DEFAULT '0',
  `transactionID` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `comment` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `customercomment` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `internalcomment` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `net` int NOT NULL,
  `taxfree` int NOT NULL,
  `partnerID` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `temporaryID` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `referer` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `cleareddate` datetime DEFAULT NULL,
  `trackingcode` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `language` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `dispatchID` int NOT NULL,
  `currency` varchar(5) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `currencyFactor` double NOT NULL,
  `subshopID` int NOT NULL,
  `remote_addr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `deviceType` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `is_proportional_calculation` tinyint NOT NULL DEFAULT '0',
  `changed` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `partnerID` (`partnerID`),
  KEY `userID` (`userID`),
  KEY `ordertime` (`ordertime`),
  KEY `cleared` (`cleared`),
  KEY `status` (`status`),
  KEY `paymentID` (`paymentID`),
  KEY `temporaryID` (`temporaryID`),
  KEY `ordernumber` (`ordernumber`),
  KEY `transactionID` (`transactionID`),
  KEY `ordernumber_2` (`ordernumber`,`status`),
  KEY `invoice_amount` (`invoice_amount`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_order_attributes`;
CREATE TABLE `s_order_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `orderID` int DEFAULT NULL,
  `attribute1` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attribute2` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attribute3` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attribute4` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attribute5` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attribute6` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orderID` (`orderID`),
  CONSTRAINT `s_order_attributes_ibfk_1` FOREIGN KEY (`orderID`) REFERENCES `s_order` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_order_basket`;
CREATE TABLE `s_order_basket` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sessionID` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `userID` int NOT NULL DEFAULT '0',
  `articlename` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `articleID` int NOT NULL DEFAULT '0',
  `ordernumber` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `shippingfree` int NOT NULL DEFAULT '0',
  `quantity` int NOT NULL DEFAULT '0',
  `price` double NOT NULL DEFAULT '0',
  `netprice` double NOT NULL DEFAULT '0',
  `tax_rate` double NOT NULL,
  `datum` datetime DEFAULT NULL,
  `modus` int NOT NULL DEFAULT '0',
  `esdarticle` int NOT NULL,
  `partnerID` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `lastviewport` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `useragent` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `config` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `currencyFactor` double NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessionID` (`sessionID`),
  KEY `articleID` (`articleID`),
  KEY `datum` (`datum`),
  KEY `get_basket` (`sessionID`,`id`,`datum`),
  KEY `ordernumber` (`ordernumber`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_order_basket_attributes`;
CREATE TABLE `s_order_basket_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `basketID` int DEFAULT NULL,
  `attribute1` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attribute2` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attribute3` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attribute4` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attribute5` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attribute6` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `basketID` (`basketID`),
  CONSTRAINT `s_order_basket_attributes_ibfk_2` FOREIGN KEY (`basketID`) REFERENCES `s_order_basket` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_order_basket_signatures`;
CREATE TABLE `s_order_basket_signatures` (
  `signature` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `basket` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `created_at` date NOT NULL,
  PRIMARY KEY (`signature`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_order_billingaddress`;
CREATE TABLE `s_order_billingaddress` (
  `id` int NOT NULL AUTO_INCREMENT,
  `userID` int DEFAULT NULL,
  `orderID` int NOT NULL,
  `company` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `department` varchar(35) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `salutation` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `customernumber` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `firstname` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `lastname` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `street` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `zipcode` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `city` varchar(70) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `phone` varchar(40) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `countryID` int NOT NULL DEFAULT '0',
  `stateID` int DEFAULT NULL,
  `ustid` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `additional_address_line1` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `additional_address_line2` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `title` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orderID` (`orderID`),
  KEY `userid` (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_order_billingaddress_attributes`;
CREATE TABLE `s_order_billingaddress_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `billingID` int DEFAULT NULL,
  `text1` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `text2` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `text3` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `text4` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `text5` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `text6` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `billingID` (`billingID`),
  CONSTRAINT `s_order_billingaddress_attributes_ibfk_2` FOREIGN KEY (`billingID`) REFERENCES `s_order_billingaddress` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_order_comparisons`;
CREATE TABLE `s_order_comparisons` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sessionID` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `userID` int NOT NULL DEFAULT '0',
  `articlename` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `articleID` int NOT NULL DEFAULT '0',
  `datum` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `articleID` (`articleID`),
  KEY `sessionID` (`sessionID`),
  KEY `datum` (`datum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_order_details`;
CREATE TABLE `s_order_details` (
  `id` int NOT NULL AUTO_INCREMENT,
  `orderID` int NOT NULL DEFAULT '0',
  `ordernumber` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT '',
  `articleID` int NOT NULL DEFAULT '0',
  `articleordernumber` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `price` double NOT NULL DEFAULT '0',
  `quantity` int NOT NULL DEFAULT '0',
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `status` int NOT NULL DEFAULT '0',
  `shipped` int NOT NULL DEFAULT '0',
  `shippedgroup` int NOT NULL DEFAULT '0',
  `releasedate` date DEFAULT NULL,
  `modus` int NOT NULL,
  `esdarticle` int NOT NULL,
  `taxID` int DEFAULT NULL,
  `tax_rate` double NOT NULL,
  `config` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `ean` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `unit` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `pack_unit` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `articleDetailID` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `orderID` (`orderID`),
  KEY `articleID` (`articleID`),
  KEY `ordernumber` (`ordernumber`),
  KEY `articleordernumber` (`articleordernumber`),
  CONSTRAINT `s_order_details_ibfk_1` FOREIGN KEY (`orderID`) REFERENCES `s_order` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_order_details_attributes`;
CREATE TABLE `s_order_details_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `detailID` int DEFAULT NULL,
  `attribute1` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attribute2` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attribute3` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attribute4` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attribute5` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `attribute6` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `detailID` (`detailID`),
  CONSTRAINT `s_order_details_attributes_ibfk_1` FOREIGN KEY (`detailID`) REFERENCES `s_order_details` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_order_documents`;
CREATE TABLE `s_order_documents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `type` int NOT NULL,
  `userID` int NOT NULL,
  `orderID` int unsigned NOT NULL,
  `amount` double NOT NULL,
  `docID` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `hash` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `orderID` (`orderID`),
  KEY `userID` (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_order_documents_attributes`;
CREATE TABLE `s_order_documents_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `documentID` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `documentID` (`documentID`),
  CONSTRAINT `s_order_documents_attributes_ibfk_1` FOREIGN KEY (`documentID`) REFERENCES `s_order_documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_order_esd`;
CREATE TABLE `s_order_esd` (
  `id` int NOT NULL AUTO_INCREMENT,
  `serialID` int NOT NULL DEFAULT '0',
  `esdID` int NOT NULL DEFAULT '0',
  `userID` int NOT NULL DEFAULT '0',
  `orderID` int NOT NULL DEFAULT '0',
  `orderdetailsID` int NOT NULL DEFAULT '0',
  `datum` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_order_history`;
CREATE TABLE `s_order_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `orderID` int NOT NULL,
  `userID` int DEFAULT NULL,
  `previous_order_status_id` int DEFAULT NULL,
  `order_status_id` int DEFAULT NULL,
  `previous_payment_status_id` int DEFAULT NULL,
  `payment_status_id` int DEFAULT NULL,
  `comment` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `change_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user` (`userID`),
  KEY `order` (`orderID`),
  KEY `current_payment_status` (`payment_status_id`),
  KEY `current_order_status` (`order_status_id`),
  KEY `previous_payment_status` (`previous_payment_status_id`),
  KEY `previous_order_status` (`previous_order_status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_order_notes`;
CREATE TABLE `s_order_notes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sUniqueID` varchar(70) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `userID` int NOT NULL DEFAULT '0',
  `articlename` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `articleID` int NOT NULL DEFAULT '0',
  `ordernumber` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `datum` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `basket_count_notes` (`sUniqueID`,`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_order_number`;
CREATE TABLE `s_order_number` (
  `id` int NOT NULL AUTO_INCREMENT,
  `number` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `desc` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_order_number` (`id`, `number`, `name`, `desc`) VALUES
(1,	20003,	'user',	'Kunden'),
(920,	20000,	'invoice',	'Bestellungen'),
(921,	20000,	'doc_1',	'Lieferscheine'),
(922,	20000,	'doc_2',	'Gutschriften'),
(924,	20000,	'doc_0',	'Rechnungen'),
(925,	10000,	'articleordernumber',	'Artikelbestellnummer  '),
(926,	10000,	'sSERVICE1',	'Service - 1'),
(927,	10000,	'sSERVICE2',	'Service - 2'),
(928,	110,	'blogordernumber',	'Blog - ID');

DROP TABLE IF EXISTS `s_order_shippingaddress`;
CREATE TABLE `s_order_shippingaddress` (
  `id` int NOT NULL AUTO_INCREMENT,
  `userID` int DEFAULT NULL,
  `orderID` int NOT NULL,
  `company` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `department` varchar(35) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `salutation` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `firstname` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `lastname` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `street` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `zipcode` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `city` varchar(70) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `phone` varchar(40) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `countryID` int NOT NULL,
  `stateID` int DEFAULT NULL,
  `additional_address_line1` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `additional_address_line2` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `title` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orderID` (`orderID`),
  KEY `userID` (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_order_shippingaddress_attributes`;
CREATE TABLE `s_order_shippingaddress_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `shippingID` int DEFAULT NULL,
  `text1` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `text2` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `text3` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `text4` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `text5` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `text6` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shippingID` (`shippingID`),
  CONSTRAINT `s_order_shippingaddress_attributes_ibfk_1` FOREIGN KEY (`shippingID`) REFERENCES `s_order_shippingaddress` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_plugin_recommendations`;
CREATE TABLE `s_plugin_recommendations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `categoryID` int NOT NULL,
  `banner_active` int NOT NULL,
  `new_active` int NOT NULL,
  `bought_active` int NOT NULL,
  `supplier_active` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categoryID_2` (`categoryID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_plugin_schema_version`;
CREATE TABLE `s_plugin_schema_version` (
  `plugin_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `version` int NOT NULL,
  `start_date` datetime NOT NULL,
  `complete_date` datetime DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `error_msg` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`plugin_name`,`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_plugin_widgets_notes`;
CREATE TABLE `s_plugin_widgets_notes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `userID` int NOT NULL,
  `notes` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_premium_dispatch`;
CREATE TABLE `s_premium_dispatch` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `type` int unsigned NOT NULL,
  `description` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `comment` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `active` int unsigned NOT NULL,
  `position` int NOT NULL,
  `calculation` int unsigned NOT NULL,
  `surcharge_calculation` int unsigned NOT NULL,
  `tax_calculation` int unsigned NOT NULL,
  `shippingfree` decimal(10,2) unsigned DEFAULT NULL,
  `multishopID` int unsigned DEFAULT NULL,
  `customergroupID` int unsigned DEFAULT NULL,
  `bind_shippingfree` int unsigned NOT NULL,
  `bind_time_from` int unsigned DEFAULT NULL,
  `bind_time_to` int unsigned DEFAULT NULL,
  `bind_instock` int unsigned DEFAULT NULL,
  `bind_laststock` int unsigned NOT NULL,
  `bind_weekday_from` int unsigned DEFAULT NULL,
  `bind_weekday_to` int unsigned DEFAULT NULL,
  `bind_weight_from` decimal(10,3) DEFAULT NULL,
  `bind_weight_to` decimal(10,3) DEFAULT NULL,
  `bind_price_from` decimal(10,2) DEFAULT NULL,
  `bind_price_to` decimal(10,2) DEFAULT NULL,
  `bind_sql` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `status_link` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `calculation_sql` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_premium_dispatch` (`id`, `name`, `type`, `description`, `comment`, `active`, `position`, `calculation`, `surcharge_calculation`, `tax_calculation`, `shippingfree`, `multishopID`, `customergroupID`, `bind_shippingfree`, `bind_time_from`, `bind_time_to`, `bind_instock`, `bind_laststock`, `bind_weekday_from`, `bind_weekday_to`, `bind_weight_from`, `bind_weight_to`, `bind_price_from`, `bind_price_to`, `bind_sql`, `status_link`, `calculation_sql`) VALUES
(9,	'Standard Versand',	0,	'',	'',	1,	0,	0,	3,	0,	NULL,	NULL,	NULL,	0,	NULL,	NULL,	NULL,	0,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	'',	NULL);

DROP TABLE IF EXISTS `s_premium_dispatch_attributes`;
CREATE TABLE `s_premium_dispatch_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `dispatchID` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dispatchID` (`dispatchID`),
  CONSTRAINT `s_premium_dispatch_attributes_ibfk_1` FOREIGN KEY (`dispatchID`) REFERENCES `s_premium_dispatch` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_premium_dispatch_categories`;
CREATE TABLE `s_premium_dispatch_categories` (
  `dispatchID` int unsigned NOT NULL,
  `categoryID` int unsigned NOT NULL,
  PRIMARY KEY (`dispatchID`,`categoryID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_premium_dispatch_countries`;
CREATE TABLE `s_premium_dispatch_countries` (
  `dispatchID` int NOT NULL,
  `countryID` int NOT NULL,
  PRIMARY KEY (`dispatchID`,`countryID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_premium_dispatch_countries` (`dispatchID`, `countryID`) VALUES
(9,	2),
(9,	3),
(9,	4),
(9,	5),
(9,	7),
(9,	8),
(9,	9),
(9,	10),
(9,	11),
(9,	12),
(9,	13),
(9,	14),
(9,	15),
(9,	16),
(9,	18),
(9,	20),
(9,	21),
(9,	22),
(9,	23),
(9,	24),
(9,	25),
(9,	26),
(9,	27),
(9,	28),
(9,	29),
(9,	30),
(9,	31),
(9,	32),
(9,	33),
(9,	34),
(9,	35),
(9,	36),
(9,	37);

DROP TABLE IF EXISTS `s_premium_dispatch_holidays`;
CREATE TABLE `s_premium_dispatch_holidays` (
  `dispatchID` int unsigned NOT NULL,
  `holidayID` int unsigned NOT NULL,
  PRIMARY KEY (`dispatchID`,`holidayID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_premium_dispatch_paymentmeans`;
CREATE TABLE `s_premium_dispatch_paymentmeans` (
  `dispatchID` int NOT NULL,
  `paymentID` int NOT NULL,
  PRIMARY KEY (`dispatchID`,`paymentID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_premium_dispatch_paymentmeans` (`dispatchID`, `paymentID`) VALUES
(9,	2),
(9,	3),
(9,	4),
(9,	5);

DROP TABLE IF EXISTS `s_premium_holidays`;
CREATE TABLE `s_premium_holidays` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `calculation` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `date` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_premium_holidays` (`id`, `name`, `calculation`, `date`) VALUES
(1,	'Neujahr',	'DATE(\'01-01\')',	'2011-01-01'),
(3,	'Heilige drei Könige',	'DATE(\'01-06\')',	'2011-01-06'),
(4,	'Rosenmontag',	'DATE_SUB(EASTERDATE(), INTERVAL 48 DAY)',	'2011-03-07'),
(5,	'Josefstag',	'DATE(\'03/19\')',	'2011-03-19'),
(6,	'Karfreitag',	'DATE_SUB(EASTERDATE(), INTERVAL 2 DAY)',	'2011-04-22'),
(7,	'Ostermontag',	'DATE_ADD(EASTERDATE(), INTERVAL 1 DAY)',	'2011-04-25'),
(8,	'Tag der Arbeit',	'DATE(\'05/01\')',	'2011-05-01'),
(9,	'Christi Himmelfahrt',	'DATE_ADD(EASTERDATE(), INTERVAL 39 DAY)',	'2011-06-02'),
(10,	'Pfingstmontag',	'DATE_ADD(EASTERDATE(), INTERVAL 50 DAY)',	'2011-06-13'),
(11,	'Fronleichnam',	'DATE_ADD(EASTERDATE(), INTERVAL 60 DAY)',	'2011-06-23'),
(13,	'Mariä Himmelfahrt',	'DATE(\'08/15\')',	'2011-08-15'),
(14,	'Tag der Deutschen Einheit',	'DATE(\'10/03\')',	'2011-10-03'),
(15,	'Nationalfeiertag (Österreich)',	'DATE(\'10/26\')',	'2010-10-26'),
(16,	'Reformationstag',	'DATE(\'10/31\')',	'2010-10-31'),
(17,	'Allerheiligen',	'DATE(\'11/01\')',	'2010-11-01'),
(18,	'Buß- und Bettag',	'SUBDATE(DATE(\'11-23\'), DAYOFWEEK(DATE(\'11-23\'))+IF(DAYOFWEEK(DATE(\'11-23\'))>4,-4,3))',	'2010-11-17'),
(19,	'Mariä Empfängnis',	'DATE(\'12/8\')',	'2010-12-08'),
(20,	'Heiligabend',	'DATE(\'12/24\')',	'2010-12-24'),
(21,	'1. Weihnachtstag',	'DATE(\'12/25\')',	'2010-12-25'),
(22,	'2. Weihnachtstag (Stephanstag)',	'DATE(\'12/26\')',	'2010-12-26'),
(23,	'Silvester',	'DATE(\'12/31\')',	'2010-12-31');

DROP TABLE IF EXISTS `s_premium_shippingcosts`;
CREATE TABLE `s_premium_shippingcosts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `from` decimal(10,3) unsigned NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `factor` decimal(10,2) NOT NULL,
  `dispatchID` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `from` (`from`,`dispatchID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_premium_shippingcosts` (`id`, `from`, `value`, `factor`, `dispatchID`) VALUES
(235,	0.000,	3.90,	0.00,	9);

DROP TABLE IF EXISTS `s_product_streams`;
CREATE TABLE `s_product_streams` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `conditions` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `type` int DEFAULT NULL,
  `sorting` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `description` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `sorting_id` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_product_streams_articles`;
CREATE TABLE `s_product_streams_articles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `stream_id` int unsigned NOT NULL,
  `article_id` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stream_id` (`stream_id`,`article_id`),
  KEY `s_product_streams_articles_fk_article_id` (`article_id`),
  CONSTRAINT `s_product_streams_articles_fk_article_id` FOREIGN KEY (`article_id`) REFERENCES `s_articles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `s_product_streams_articles_fk_stream_id` FOREIGN KEY (`stream_id`) REFERENCES `s_product_streams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_product_streams_attributes`;
CREATE TABLE `s_product_streams_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `streamID` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `streamID` (`streamID`),
  CONSTRAINT `s_product_streams_attributes_ibfk_1` FOREIGN KEY (`streamID`) REFERENCES `s_product_streams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_product_streams_selection`;
CREATE TABLE `s_product_streams_selection` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `stream_id` int unsigned NOT NULL,
  `article_id` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stream_id` (`stream_id`,`article_id`),
  KEY `s_product_streams_selection_fk_article_id` (`article_id`),
  CONSTRAINT `s_product_streams_selection_fk_article_id` FOREIGN KEY (`article_id`) REFERENCES `s_articles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `s_product_streams_selection_fk_stream_id` FOREIGN KEY (`stream_id`) REFERENCES `s_product_streams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_schema_version`;
CREATE TABLE `s_schema_version` (
  `version` int NOT NULL,
  `start_date` datetime NOT NULL,
  `complete_date` datetime DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `error_msg` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

DROP TABLE IF EXISTS `s_search_custom_facet`;
CREATE TABLE `s_search_custom_facet` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `active` int unsigned NOT NULL,
  `unique_key` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `display_in_categories` int unsigned NOT NULL,
  `deletable` int unsigned NOT NULL,
  `position` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `facet` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_identifier` (`unique_key`),
  KEY `sorting` (`display_in_categories`,`position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_search_custom_facet` (`id`, `active`, `unique_key`, `display_in_categories`, `deletable`, `position`, `name`, `facet`) VALUES
(1,	1,	'CategoryFacet',	0,	0,	1,	'Kategorien',	'{\"Shopware\\\\Bundle\\\\SearchBundle\\\\Facet\\\\CategoryFacet\":{\"label\":\"Kategorien\", \"depth\": \"2\"}}'),
(2,	1,	'ImmediateDeliveryFacet',	1,	0,	2,	'Sofort lieferbar',	'{\"Shopware\\\\Bundle\\\\SearchBundle\\\\Facet\\\\ImmediateDeliveryFacet\":{\"label\":\"Sofort lieferbar\"}}'),
(3,	1,	'ManufacturerFacet',	1,	0,	3,	'Hersteller',	'{\"Shopware\\\\Bundle\\\\SearchBundle\\\\Facet\\\\ManufacturerFacet\":{\"label\":\"Hersteller\"}}'),
(4,	1,	'PriceFacet',	1,	0,	4,	'Preis',	'{\"Shopware\\\\Bundle\\\\SearchBundle\\\\Facet\\\\PriceFacet\":{\"label\":\"Preis\"}}'),
(5,	1,	'PropertyFacet',	1,	0,	5,	'Eigenschaften',	'{\"Shopware\\\\Bundle\\\\SearchBundle\\\\Facet\\\\PropertyFacet\":[]}'),
(6,	1,	'ShippingFreeFacet',	1,	0,	6,	'Versandkostenfrei',	'{\"Shopware\\\\Bundle\\\\SearchBundle\\\\Facet\\\\ShippingFreeFacet\":{\"label\":\"Versandkostenfrei\"}}'),
(7,	1,	'VoteAverageFacet',	1,	0,	7,	'Bewertungen',	'{\"Shopware\\\\Bundle\\\\SearchBundle\\\\Facet\\\\VoteAverageFacet\":{\"label\":\"Bewertung\"}}'),
(8,	0,	'WeightFacet',	1,	0,	8,	'Gewicht',	'{\"Shopware\\\\Bundle\\\\SearchBundle\\\\Facet\\\\WeightFacet\":{\"label\":\"Gewicht\",\"suffix\":\"kg\",\"digits\":2}}'),
(9,	0,	'WidthFacet',	1,	0,	9,	'Breite',	'{\"Shopware\\\\Bundle\\\\SearchBundle\\\\Facet\\\\WidthFacet\":{\"label\":\"Breite\",\"suffix\":\"cm\",\"digits\":2}}'),
(10,	0,	'HeightFacet',	1,	0,	10,	'Höhe',	'{\"Shopware\\\\Bundle\\\\SearchBundle\\\\Facet\\\\HeightFacet\":{\"label\":\"Höhe\",\"suffix\":\"cm\",\"digits\":2}}'),
(11,	0,	'LengthFacet',	1,	0,	11,	'Länge',	'{\"Shopware\\\\Bundle\\\\SearchBundle\\\\Facet\\\\LengthFacet\":{\"label\":\"Länge\",\"suffix\":\"cm\",\"digits\":2}}'),
(12,	0,	'VariantFacet',	1,	0,	11,	'Varianten',	'{\"Shopware\\\\Bundle\\\\SearchBundle\\\\Facet\\\\VariantFacet\":{\"groupIds\":\"\", \"expandGroupIds\":\"\"}}');

DROP TABLE IF EXISTS `s_search_custom_sorting`;
CREATE TABLE `s_search_custom_sorting` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `active` int unsigned NOT NULL,
  `display_in_categories` int unsigned NOT NULL,
  `position` int NOT NULL,
  `sortings` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sorting` (`display_in_categories`,`position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_search_custom_sorting` (`id`, `label`, `active`, `display_in_categories`, `position`, `sortings`) VALUES
(1,	'Erscheinungsdatum',	1,	1,	-10,	'{\"Shopware\\\\Bundle\\\\SearchBundle\\\\Sorting\\\\ReleaseDateSorting\":{\"direction\":\"DESC\"}}'),
(2,	'Beliebtheit',	1,	1,	1,	'{\"Shopware\\\\Bundle\\\\SearchBundle\\\\Sorting\\\\PopularitySorting\":{\"direction\":\"DESC\"}}'),
(3,	'Niedrigster Preis',	1,	1,	2,	'{\"Shopware\\\\Bundle\\\\SearchBundle\\\\Sorting\\\\PriceSorting\":{\"direction\":\"ASC\"}}'),
(4,	'Höchster Preis',	1,	1,	3,	'{\"Shopware\\\\Bundle\\\\SearchBundle\\\\Sorting\\\\PriceSorting\":{\"direction\":\"DESC\"}}'),
(5,	'Artikelbezeichnung',	1,	1,	4,	'{\"Shopware\\\\Bundle\\\\SearchBundle\\\\Sorting\\\\ProductNameSorting\":{\"direction\":\"ASC\"}}'),
(7,	'Beste Ergebnisse',	1,	0,	6,	'{\"Shopware\\\\Bundle\\\\SearchBundle\\\\Sorting\\\\SearchRankingSorting\":{\"direction\":\"DESC\"}}'),
(8,	'Position',	1,	1,	5,	'{\"Shopware\\\\Bundle\\\\SearchBundle\\\\Sorting\\\\ManualSorting\":{\"direction\":\"ASC\"}}'),
(9,	'Artikelnummer',	0,	1,	5,	'{\"Shopware\\\\Bundle\\\\SearchBundle\\\\Sorting\\\\ProductNumberSorting\":{\"direction\":\"ASC\"}}');

DROP TABLE IF EXISTS `s_search_fields`;
CREATE TABLE `s_search_fields` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `relevance` int NOT NULL,
  `field` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `tableID` int NOT NULL,
  `do_not_split` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `field` (`field`,`tableID`),
  KEY `tableID` (`tableID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_search_fields` (`id`, `name`, `relevance`, `field`, `tableID`, `do_not_split`) VALUES
(1,	'Kategorie-Keywords',	10,	'metakeywords',	2,	0),
(2,	'Kategorie-Überschrift',	70,	'description',	2,	0),
(3,	'Artikel-Name',	400,	'name',	1,	0),
(4,	'Artikel-Keywords',	10,	'keywords',	1,	0),
(5,	'Artikel-Bestellnummer',	50,	'ordernumber',	4,	0),
(6,	'Hersteller-Name',	45,	'name',	3,	0),
(7,	'Artikel-Name Übersetzung',	50,	'name',	5,	0),
(8,	'Artikel-Keywords Übersetzung',	10,	'keywords',	5,	0);

DROP TABLE IF EXISTS `s_search_index`;
CREATE TABLE `s_search_index` (
  `keywordID` int NOT NULL,
  `fieldID` int NOT NULL,
  `elementID` int NOT NULL,
  PRIMARY KEY (`keywordID`,`fieldID`,`elementID`),
  KEY `clean_up_index` (`keywordID`,`fieldID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_search_index` (`keywordID`, `fieldID`, `elementID`) VALUES
(1,	3,	1),
(1,	3,	2),
(1,	3,	3),
(1,	3,	6),
(1,	3,	7),
(1,	3,	8),
(1,	3,	10),
(1,	3,	11),
(1,	3,	12),
(1,	3,	13),
(2,	3,	2),
(3,	3,	2),
(4,	3,	3),
(5,	3,	3),
(6,	3,	4),
(6,	3,	9),
(6,	3,	14),
(7,	3,	4),
(8,	3,	4),
(8,	3,	9),
(8,	3,	14),
(9,	3,	5),
(10,	3,	6),
(11,	3,	6),
(12,	3,	6),
(13,	3,	7),
(14,	3,	8),
(15,	3,	9),
(16,	3,	10),
(17,	3,	11),
(18,	3,	12),
(19,	3,	12),
(20,	3,	13),
(21,	3,	14),
(22,	2,	1),
(23,	2,	3),
(24,	2,	5),
(25,	2,	6),
(26,	2,	7),
(27,	2,	7),
(28,	2,	8),
(29,	2,	9),
(30,	2,	10),
(31,	2,	11),
(32,	2,	12),
(37,	5,	1),
(38,	5,	2),
(39,	5,	3),
(40,	5,	4),
(40,	5,	38),
(40,	5,	39),
(41,	5,	16),
(41,	5,	20),
(41,	5,	21),
(41,	5,	22),
(41,	5,	23),
(41,	5,	24),
(42,	5,	6),
(43,	5,	7),
(43,	5,	17),
(43,	5,	18),
(43,	5,	19),
(44,	5,	8),
(45,	5,	26),
(45,	5,	27),
(45,	5,	28),
(45,	5,	29),
(45,	5,	30),
(45,	5,	31),
(45,	5,	32),
(45,	5,	33),
(45,	5,	34),
(45,	5,	35),
(45,	5,	36),
(45,	5,	37),
(46,	5,	10),
(47,	5,	11),
(48,	5,	12),
(49,	5,	13),
(50,	5,	14),
(50,	5,	40),
(50,	5,	41);

DROP TABLE IF EXISTS `s_search_keywords`;
CREATE TABLE `s_search_keywords` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `keyword` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `soundex` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `keyword` (`keyword`),
  KEY `soundex` (`soundex`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_search_keywords` (`id`, `keyword`, `soundex`) VALUES
(1,	'hauptartikel',	NULL),
(2,	'mail',	NULL),
(3,	'benachrichtigung',	NULL),
(4,	'esd',	NULL),
(5,	'download',	NULL),
(6,	'artikel',	NULL),
(7,	'standard',	NULL),
(8,	'konfigurator',	NULL),
(9,	'variantenartikel',	NULL),
(10,	'kennzeichnung',	NULL),
(11,	'versandkostenfrei',	NULL),
(12,	'hervorhebung',	NULL),
(13,	'eigenschaften',	NULL),
(14,	'ressourcen',	NULL),
(15,	'auswahl',	NULL),
(16,	'grundpreisberechnung',	NULL),
(17,	'abverkauf',	NULL),
(18,	'cross',	NULL),
(19,	'selling',	NULL),
(20,	'bewertungen',	NULL),
(21,	'bild',	NULL),
(22,	'root',	NULL),
(23,	'deutsch',	NULL),
(24,	'lebensmittel',	NULL),
(25,	'bekleidung',	NULL),
(26,	'freizeit',	NULL),
(27,	'elektro',	NULL),
(28,	'backwaren',	NULL),
(29,	'fisch',	NULL),
(30,	'suesses',	NULL),
(31,	'damen',	NULL),
(32,	'herren',	NULL),
(37,	'sw10001',	NULL),
(38,	'sw10002',	NULL),
(39,	'sw10003',	NULL),
(40,	'sw10004',	NULL),
(41,	'sw10005',	NULL),
(42,	'sw10006',	NULL),
(43,	'sw10007',	NULL),
(44,	'sw10008',	NULL),
(45,	'sw10009',	NULL),
(46,	'sw10010',	NULL),
(47,	'sw10011',	NULL),
(48,	'sw10012',	NULL),
(49,	'sw10013',	NULL),
(50,	'sw10014',	NULL);

DROP TABLE IF EXISTS `s_search_tables`;
CREATE TABLE `s_search_tables` (
  `id` int NOT NULL AUTO_INCREMENT,
  `table` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `referenz_table` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `foreign_key` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `where` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_search_tables` (`id`, `table`, `referenz_table`, `foreign_key`, `where`) VALUES
(1,	's_articles',	NULL,	NULL,	NULL),
(2,	's_categories',	's_articles_categories',	'categoryID',	NULL),
(3,	's_articles_supplier',	NULL,	'supplierID',	NULL),
(4,	's_articles_details',	's_articles_details',	'id',	NULL),
(5,	's_articles_translations',	NULL,	NULL,	NULL),
(6,	's_articles_attributes',	NULL,	NULL,	NULL);

DROP TABLE IF EXISTS `s_sitemap_custom`;
CREATE TABLE `s_sitemap_custom` (
  `id` int NOT NULL AUTO_INCREMENT,
  `url` varchar(512) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `priority` int NOT NULL,
  `change_freq` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `last_mod` datetime NOT NULL,
  `shop_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shop_id` (`shop_id`),
  CONSTRAINT `s_sitemap_custom_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `s_core_shops` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_sitemap_exclude`;
CREATE TABLE `s_sitemap_exclude` (
  `id` int NOT NULL AUTO_INCREMENT,
  `resource` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `identifier` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `shop_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shop_id` (`shop_id`),
  CONSTRAINT `s_sitemap_exclude_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `s_core_shops` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_statistics_article_impression`;
CREATE TABLE `s_statistics_article_impression` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `articleId` int unsigned NOT NULL,
  `shopId` int unsigned NOT NULL,
  `date` date NOT NULL DEFAULT '0000-00-00',
  `impressions` int NOT NULL,
  `deviceType` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'desktop',
  PRIMARY KEY (`id`),
  UNIQUE KEY `articleId_2` (`articleId`,`shopId`,`date`,`deviceType`),
  KEY `articleId` (`articleId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_statistics_currentusers`;
CREATE TABLE `s_statistics_currentusers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `remoteaddr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `page` varchar(70) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `time` datetime DEFAULT NULL,
  `userID` int NOT NULL DEFAULT '0',
  `deviceType` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'desktop',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_statistics_currentusers` (`id`, `remoteaddr`, `page`, `time`, `userID`, `deviceType`) VALUES
(1,	'212.185.0.0',	'/',	'2026-02-05 14:24:38',	0,	'desktop'),
(2,	'212.185.0.0',	'/account',	'2026-02-05 14:24:41',	0,	'desktop'),
(3,	'212.185.0.0',	'/register/saveRegister/sTarget/account/sTargetAction/index',	'2026-02-05 14:25:05',	0,	'desktop'),
(4,	'212.185.0.0',	'/account',	'2026-02-05 14:25:14',	1,	'desktop');

DROP TABLE IF EXISTS `s_statistics_pool`;
CREATE TABLE `s_statistics_pool` (
  `id` int NOT NULL AUTO_INCREMENT,
  `remoteaddr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `datum` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_statistics_pool` (`id`, `remoteaddr`, `datum`) VALUES
(1,	'dc02f2fa4aaa1115e32df95cf557315f',	'2026-02-05');

DROP TABLE IF EXISTS `s_statistics_referer`;
CREATE TABLE `s_statistics_referer` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` date DEFAULT NULL,
  `referer` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_statistics_referer` (`id`, `datum`, `referer`) VALUES
(1,	'2026-02-05',	'https://shopware.build/');

DROP TABLE IF EXISTS `s_statistics_search`;
CREATE TABLE `s_statistics_search` (
  `id` int NOT NULL AUTO_INCREMENT,
  `datum` datetime NOT NULL,
  `searchterm` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `results` int NOT NULL,
  `shop_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `searchterm` (`searchterm`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `s_statistics_visitors`;
CREATE TABLE `s_statistics_visitors` (
  `id` int NOT NULL AUTO_INCREMENT,
  `shopID` int NOT NULL,
  `datum` date DEFAULT NULL,
  `pageimpressions` int NOT NULL DEFAULT '0',
  `uniquevisits` int NOT NULL DEFAULT '0',
  `deviceType` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'desktop',
  PRIMARY KEY (`id`),
  KEY `datum` (`datum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_statistics_visitors` (`id`, `shopID`, `datum`, `pageimpressions`, `uniquevisits`, `deviceType`) VALUES
(1,	1,	'2026-02-05',	4,	1,	'desktop');

DROP TABLE IF EXISTS `s_user`;
CREATE TABLE `s_user` (
  `id` int NOT NULL AUTO_INCREMENT,
  `password` varchar(1024) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `encoder` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'md5',
  `email` varchar(70) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `active` int NOT NULL DEFAULT '0',
  `accountmode` int NOT NULL,
  `confirmationkey` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `paymentID` int NOT NULL DEFAULT '0',
  `doubleOptinRegister` tinyint(1) DEFAULT '0',
  `doubleOptinEmailSentDate` datetime DEFAULT NULL,
  `doubleOptinConfirmDate` datetime DEFAULT NULL,
  `firstlogin` date DEFAULT NULL,
  `lastlogin` datetime DEFAULT NULL,
  `sessionID` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `newsletter` int NOT NULL DEFAULT '0',
  `validation` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '0',
  `affiliate` int NOT NULL DEFAULT '0',
  `customergroup` varchar(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `paymentpreset` int NOT NULL,
  `language` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `subshopID` int NOT NULL,
  `referer` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `pricegroupID` int unsigned DEFAULT NULL,
  `internalcomment` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `failedlogins` int NOT NULL,
  `lockeduntil` datetime DEFAULT NULL,
  `default_billing_address_id` int DEFAULT NULL,
  `default_shipping_address_id` int DEFAULT NULL,
  `title` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `salutation` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `firstname` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `lastname` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `customernumber` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `login_token` varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `changed` datetime DEFAULT NULL,
  `password_change_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `register_opt_in_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `email` (`email`),
  KEY `sessionID` (`sessionID`),
  KEY `firstlogin` (`firstlogin`),
  KEY `lastlogin` (`lastlogin`),
  KEY `pricegroupID` (`pricegroupID`),
  KEY `customergroup` (`customergroup`),
  KEY `validation` (`validation`),
  KEY `default_billing_address_id` (`default_billing_address_id`),
  KEY `default_shipping_address_id` (`default_shipping_address_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_user` (`id`, `password`, `encoder`, `email`, `active`, `accountmode`, `confirmationkey`, `paymentID`, `doubleOptinRegister`, `doubleOptinEmailSentDate`, `doubleOptinConfirmDate`, `firstlogin`, `lastlogin`, `sessionID`, `newsletter`, `validation`, `affiliate`, `customergroup`, `paymentpreset`, `language`, `subshopID`, `referer`, `pricegroupID`, `internalcomment`, `failedlogins`, `lockeduntil`, `default_billing_address_id`, `default_shipping_address_id`, `title`, `salutation`, `firstname`, `lastname`, `birthday`, `customernumber`, `login_token`, `changed`, `password_change_date`, `register_opt_in_id`) VALUES
(1,	'$2y$10$7PFqyAXXdd/LRDr5VxL4Su1mbMBjtXzJ8gjwMC0bLuUi2mTGKpziC',	'bcrypt',	NULL,	1,	0,	'',	5,	0,	NULL,	NULL,	'2026-02-05',	'2026-02-05 14:25:13',	'j4nbs5rt11hpj56fo8v81ug1sc',	0,	'',	0,	'EK',	0,	NULL,	1,	'https://shopware.build/',	NULL,	'',	0,	NULL,	1,	1,	NULL,	'mr',	'Test',	'Test',	NULL,	'20003',	'a93cbcbf-d63b-4a73-9df8-fc0d52acb838.1',	'2026-02-05 15:25:13',	'2026-02-05 15:25:12',	NULL);

DROP TABLE IF EXISTS `s_user_addresses`;
CREATE TABLE `s_user_addresses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `company` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `department` varchar(35) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `salutation` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `title` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `firstname` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `lastname` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `street` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `zipcode` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `city` varchar(70) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `country_id` int NOT NULL,
  `state_id` int DEFAULT NULL,
  `ustid` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `phone` varchar(40) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `additional_address_line1` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `additional_address_line2` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `country_id` (`country_id`),
  KEY `state_id` (`state_id`),
  CONSTRAINT `s_user_addresses_ibfk_1` FOREIGN KEY (`country_id`) REFERENCES `s_core_countries` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `s_user_addresses_ibfk_2` FOREIGN KEY (`state_id`) REFERENCES `s_core_countries_states` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `s_user_addresses_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `s_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_user_addresses` (`id`, `user_id`, `company`, `department`, `salutation`, `title`, `firstname`, `lastname`, `street`, `zipcode`, `city`, `country_id`, `state_id`, `ustid`, `phone`, `additional_address_line1`, `additional_address_line2`) VALUES
(1,	1,	NULL,	NULL,	'mr',	NULL,	'Test',	'Test',	'teststraße 1',	'12345',	'testort',	2,	NULL,	NULL,	NULL,	NULL,	NULL);

DROP TABLE IF EXISTS `s_user_addresses_attributes`;
CREATE TABLE `s_user_addresses_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `address_id` int NOT NULL,
  `text1` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `text2` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `text3` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `text4` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `text5` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `text6` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `address_id` (`address_id`),
  CONSTRAINT `s_user_addresses_attributes_ibfk_1` FOREIGN KEY (`address_id`) REFERENCES `s_user_addresses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_user_addresses_attributes` (`id`, `address_id`, `text1`, `text2`, `text3`, `text4`, `text5`, `text6`) VALUES
(1,	1,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL);

DROP TABLE IF EXISTS `s_user_attributes`;
CREATE TABLE `s_user_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `userID` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userID` (`userID`),
  CONSTRAINT `s_user_attributes_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `s_user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `s_user_attributes` (`id`, `userID`) VALUES
(1,	1);

DROP TABLE IF EXISTS `s_user_billingaddress`;
CREATE TABLE `s_user_billingaddress` (
  `id` int NOT NULL AUTO_INCREMENT,
  `userID` int NOT NULL DEFAULT '0',
  `company` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `department` varchar(35) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `salutation` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `firstname` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `lastname` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `street` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `zipcode` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `city` varchar(70) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `phone` varchar(40) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `countryID` int NOT NULL DEFAULT '0',
  `stateID` int DEFAULT NULL,
  `ustid` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `additional_address_line1` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `additional_address_line2` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `title` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userID` (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_user_billingaddress_attributes`;
CREATE TABLE `s_user_billingaddress_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `billingID` int DEFAULT NULL,
  `text1` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `text2` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `text3` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `text4` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `text5` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `text6` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `billingID` (`billingID`),
  CONSTRAINT `s_user_billingaddress_attributes_ibfk_1` FOREIGN KEY (`billingID`) REFERENCES `s_user_billingaddress` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_user_shippingaddress`;
CREATE TABLE `s_user_shippingaddress` (
  `id` int NOT NULL AUTO_INCREMENT,
  `userID` int NOT NULL DEFAULT '0',
  `company` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `department` varchar(35) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `salutation` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `firstname` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `lastname` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `street` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `zipcode` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `city` varchar(70) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `countryID` int DEFAULT NULL,
  `stateID` int DEFAULT NULL,
  `additional_address_line1` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `additional_address_line2` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `title` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userID` (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


DROP TABLE IF EXISTS `s_user_shippingaddress_attributes`;
CREATE TABLE `s_user_shippingaddress_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `shippingID` int DEFAULT NULL,
  `text1` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `text2` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `text3` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `text4` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `text5` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `text6` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shippingID` (`shippingID`),
  CONSTRAINT `s_user_shippingaddress_attributes_ibfk_1` FOREIGN KEY (`shippingID`) REFERENCES `s_user_shippingaddress` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


-- 2026-02-05 14:48:18 UTC
