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
use SwagMigrationNext\Migration\Asset\SwagMigrationMediaFileDefinition;
use SwagMigrationNext\Migration\Data\SwagMigrationDataDefinition;
use SwagMigrationNext\Migration\Profile\SwagMigrationProfileDefinition;

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
            (new FkField('profile_id', 'profileId', SwagMigrationProfileDefinition::class))->setFlags(new Required()),
            new JsonField('totals', 'totals'),
            new JsonField('additional_data', 'additionalData'),
            new StringField('user_id', 'userId'),
            new StringField('access_token', 'accessToken'),
            new StringField('status', 'status'),
            new CreatedAtField(),
            new UpdatedAtField(),
            new ManyToOneAssociationField('profile', 'profile_id', SwagMigrationProfileDefinition::class, false),
            new OneToManyAssociationField('data', SwagMigrationDataDefinition::class, 'run_id', false),
            new OneToManyAssociationField('mediaFiles', SwagMigrationMediaFileDefinition::class, 'run_id', false),
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
