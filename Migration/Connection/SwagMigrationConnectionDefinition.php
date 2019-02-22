<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Connection;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Flag\WriteProtected;
use SwagMigrationNext\Migration\Mapping\SwagMigrationMappingDefinition;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Profile\SwagMigrationProfileDefinition;
use SwagMigrationNext\Migration\Run\SwagMigrationRunDefinition;

class SwagMigrationConnectionDefinition extends EntityDefinition
{
    public static function getEntityName(): string
    {
        return 'swag_migration_connection';
    }

    public static function getCollectionClass(): string
    {
        return SwagMigrationConnectionCollection::class;
    }

    public static function getEntityClass(): string
    {
        return SwagMigrationConnectionEntity::class;
    }

    protected static function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new PrimaryKey(), new Required()),
            (new StringField('name', 'name'))->setFlags(new Required()),
            (new JsonField('credential_fields', 'credentialFields'))->setFlags(new WriteProtected(MigrationContext::SOURCE_CONTEXT)),
            (new FkField('profile_id', 'profileId', SwagMigrationProfileDefinition::class))->setFlags(new Required()),
            new CreatedAtField(),
            new UpdatedAtField(),
            new ManyToOneAssociationField('profile', 'profile_id', SwagMigrationProfileDefinition::class, true),
            new OneToManyAssociationField('runs', SwagMigrationRunDefinition::class, 'connection_id', false),
            new OneToManyAssociationField('mappings', SwagMigrationMappingDefinition::class, 'connection_id', false),
        ]);
    }
}
