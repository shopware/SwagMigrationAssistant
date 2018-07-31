<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Mapping;

use Shopware\Core\Framework\ORM\EntityDefinition;
use Shopware\Core\Framework\ORM\Field\IdField;
use Shopware\Core\Framework\ORM\Field\JsonField;
use Shopware\Core\Framework\ORM\Field\StringField;
use Shopware\Core\Framework\ORM\Field\TenantIdField;
use Shopware\Core\Framework\ORM\FieldCollection;
use Shopware\Core\Framework\ORM\Write\Flag\PrimaryKey;
use Shopware\Core\Framework\ORM\Write\Flag\Required;

class SwagMigrationMappingDefinition extends EntityDefinition
{
    public static function getEntityName(): string
    {
        return 'swag_migration_mapping';
    }

    protected static function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new PrimaryKey(), new Required()),
            new TenantIdField(),
            (new StringField('profile', 'profile'))->setFlags(new Required()),
            (new StringField('entity', 'entity'))->setFlags(new Required()),
            new StringField('old_identifier', 'oldIdentifier'),
            new IdField('entity_uuid', 'entityUuid'),
            new JsonField('additional_data', 'additionalData'),
        ]);
    }
}
