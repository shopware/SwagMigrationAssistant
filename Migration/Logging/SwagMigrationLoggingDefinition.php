<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Logging;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
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
            (new FkField('run_id', 'runId', SwagMigrationRunDefinition::class))->setFlags(new Required()),
            (new StringField('type', 'type'))->setFlags(new Required()),
            (new JsonField('log_entry', 'logEntry'))->setFlags(new Required()),
            new CreatedAtField(),
            new UpdatedAtField(),
            new ManyToOneAssociationField('run', 'run_id', SwagMigrationRunDefinition::class),
        ]);
    }

    public static function getCollectionClass(): string
    {
        return SwagMigrationLoggingCollection::class;
    }

    public static function getEntityClass(): string
    {
        return SwagMigrationLoggingEntity::class;
    }
}
