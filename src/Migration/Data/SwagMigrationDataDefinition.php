<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Data;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunDefinition;

#[Package('services-settings')]
class SwagMigrationDataDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'swag_migration_data';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return SwagMigrationDataCollection::class;
    }

    public function getEntityClass(): string
    {
        return SwagMigrationDataEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new FkField('run_id', 'runId', SwagMigrationRunDefinition::class))->addFlags(new Required()),
            (new IntField('auto_increment', 'autoIncrement'))->addFlags(new WriteProtected()),
            (new StringField('entity', 'entity'))->addFlags(new Required()),
            (new JsonField('raw', 'raw'))->addFlags(new Required()),
            new JsonField('converted', 'converted'),
            new JsonField('unmapped', 'unmapped'),
            new IdField('mapping_uuid', 'mappingUuid'),
            new BoolField('written', 'written'),
            new BoolField('convert_failure', 'convertFailure'),
            new BoolField('write_failure', 'writeFailure'),
            new CreatedAtField(),
            new UpdatedAtField(),
            new ManyToOneAssociationField('run', 'run_id', SwagMigrationRunDefinition::class, 'id', true),
        ]);
    }
}
