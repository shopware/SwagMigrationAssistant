<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile;

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
use SwagMigrationNext\Migration\Mapping\SwagMigrationMappingDefinition;

class SwagMigrationProfileDefinition extends EntityDefinition
{
    public static function getEntityName(): string
    {
        return 'swag_migration_profile';
    }

    public static function defineFields(): FieldCollection
    {
        return new FieldCollection([
            new TenantIdField(),
            (new IdField('id', 'id'))->setFlags(new PrimaryKey(), new Required()),
            (new StringField('profile', 'profile'))->setFlags(new Required()),
            (new StringField('gateway', 'gateway'))->setFlags(new Required()),
            new JsonField('credential_fields', 'credentialFields'),
            new CreatedAtField(),
            new UpdatedAtField(),
            new OneToManyAssociationField('mappings', SwagMigrationMappingDefinition::class, 'profile_id', false)
        ]);
    }

    public static function getCollectionClass(): string
    {
        return SwagMigrationProfileCollection::class;
    }

    public static function getStructClass(): string
    {
        return SwagMigrationProfileStruct::class;
    }
}
