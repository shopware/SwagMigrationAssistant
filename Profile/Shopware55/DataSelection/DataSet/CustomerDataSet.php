<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class CustomerDataSet extends Shopware55DataSet
{
    public static function getEntity(): string
    {
        return CustomerDefinition::getEntityName();
    }

    public function supports(string $profileName, string $entity): bool
    {
        return $profileName === Shopware55Profile::PROFILE_NAME && $entity === self::getEntity();
    }

    public function getApiRoute(): string
    {
        return 'SwagMigrationCustomers';
    }

    public function getExtraQueryParameters(): array
    {
        return [];
    }
}
