<?php declare(strict_types=1);


namespace SwagMigrationNext\Migration\Logging;


use Shopware\Core\Framework\ORM\EntityDefinition;
use Shopware\Core\Framework\ORM\Field\CreatedAtField;
use Shopware\Core\Framework\ORM\Field\FkField;
use Shopware\Core\Framework\ORM\Field\IdField;
use Shopware\Core\Framework\ORM\Field\JsonField;
use Shopware\Core\Framework\ORM\Field\StringField;
use Shopware\Core\Framework\ORM\Field\TenantIdField;
use Shopware\Core\Framework\ORM\Field\UpdatedAtField;
use Shopware\Core\Framework\ORM\FieldCollection;
use Shopware\Core\Framework\ORM\Write\Flag\PrimaryKey;
use Shopware\Core\Framework\ORM\Write\Flag\Required;
use SwagMigrationNext\Migration\Run\SwagMigrationRunDefinition;

class SwagMigrationLoggingDefinition extends EntityDefinition
{
    public static function getEntityName(): string
    {
        return 'swag_migration_logging';
    }

    public static function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new PrimaryKey(), new Required()),
            new TenantIdField(),
            (new FkField('run_id', 'runId', SwagMigrationRunDefinition::class))->setFlags(new Required()),
            new StringField('type', 'type'),
            new JsonField('log_entry', 'logEntry'),
            new CreatedAtField(),
            new UpdatedAtField(),
        ]);
    }

    public static function getCollectionClass(): string
    {
        return SwagMigrationLoggingCollection::class;
    }

    public static function getStructClass(): string
    {
        return SwagMigrationLoggingStruct::class;
    }
}