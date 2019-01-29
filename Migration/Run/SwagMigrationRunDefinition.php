<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Run;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Flag\Required;
use SwagMigrationNext\Migration\Connection\SwagMigrationConnectionDefinition;
use SwagMigrationNext\Migration\Data\SwagMigrationDataDefinition;
use SwagMigrationNext\Migration\Logging\SwagMigrationLoggingDefinition;
use SwagMigrationNext\Migration\Media\SwagMigrationMediaFileDefinition;

class SwagMigrationRunDefinition extends EntityDefinition
{
    public static function getEntityName(): string
    {
        return 'swag_migration_run';
    }

    public static function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new PrimaryKey(), new Required()),
            new FkField('connection_id', 'connectionId', SwagMigrationConnectionDefinition::class),
            new JsonField('environment_information', 'environmentInformation'),
            new JsonField('progress', 'progress'),
            new StringField('user_id', 'userId'),
            new StringField('access_token', 'accessToken'),
            (new StringField('status', 'status'))->setFlags(new Required()),
            new CreatedAtField(),
            new UpdatedAtField(),
            new ManyToOneAssociationField('connection', 'connection_id', SwagMigrationConnectionDefinition::class, true),
            new OneToManyAssociationField('data', SwagMigrationDataDefinition::class, 'run_id', false),
            new OneToManyAssociationField('mediaFiles', SwagMigrationMediaFileDefinition::class, 'run_id', false),
            new OneToManyAssociationField('logs', SwagMigrationLoggingDefinition::class, 'run_id', false),
        ]);
    }

    public static function getCollectionClass(): string
    {
        return SwagMigrationRunCollection::class;
    }

    public static function getEntityClass(): string
    {
        return SwagMigrationRunEntity::class;
    }
}
