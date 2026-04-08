SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS proof_keep_articles;
CREATE TABLE proof_keep_articles (id INT NOT NULL PRIMARY KEY);
INSERT INTO proof_keep_articles
SELECT id
FROM s_articles
WHERE mode = 0
ORDER BY id
LIMIT 30;
INSERT IGNORE INTO proof_keep_articles
SELECT DISTINCT articleID
FROM s_articles_vote;

DROP TABLE IF EXISTS proof_keep_article_details;
CREATE TABLE proof_keep_article_details (id INT NOT NULL PRIMARY KEY);
INSERT INTO proof_keep_article_details
SELECT id
FROM s_articles_details
WHERE articleID IN (SELECT id FROM proof_keep_articles);

DROP TABLE IF EXISTS proof_keep_article_images;
CREATE TABLE proof_keep_article_images (id INT NOT NULL PRIMARY KEY);
INSERT INTO proof_keep_article_images
SELECT MIN(id)
FROM s_articles_img
WHERE articleID IN (SELECT id FROM proof_keep_articles)
GROUP BY articleID;

DROP TABLE IF EXISTS proof_keep_orders;
CREATE TABLE proof_keep_orders (id INT NOT NULL PRIMARY KEY);
INSERT INTO proof_keep_orders
SELECT id
FROM s_order
ORDER BY id
LIMIT 2;

DROP TABLE IF EXISTS proof_keep_users;
CREATE TABLE proof_keep_users (id INT NOT NULL PRIMARY KEY);
INSERT IGNORE INTO proof_keep_users
SELECT DISTINCT userID
FROM s_order
WHERE id IN (SELECT id FROM proof_keep_orders)
AND userID IS NOT NULL;

DROP TABLE IF EXISTS proof_keep_categories;
CREATE TABLE proof_keep_categories (id INT UNSIGNED NOT NULL PRIMARY KEY);
INSERT IGNORE INTO proof_keep_categories
SELECT category_id
FROM s_core_shops
WHERE category_id IS NOT NULL;
INSERT IGNORE INTO proof_keep_categories
SELECT DISTINCT categoryID
FROM s_articles_categories
WHERE articleID IN (SELECT id FROM proof_keep_articles);
INSERT IGNORE INTO proof_keep_categories
SELECT DISTINCT c.id
FROM s_categories c
JOIN s_categories kc
    ON kc.id IN (SELECT id FROM proof_keep_categories)
WHERE FIND_IN_SET(c.id, REPLACE(TRIM(BOTH '|' FROM kc.path), '|', ',')) > 0;
INSERT IGNORE INTO proof_keep_categories
SELECT DISTINCT parent
FROM s_categories
WHERE id IN (SELECT id FROM proof_keep_categories)
AND parent IS NOT NULL;

DROP TABLE IF EXISTS proof_keep_suppliers;
CREATE TABLE proof_keep_suppliers (id INT NOT NULL PRIMARY KEY);
INSERT INTO proof_keep_suppliers
SELECT DISTINCT supplierID
FROM s_articles
WHERE id IN (SELECT id FROM proof_keep_articles)
AND supplierID IS NOT NULL;

DROP TABLE IF EXISTS proof_keep_rewrite_urls;
CREATE TABLE proof_keep_rewrite_urls (id INT UNSIGNED NOT NULL PRIMARY KEY);
INSERT INTO proof_keep_rewrite_urls
SELECT id
FROM s_core_rewrite_urls
ORDER BY id
LIMIT 120;

DROP TABLE IF EXISTS proof_keep_media;
CREATE TABLE proof_keep_media (id INT NOT NULL PRIMARY KEY);
INSERT IGNORE INTO proof_keep_media
SELECT media_id
FROM s_articles_img
WHERE id IN (SELECT id FROM proof_keep_article_images)
AND media_id IS NOT NULL;
INSERT IGNORE INTO proof_keep_media
SELECT mediaID
FROM s_categories
WHERE id IN (SELECT id FROM proof_keep_categories)
AND mediaID IS NOT NULL;
INSERT IGNORE INTO proof_keep_media
SELECT m.id
FROM s_media m
JOIN s_articles_supplier s
    ON s.img = m.path
WHERE s.id IN (SELECT id FROM proof_keep_suppliers)
AND s.img <> '';
INSERT IGNORE INTO proof_keep_media
SELECT media_id
FROM s_filter_values
WHERE media_id IS NOT NULL;

DELETE FROM s_articles_attributes
WHERE articledetailsID NOT IN (SELECT id FROM proof_keep_article_details);
DELETE FROM s_articles_prices
WHERE articledetailsID NOT IN (SELECT id FROM proof_keep_article_details);
DELETE FROM s_articles_translations
WHERE articleID NOT IN (SELECT id FROM proof_keep_articles);
DELETE FROM s_articles_information
WHERE articleID NOT IN (SELECT id FROM proof_keep_articles);
TRUNCATE s_articles_notification;
TRUNCATE s_articles_notification_attributes;
DELETE FROM s_articles_avoid_customergroups
WHERE articleID NOT IN (SELECT id FROM proof_keep_articles);
DELETE FROM s_articles_similar
WHERE articleID NOT IN (SELECT id FROM proof_keep_articles)
OR relatedarticle NOT IN (SELECT id FROM proof_keep_articles);
DELETE FROM s_articles_relationships
WHERE articleID NOT IN (SELECT id FROM proof_keep_articles)
OR relatedarticle NOT IN (SELECT id FROM proof_keep_articles);
DELETE FROM s_articles_vote
WHERE articleID NOT IN (SELECT id FROM proof_keep_articles);
DELETE FROM s_articles_categories
WHERE articleID NOT IN (SELECT id FROM proof_keep_articles)
OR categoryID NOT IN (SELECT id FROM proof_keep_categories);
DELETE FROM s_articles_categories_ro
WHERE articleID NOT IN (SELECT id FROM proof_keep_articles)
OR categoryID NOT IN (SELECT id FROM proof_keep_categories);
DELETE FROM s_articles_categories_seo
WHERE article_id NOT IN (SELECT id FROM proof_keep_articles)
OR category_id NOT IN (SELECT id FROM proof_keep_categories);
DELETE FROM s_articles_img_attributes
WHERE imageID NOT IN (SELECT id FROM proof_keep_article_images);
DELETE FROM s_articles_img
WHERE id NOT IN (SELECT id FROM proof_keep_article_images);
DELETE FROM s_articles_details
WHERE id NOT IN (SELECT id FROM proof_keep_article_details);
DELETE FROM s_articles
WHERE id NOT IN (SELECT id FROM proof_keep_articles);
DELETE FROM s_articles_supplier_attributes
WHERE supplierID NOT IN (SELECT id FROM proof_keep_suppliers);
DELETE FROM s_articles_supplier
WHERE id NOT IN (SELECT id FROM proof_keep_suppliers);

TRUNCATE s_articles_downloads;
TRUNCATE s_articles_downloads_attributes;
TRUNCATE s_articles_esd;
TRUNCATE s_articles_esd_attributes;
TRUNCATE s_articles_esd_serials;

DELETE FROM s_categories
WHERE id NOT IN (SELECT id FROM proof_keep_categories);
DELETE FROM s_core_rewrite_urls
WHERE id NOT IN (SELECT id FROM proof_keep_rewrite_urls);

DELETE FROM s_media_attributes
WHERE mediaID NOT IN (SELECT id FROM proof_keep_media);
DELETE FROM s_media_association
WHERE mediaID NOT IN (SELECT id FROM proof_keep_media);
DELETE FROM s_media
WHERE id NOT IN (SELECT id FROM proof_keep_media);

DELETE FROM s_order_details
WHERE orderID NOT IN (SELECT id FROM proof_keep_orders);
DELETE FROM s_order_attributes
WHERE orderID NOT IN (SELECT id FROM proof_keep_orders);
DELETE FROM s_order_billingaddress
WHERE orderID NOT IN (SELECT id FROM proof_keep_orders);
DELETE FROM s_order_billingaddress_attributes
WHERE billingID NOT IN (SELECT id FROM s_order_billingaddress);
DELETE FROM s_order_shippingaddress
WHERE orderID NOT IN (SELECT id FROM proof_keep_orders);
DELETE FROM s_order_shippingaddress_attributes
WHERE shippingID NOT IN (SELECT id FROM s_order_shippingaddress);
DELETE FROM s_order_history
WHERE orderID NOT IN (SELECT id FROM proof_keep_orders);
DELETE FROM s_order
WHERE id NOT IN (SELECT id FROM proof_keep_orders);

TRUNCATE s_order_documents;
TRUNCATE s_order_documents_attributes;
TRUNCATE s_order_esd;
TRUNCATE s_order_basket;
TRUNCATE s_order_basket_attributes;
TRUNCATE s_order_basket_signatures;
TRUNCATE s_order_comparisons;
TRUNCATE s_order_notes;

DELETE FROM s_user_addresses
WHERE user_id NOT IN (SELECT id FROM proof_keep_users);
DELETE FROM s_user_addresses_attributes
WHERE address_id NOT IN (SELECT id FROM s_user_addresses);
DELETE FROM s_user_attributes
WHERE userID NOT IN (SELECT id FROM proof_keep_users);
DELETE FROM s_user
WHERE id NOT IN (SELECT id FROM proof_keep_users);

TRUNCATE s_articles_also_bought_ro;
TRUNCATE s_articles_similar_shown_ro;
TRUNCATE s_articles_top_seller_ro;
TRUNCATE s_search_index;
TRUNCATE s_search_keywords;
TRUNCATE s_core_sessions;
TRUNCATE s_statistics_article_impression;
TRUNCATE s_statistics_currentusers;
TRUNCATE s_statistics_pool;
TRUNCATE s_statistics_search;
TRUNCATE s_statistics_visitors;

DROP TABLE proof_keep_media;
DROP TABLE proof_keep_rewrite_urls;
DROP TABLE proof_keep_suppliers;
DROP TABLE proof_keep_categories;
DROP TABLE proof_keep_users;
DROP TABLE proof_keep_orders;
DROP TABLE proof_keep_article_images;
DROP TABLE proof_keep_article_details;
DROP TABLE proof_keep_articles;

SET FOREIGN_KEY_CHECKS = 1;
