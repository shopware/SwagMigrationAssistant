<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Mapping\SwagMigrationMappingDefinition;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\Run\PremappingField;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunDefinition;
use SwagMigrationAssistant\Migration\Setting\GeneralSettingDefinition;

#[Package('services-settings')]
class SwagMigrationConnectionDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'swag_migration_connection';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
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
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new StringField('name', 'name'))->addFlags(new Required()),
            (new JsonField('credential_fields', 'credentialFields'))->addFlags(new WriteProtected(MigrationContext::SOURCE_CONTEXT)),
            new PremappingField('premapping', 'premapping'),
            (new StringField('profile_name', 'profileName'))->addFlags(new Required()),
            (new StringField('gateway_name', 'gatewayName'))->addFlags(new Required()),
            new CreatedAtField(),
            new UpdatedAtField(),
            new OneToManyAssociationField('runs', SwagMigrationRunDefinition::class, 'connection_id'),
            new OneToManyAssociationField('mappings', SwagMigrationMappingDefinition::class, 'connection_id'),
            new OneToManyAssociationField('settings', GeneralSettingDefinition::class, 'selected_connection_id'),
        ]);
    }
}
