CREATE TABLE `swag_migration_data` (
  `id`          binary(16) NOT NULL,
  `tenant_id`   binary(16) NOT NULL,
  `profile`     VARCHAR(255),
  `entity_type` VARCHAR(255),
  `raw`         LONGTEXT,
  `mapped`      LONGTEXT,
  `unmapped`    LONGTEXT,
  `created_at`  datetime,
  `updated_at`  datetime,
  PRIMARY KEY (`id`, `tenant_id`, `profile`, `entity_type`),
  CHECK (JSON_VALID(`raw`)),
  CHECK (JSON_VALID(`mapped`)),
  CHECK (JSON_VALID(`unmapped`))
) ENGINE = InnoDBDEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
