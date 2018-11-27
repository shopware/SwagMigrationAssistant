<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Mock\Profile\Dummy;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use SwagMigrationNext\Profile\Shopware55\Converter\CustomerConverter;

class DummyInvalidCustomerConverter extends CustomerConverter
{
    public function supports(string $profileName, string $entityName): bool
    {
        return $entityName === CustomerDefinition::getEntityName() . 'Invalid';
    }
}
