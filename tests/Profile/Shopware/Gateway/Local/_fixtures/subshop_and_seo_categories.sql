INSERT INTO `s_core_shops` (`id`, `main_id`, `name`, `title`, `position`, `host`, `hosts`, `secure`, `template_id`,
                            `document_template_id`, `category_id`, `locale_id`, `currency_id`, `customer_group_id`,
                            `customer_scope`, `default`, `active`)
VALUES (3, NULL, 'FooBarSubShop', '', 0, 'foobar.de', '', 0, 23, 23, 3, 1, 1, 1, 1, 0, 1);

INSERT INTO `s_articles_categories_seo` (`id`, `shop_id`, `article_id`, `category_id`)
VALUES (3, 1, 272, 15),
       (4, 2, 272, 51),
       (5, 3, 272, 16),
       (6, 1, 9, 14),
       (7, 2, 9, 50),
       (8, 3, 9, 34);
