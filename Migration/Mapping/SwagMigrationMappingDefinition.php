<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionDefinition;

#[Package('services-settings')]
class SwagMigrationMappingDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'swag_migration_mapping';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new FkField('connection_id', 'connectionId', SwagMigrationConnectionDefinition::class))->addFlags(new Required()),
            (new StringField('entity', 'entity'))->addFlags(new Required()),
            new StringField('old_identifier', 'oldIdentifier'),
            new IdField('entity_uuid', 'entityUuid'),
            new StringField('entity_value', 'entityValue'),
            new StringField('checksum', 'checksum'),
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
