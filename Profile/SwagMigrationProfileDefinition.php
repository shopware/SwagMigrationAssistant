<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile;

use Shopware\Core\Framework\ORM\EntityDefinition;
use Shopware\Core\Framework\ORM\Field\IdField;
use Shopware\Core\Framework\ORM\Field\JsonField;
use Shopware\Core\Framework\ORM\Field\StringField;
use Shopware\Core\Framework\ORM\Field\TenantIdField;
use Shopware\Core\Framework\ORM\FieldCollection;
use Shopware\Core\Framework\ORM\Write\Flag\PrimaryKey;
use Shopware\Core\Framework\ORM\Write\Flag\Required;

class SwagMigrationProfileDefinition extends EntityDefinition
{
    public static function getEntityName(): string
    {
        return 'swag_migration_profile';
    }

    protected static function defineFields(): FieldCollection
    {
        return new FieldCollection([
            new TenantIdField(),
            (new IdField('id', 'id'))->setFlags(new PrimaryKey(), new Required()),
            (new StringField('profile', 'profile'))->setFlags(new Required()),
            (new StringField('gateway', 'gateway'))->setFlags(new Required()),
            new JsonField('credential_fields', 'credentialFields'),
        ]);
    }
}
