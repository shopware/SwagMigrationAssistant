<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Mock\Migration\Mapping;

use Shopware\Core\Framework\Context;
use SwagMigrationNext\Migration\Mapping\MappingServiceInterface;

class DummyMappingService implements MappingServiceInterface
{
    public function readExistingMappings(Context $context): void
    {
    }

    public function createNewUuid(
        string $profile,
        string $entityName,
        string $oldId,
        Context $context,
        array $additionalData = null
    ): string {
        return '';
    }

    public function writeMapping(array $writeMapping, Context $context): void
    {
    }

    public function setProfile(string $profileName): void
    {
    }

    public function getUuid(string $profile, string $entityName, string $oldId, Context $context): ?string
    {
        return null;
    }

    public function getLanguageUuid(string $profile, string $localeCode, Context $context): array
    {
        return [];
    }

    public function getPaymentUuid(string $technicalName, Context $context): ?string
    {
        return null;
    }

    public function getCountryUuid(string $oldId, string $iso, string $iso3, string $profile, Context $context): ?string
    {
        return null;
    }

    public function getOrderStateUuid(int $oldStateId, Context $context): ?string
    {
        return null;
    }

    public function getTransactionStateUuid(int $oldStateId, Context $context): ?string
    {
        return null;
    }
}
