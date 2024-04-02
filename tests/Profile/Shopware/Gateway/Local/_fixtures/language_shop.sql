INSERT INTO `s_categories` (`id`, `parent`, `path`, `description`, `position`, `left`, `right`, `level`, `added`,
                            `changed`, `metakeywords`, `metadescription`, `cmsheadline`, `cmstext`, `template`,
                            `active`, `blog`, `external`, `hidefilter`, `hidetop`, `mediaID`, `product_box_layout`,
                            `meta_title`, `stream_id`, `hide_sortings`, `sorting_ids`, `facet_ids`, `external_target`,
                            `shops`)
VALUES (76, 1, '', 'Französisch', NULL, 0, 0, 0, '2024-02-16 07:41:19', '2024-02-16 07:41:19', NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, 0, 0, NULL, NULL, NULL, NULL, 0, NULL, '', '', NULL),
       (77, 76, '|76|', 'Baguette', NULL, 0, 0, 0, '2024-02-16 07:41:58', '2024-02-16 07:41:58', NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, 0, 0, NULL, NULL, NULL, NULL, 0, NULL, '', '', NULL);


INSERT INTO `s_core_shops` (`id`, `main_id`, `name`, `title`, `position`, `host`, `base_path`, `base_url`, `hosts`,
                            `secure`, `template_id`, `document_template_id`, `category_id`, `locale_id`, `currency_id`,
                            `customer_group_id`, `fallback_id`, `customer_scope`, `default`, `active`)
VALUES (3, NULL, 'International', 'International', 0, 'international.shopware5.localhost', NULL, NULL, '', 0, 22, 22, 76, 108, 1, 1, 2, 0, 0, 1),
       (4, 3, 'Französisch', 'Französisch', 0, NULL, NULL, '/fr', '', 0, NULL, NULL, 76, 108, 1, 1, 3, 0, 0, 1);


INSERT INTO `s_articles` (`id`, `supplierID`, `name`, `description`, `description_long`, `shippingtime`, `datum`,
                          `active`, `taxID`, `pseudosales`, `topseller`, `metaTitle`, `keywords`, `changetime`,
                          `pricegroupID`, `pricegroupActive`, `filtergroupID`, `laststock`, `crossbundlelook`,
                          `notification`, `template`, `mode`, `main_detail_id`, `available_from`, `available_to`,
                          `configurator_set_id`)
VALUES (20273, 1, 'Some French cool name', '', '<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua</p>', NULL, '2024-02-16', 1, 1, 0, 0, '', '', '2024-02-16 07:46:36', NULL, 0, NULL, 0, 0, 0, '', 0, 20828, NULL, NULL, NULL);


INSERT INTO `s_articles_details` (`id`, `articleID`, `ordernumber`, `suppliernumber`, `kind`, `additionaltext`, `sales`,
                                  `active`, `instock`, `stockmin`, `laststock`, `weight`, `position`, `width`, `height`,
                                  `length`, `ean`, `unitID`, `purchasesteps`, `maxpurchase`, `minpurchase`,
                                  `purchaseunit`, `referenceunit`, `packunit`, `releasedate`, `shippingfree`,
                                  `shippingtime`, `purchaseprice`)
VALUES (20828, 20273, 'SW10002', '', 1, '', 0, 1, 999999, 0, 0, NULL, 0, NULL, NULL, NULL, '', NULL, NULL, NULL, 1, NULL, NULL, '', NULL, 0, '', 0);


INSERT INTO `s_articles_prices` (`id`, `pricegroup`, `from`, `to`, `articleID`, `articledetailsID`, `price`,
                                 `pseudoprice`,  `baseprice`, `percent`)
VALUES (201029, 'EK', 1, 'beliebig', 20273, 20828, 1.0840336134454, 0, NULL, 0.00);


INSERT INTO `s_articles_categories` (`id`, `articleID`, `categoryID`)
VALUES (3824, 20273, 77);
