<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\ErrorResolution\Entity;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Core\Field\AnyJsonField;

#[Package('fundamentals@after-sales')]
class SwagMigrationFixDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'swag_migration_fix';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return SwagMigrationFixCollection::class;
    }

    public function getEntityClass(): string
    {
        return SwagMigrationFixEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new IdField('connection_id', 'connectionId'))->addFlags(new Required()),
            (new AnyJsonField('value', 'value'))->addFlags(new Required()),
            (new StringField('path', 'path'))->addFlags(new Required()),
            new IdField('entity_id', 'entityId'),
            new StringField('entity_name', 'entityName'),
            new CreatedAtField(),
            new UpdatedAtField(),
        ]);
    }
}
