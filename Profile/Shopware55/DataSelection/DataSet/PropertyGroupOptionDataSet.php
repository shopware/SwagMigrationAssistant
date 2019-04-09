<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet;

use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionDefinition;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class PropertyGroupOptionDataSet extends Shopware55DataSet
{
    public static function getEntity(): string
    {
        return PropertyGroupOptionDefinition::getEntityName();
    }

    public function supports(string $profileName, string $entity): bool
    {
        return $profileName === Shopware55Profile::PROFILE_NAME && $entity === self::getEntity();
    }

    public function getApiRoute(): string
    {
        return 'SwagMigrationConfiguratorOptions';
    }

    public function getExtraQueryParameters(): array
    {
        return [];
    }
}
