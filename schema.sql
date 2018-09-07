CREATE TABLE `swag_migration_data` (
    `id`         BINARY(16)  NOT NULL,
    `tenant_id`  BINARY(16)  NOT NULL,
    `profile`    VARCHAR(255),
    `entity`     VARCHAR(255),
    `raw`        LONGTEXT,
    `converted`  LONGTEXT,
    `unmapped`   LONGTEXT,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3),
    PRIMARY KEY (`id`, `tenant_id`),
    CHECK (JSON_VALID(`raw`)),
    CHECK (JSON_VALID(`converted`)),
    CHECK (JSON_VALID(`unmapped`))
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `swag_migration_mapping` (
    `id`              BINARY(16)  NOT NULL,
    `tenant_id`       BINARY(16)  NOT NULL,
    `profile`         VARCHAR(255),
    `entity`          VARCHAR(255),
    `old_identifier`  VARCHAR(255),
    `entity_uuid`     BINARY(16),
    `additional_data` LONGTEXT,
    `created_at`      DATETIME(3) NOT NULL,
    `updated_at`      DATETIME(3),
    PRIMARY KEY (`id`, `tenant_id`),
    CHECK (JSON_VALID(`additional_data`))
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `swag_migration_profile` (
    `id`                BINARY(16)  NOT NULL,
    `tenant_id`         BINARY(16)  NOT NULL,
    `profile`           VARCHAR(255),
    `gateway`           VARCHAR(255),
    `credential_fields` LONGTEXT,
    `created_at`        DATETIME(3) NOT NULL,
    `updated_at`        DATETIME(3),
    PRIMARY KEY (`id`, `tenant_id`),
    CHECK (JSON_VALID(`credential_fields`))
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;
