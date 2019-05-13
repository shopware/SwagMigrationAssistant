<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Mapping;

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
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionDefinition;

class SwagMigrationMappingDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'swag_migration_mapping';
    }

    public function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new PrimaryKey(), new Required()),
            (new FkField('connection_id', 'connectionId', SwagMigrationConnectionDefinition::class))->setFlags(new Required()),
            (new StringField('entity', 'entity'))->setFlags(new Required()),
            new StringField('old_identifier', 'oldIdentifier'),
            new IdField('entity_uuid', 'entityUuid'),
            new JsonField('additional_data', 'additionalData'),
            new CreatedAtField(),
            new UpdatedAtField(),
            new ManyToOneAssociationField('connection', 'connection_id', SwagMigrationConnectionDefinition::class),
        ]);
    }

    public function getCollectionClass(): string
    {
        return SwagMigrationMappingCollection::class;
    }

    public function getEntityClass(): string
    {
        return SwagMigrationMappingEntity::class;
    }
}
