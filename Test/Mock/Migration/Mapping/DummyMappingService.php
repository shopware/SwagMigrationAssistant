<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Mock\Migration\Mapping;

use Shopware\Core\Framework\Context;
use SwagMigrationNext\Profile\Shopware55\Mapping\Shopware55MappingService;

class DummyMappingService extends Shopware55MappingService
{
    public const DEFAULT_LANGUAGE_UUID = '20080911ffff4fffafffffff19830531';

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
        return ['uuid' => self::DEFAULT_LANGUAGE_UUID];
    }

    public function getPaymentUuid(string $technicalName, Context $context): ?string
    {
        return null;
    }

    public function getCountryUuid(string $oldId, string $iso, string $iso3, string $profile, Context $context): ?string
    {
        return null;
    }

    public function getCurrencyUuid(string $oldShortName, Context $context): ?string
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
