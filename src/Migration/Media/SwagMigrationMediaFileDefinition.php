<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Media;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunDefinition;

#[Package('services-settings')]
class SwagMigrationMediaFileDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'swag_migration_media_file';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new FkField('run_id', 'runId', SwagMigrationRunDefinition::class))->addFlags(new Required()),
            (new StringField('entity', 'entity'))->addFlags(new Required()),
            (new StringField('uri', 'uri'))->addFlags(new Required()),
            (new StringField('file_name', 'fileName'))->addFlags(new Required()),
            (new IntField('file_size', 'fileSize'))->addFlags(new Required()),
            (new IdField('media_id', 'mediaId'))->addFlags(new Required()),
            new BoolField('written', 'written'),
            new BoolField('processed', 'processed'),
            new BoolField('process_failure', 'processFailure'),
            new CreatedAtField(),
            new UpdatedAtField(),
            new ManyToOneAssociationField('run', 'run_id', SwagMigrationRunDefinition::class),
        ]);
    }

    public function getCollectionClass(): string
    {
        return SwagMigrationMediaFileCollection::class;
    }

    public function getEntityClass(): string
    {
        return SwagMigrationMediaFileEntity::class;
    }
}
