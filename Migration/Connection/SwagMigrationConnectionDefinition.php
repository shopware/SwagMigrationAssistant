<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Connection;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use SwagMigrationAssistant\Migration\Mapping\SwagMigrationMappingDefinition;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunDefinition;
use SwagMigrationAssistant\Migration\Setting\GeneralSettingDefinition;

class SwagMigrationConnectionDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'swag_migration_connection';
    }

    public function getCollectionClass(): string
    {
        return SwagMigrationConnectionCollection::class;
    }

    public function getEntityClass(): string
    {
        return SwagMigrationConnectionEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new PrimaryKey(), new Required()),
            (new StringField('name', 'name'))->setFlags(new Required()),
            (new JsonField('credential_fields', 'credentialFields'))->setFlags(new WriteProtected(MigrationContext::SOURCE_CONTEXT)),
            new JsonField('premapping', 'premapping'),
            (new StringField('profile_name', 'profileName'))->setFlags(new Required()),
            (new StringField('gateway_name', 'gatewayName'))->setFlags(new Required()),
            new CreatedAtField(),
            new UpdatedAtField(),
            new OneToManyAssociationField('runs', SwagMigrationRunDefinition::class, 'connection_id'),
            new OneToManyAssociationField('mappings', SwagMigrationMappingDefinition::class, 'connection_id'),
            new OneToManyAssociationField('settings', GeneralSettingDefinition::class, 'selected_connection_id'),
        ]);
    }
}
