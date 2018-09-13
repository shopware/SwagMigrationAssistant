<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Run;

use Shopware\Core\Framework\ORM\EntityDefinition;
use Shopware\Core\Framework\ORM\Field\CreatedAtField;
use Shopware\Core\Framework\ORM\Field\IdField;
use Shopware\Core\Framework\ORM\Field\JsonField;
use Shopware\Core\Framework\ORM\Field\OneToManyAssociationField;
use Shopware\Core\Framework\ORM\Field\StringField;
use Shopware\Core\Framework\ORM\Field\TenantIdField;
use Shopware\Core\Framework\ORM\Field\UpdatedAtField;
use Shopware\Core\Framework\ORM\FieldCollection;
use Shopware\Core\Framework\ORM\Write\Flag\PrimaryKey;
use Shopware\Core\Framework\ORM\Write\Flag\Required;
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
