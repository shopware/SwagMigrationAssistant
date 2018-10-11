<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Mock\Migration\Mapping;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use SwagMigrationNext\Profile\Shopware55\Mapping\Shopware55MappingService;

class DummyMappingService extends Shopware55MappingService
{
    public function __construct()
    {
    }

    public function setProfile(string $profileName): void
    {
    }

    public function getUuid(string $profile, string $entityName, string $oldId, Context $context): ?string
    {
        if (isset($this->uuids[$profile][$entityName][$oldId])) {
            return $this->uuids[$profile][$entityName][$oldId];
        }

        return null;
    }

    public function deleteMapping(string $entityUuid, string $profile, Context $context): void
    {
        foreach ($this->writeArray as $writeMapping) {
            if ($writeMapping['profile'] === $profile && $writeMapping['entityUuid'] === $entityUuid) {
                unset($writeMapping);
                break;
            }
        }
    }

    public function getLanguageUuid(string $profile, string $localeCode, Context $context): array
    {
        return ['uuid' => Defaults::LANGUAGE];
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
