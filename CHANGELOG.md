# 4.2.5
- MIG-293 - Improve performance of order documents migration

# 4.2.4
- MIG-246 - Fix resetting of connection settings
- MIG-262 - Customer migrations are no longer grouped by email address
- MIG-279 - Fix pre-mapping

# 4.2.3
- MIG-269 - Fix missing order data for systems where MySQL trigger do not work

# 4.2.2
- MIG-263 - Fixes a problem, where order addresses might be erroneously identical or interchanged
- MIG-260 - Fix sales channel migration with Shopware language pack

# 4.2.1
- MIG-252 - Fix profile installer view

# 4.2.0
- MIG-100 - Add migration profile for Shopware 5.7
- MIG-243 - Fix migration of orders for Shopware 6 profile
- MIG-247 - Fix write protection errors for Shopware 6 migration

# 4.1.1
- MIG-206 - Migrate product creation date
- MIG-237 - Fix customer vatId migration
- MIG-240 - Optimize customer order count indexer

# 4.1.0
- MIG-126 - Add migration profile for Shopware 6
- MIG-221 - Fix migration of invoices
- MIG-224 - Optimize attribute value migration handling from SW5
- MIG-233 - Migration of notes / wishlists added

# 4.0.0
- MIG-203 - Shopware 6.4 compatibility
- MIG-220 - Optimize product image cover migration

# 3.0.2
- MIG-218 - Prevents abortion of a migration if customer has an invalid email address
- MIG-219 - Solves an issue on migration translations of custom fields

# 3.0.1
- MIG-213 - Prevents abortion of a migration if some products with variants could not be written
- MIG-214 - Improves progress display in CLI
- MIG-216 - Fix issue with customer emails longer than 64 characters

# 3.0.0
- MIG-125 - Improves the migration of orders, that customer order count is indexed
- MIG-181 - Provide migration of main variant information for Shopware 5.4 / 5.6
- MIG-182 - Migration of vouchers added
- MIG-187 - Improves the migration of media without filename
- MIG-188 - Improves media download stability
- MIG-189 - Fix migration of product line items
- MIG-194 - Optimizes sales channel migration
- MIG-196 - Improve the extendability of the plugin

# 2.2.2
- MIG-110 - Improves the migration of media
- MIG-114 - Provide migration of main variant information
- MIG-118 - Fix migration of credit line items
- MIG-120 - Solves an issue when loading the pre-mapping
- MIG-162 - Solves an issue on migrating products with empty custom fields
- MIG-167 - Solves an issue on migrating custom field values
- MIG-168 - Optimized request options

# 2.2.1
- MIG-105 - Add warning if default languages differ
- MIG-107 - Improves the migration of shipping methods
- MIG-109 - Improve migration of orders

# 2.2.0
- MIG-75 - Improves the takeover of a migration
- MIG-106 - Improves the migration of order line items
- MIG-124 - Added ACL privileges

# 2.1.2
- MIG-85 - Migrate customer comments in orders
- MIG-90 - Fix variant migration
- MIG-92 - Fix broken history download
- MIG-98 - Fix premappings without description
- MIG-103 - Improves the migration of variant translations

# 2.1.1
- MIG-39 - Optimized basic converter
- MIG-72 - Recognize correct category type when external link is set
- MIG-73 - Recognize variant attribute translations on SW5 migration
- MIG-74 - Optimize migration of custom fields

# 2.1.0
- MIG-13 - Migrate product reviews without customer
- MIG-28 - Optimized rebuilding of container for deactivation and activation of the plugin

# 2.0.0
- MIG-3 - Fixes a problem with migrating order documents
- MIG-5 - Improve snippet loading of DataSets
- MIG-14 - Implement deletion of logs from a selected run
- MIG-22 - Fixes a problem with migrating orders, caused by aborted orders
- MIG-23 - Fixes a problem with log file download

# 1.7.1
- MIG-6 - Add functionality to save premapping without start migration for CLI support

# 1.7.0
- PT-11910 - Add migration of cross selling
- PT-11922 - Shopware 6.3 compatibility
- PT-11955 - Fixes a problem with saving media

# 1.6.0
- PT-11692 - Add functionality to finish migration and remove unneeded data
- PT-11864 - Improve media file processing
- PT-11942 - Improve migration of product-translations

# 1.5.3
- PT-11845 - Improve migration of customers
- PT-11855 - Improve migration of media

# 1.5.2
- PT-11788 - Introduce migration of pseudo prices from SW5

# 1.5.1
- PT-11819 - Optimize product variant migration for Shopware 5 profile

# 1.5.0
- PT-11692 - Move migration dashboard card to own component
- PT-11747 - Ignore seo urls without type id
- PT-11764 - Add sorting of pre-mapping values

# 1.4.2
- PT-11689 - Add survey to get feedback about the tool from users

# 1.4.1
- NTR - Solve issue with cache service injection

# 1.4.0
- PT-11497 - Solves an issue with incorrect connection state
- PT-11601 - Shopware 6.2 compatibility
- PT-11462 - Solves an issue regarding order migration

# 1.3.0
- PT-11586 - Optimized product migration from Shopware 5
- PT-11617 - Fix error with too many open database connections

# 1.2.2
- NTR - Fixes an issue with the layout when resetting the checksums

# 1.2.1
- NTR - Fixes an issue migrating media folder thumbnail settings from Shopware 5

# 1.2.0
- PT-11450 - It is now possible to reset the checksums via a button in the connection management drop-down menu.
- PT-11525 - Optimize media migration process

# 1.1.0
- PT-10832 - Preventing an undesired state when creating new connections
- PT-10983 - Technical concept of the user interfaces changed to Vuex
- PT-11331 - Fix request timeout for bigger migrations
- PT-11394 - Fix product visibility for nested shop structures
- PT-11400 - Migration error at wrong defined thumbnail sizes fixed

# 1.0.3
- PT-11329 - Migrate meta data for products and categories
- NTR - Solves an issue with database field length difference between SW5 and SW6

# 1.0.2
- NTR - Improve progress calculation for big amounts of data

# 1.0.1
- NTR - Solves an issue with delta checksums when aborting migration

# 1.0.0
- PT-11113 - Refactor plugin icons
- PT-11111 - Refactor profile icon for external profile
- NTR - Fix refresh after wizard install of external profile
- NTR - Snippet renaming
- PT-11252 - Force number range migration

# 0.40.0
- PT-11014- Add magento onboarding to migration wizard
- PT-11016 - Refactor first migration wizard page
- PT-11017 - Add migration card to dashboard
- PT-11033 - Fix migration of categories
- PT-11020 - Implement measurement call
- NTR - Fix new plugin folder structure
- NTR - Stabilize migration data writer
- NTR - Refactor datasets
- NTR - Refactor api total fetching
- NTR - Refactor reader interface and classes
- NTR - Fix product image cover when only 1 image exists

# 0.30.1
- PT-10925 - Call indexing controller after every migration
- PT-10948 - Prevent duplicate document types
- PT-10946 - Migrate customer language


# 0.30.0
- PT-10629 - Raise test coverage
- PT-10761 - Implement new frontend data handling
- PT-10783 - Migrate empty labels with attribute name
- PT-10797 - Fix product active state during migration
- NTR - Implement partial indexing via message queue and fix tests
- PT-10800 - Fix rising of mapping entries for properties and options
- PT-10818 - Fix custom field migration
- PT-10819 - Fix newsletter recipients migration
- PT-10835 - Fix migration of shippingfree products
- PT-10844 - Migrate deliveryTime of products
- PT-10769 - Fix json_encode error on logging
- PT-10846 - Migrate product reviews
- PT-10847 - Fix sales channel error for multiple migrations
- NTR - Fix order state premapping
- PT-10793 - Use checksum for migration
- PT-10861 - Migrate seo urls
- PT-10718 - Remove unprocessed media entries
- PT-10875 - Cleanup unwritten migration data when run new starts

# 0.20.0
- Refactor imports to global object
- Refactor deprecated data handling imports

# 0.10.1
- Add default theme to sales channels
- Fix indexing after migration

# 0.10.0
- Implement Shopware 5.4 & Shopware 5.6 profiles
- Refactor converter and reader structure

# 0.9.0
- First version of the Shopware Migration Assistant for Shopware 6
