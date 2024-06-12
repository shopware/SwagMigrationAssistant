<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Run;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionDefinition;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataDefinition;
use SwagMigrationAssistant\Migration\Logging\SwagMigrationLoggingDefinition;
use SwagMigrationAssistant\Migration\Media\SwagMigrationMediaFileDefinition;

#[Package('services-settings')]
class SwagMigrationRunDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'swag_migration_run';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return SwagMigrationRunCollection::class;
    }

    public function getEntityClass(): string
    {
        return SwagMigrationRunEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            new FkField('connection_id', 'connectionId', SwagMigrationConnectionDefinition::class),
            new JsonField('environment_information', 'environmentInformation'),
            new MigrationProgressField('progress', 'progress'),
            (new StringField('step', 'step'))->addFlags(new Required()),
            new CreatedAtField(),
            new UpdatedAtField(),
            new ManyToOneAssociationField('connection', 'connection_id', SwagMigrationConnectionDefinition::class, 'id', true),
            new OneToManyAssociationField('data', SwagMigrationDataDefinition::class, 'run_id'),
            new OneToManyAssociationField('mediaFiles', SwagMigrationMediaFileDefinition::class, 'run_id'),
            new OneToManyAssociationField('logs', SwagMigrationLoggingDefinition::class, 'run_id'),
        ]);
    }
}
