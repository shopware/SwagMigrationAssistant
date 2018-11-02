<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Run;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TenantIdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Flag\Required;
use SwagMigrationNext\Migration\Data\SwagMigrationDataDefinition;

class SwagMigrationRunDefinition extends EntityDefinition
{
    public static function getEntityName(): string
    {
        return 'swag_migration_run';
    }

    public static function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new PrimaryKey(), new Required()),
            new TenantIdField(),
            (new StringField('profile', 'profile'))->setFlags(new Required()),
            new JsonField('totals', 'totals'),
            new CreatedAtField(),
            new UpdatedAtField(),
            new OneToManyAssociationField('data', SwagMigrationDataDefinition::class, 'run_id', false),
        ]);
    }

    public static function getCollectionClass(): string
    {
        return SwagMigrationRunCollection::class;
    }

    public static function getStructClass(): string
    {
        return SwagMigrationRunStruct::class;
    }
}
