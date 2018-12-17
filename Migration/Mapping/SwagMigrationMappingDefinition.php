<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Mapping;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Flag\Required;
use SwagMigrationNext\Migration\Profile\SwagMigrationProfileDefinition;

class SwagMigrationMappingDefinition extends EntityDefinition
{
    public static function getEntityName(): string
    {
        return 'swag_migration_mapping';
    }

    public static function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new PrimaryKey(), new Required()),
            (new FkField('profile_id', 'profileId', SwagMigrationProfileDefinition::class))->setFlags(new Required()),
            (new StringField('entity', 'entity'))->setFlags(new Required()),
            new StringField('old_identifier', 'oldIdentifier'),
            new IdField('entity_uuid', 'entityUuid'),
            new JsonField('additional_data', 'additionalData'),
            new CreatedAtField(),
            new UpdatedAtField(),
            new ManyToOneAssociationField('profile', 'profile_id', SwagMigrationProfileDefinition::class, false),
        ]);
    }

    public static function getCollectionClass(): string
    {
        return SwagMigrationMappingCollection::class;
    }

    public static function getEntityClass(): string
    {
        return SwagMigrationMappingEntity::class;
    }
}
