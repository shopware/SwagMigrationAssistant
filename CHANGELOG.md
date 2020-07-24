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
