<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Profile;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use SwagMigrationNext\Migration\Connection\SwagMigrationConnectionDefinition;

class SwagMigrationProfileDefinition extends EntityDefinition
{
    public static function getEntityName(): string
    {
        return 'swag_migration_profile';
    }

    public static function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new PrimaryKey(), new Required()),
            (new StringField('name', 'name'))->setFlags(new Required()),
            (new StringField('gateway_name', 'gatewayName'))->setFlags(new Required()),
            new CreatedAtField(),
            new UpdatedAtField(),
            new OneToManyAssociationField('connections', SwagMigrationConnectionDefinition::class, 'profile_id'),
        ]);
    }

    public static function getCollectionClass(): string
    {
        return SwagMigrationProfileCollection::class;
    }

    public static function getEntityClass(): string
    {
        return SwagMigrationProfileEntity::class;
    }
}
