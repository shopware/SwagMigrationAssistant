<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Setting;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use SwagMigrationNext\Migration\Connection\SwagMigrationConnectionDefinition;

class GeneralSettingDefinition extends EntityDefinition
{
    public static function getEntityName(): string
    {
        return 'swag_migration_general_setting';
    }

    public static function getCollectionClass(): string
    {
        return GeneralSettingCollection::class;
    }

    public static function getEntityClass(): string
    {
        return GeneralSettingEntity::class;
    }

    protected static function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new PrimaryKey(), new Required()),
            new FkField('selected_connection_id', 'selectedConnectionId', SwagMigrationConnectionDefinition::class),
            new CreatedAtField(),
            new UpdatedAtField(),
            new ManyToOneAssociationField('selectedConnection', 'selected_connection_id', SwagMigrationConnectionDefinition::class, true),
        ]);
    }
}
