<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Mock\Migration\Mapping;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Uuid;
use SwagMigrationNext\Profile\Shopware55\Mapping\Shopware55MappingService;

class DummyMappingService extends Shopware55MappingService
{
    public const DEFAULT_LANGUAGE_UUID = '20080911ffff4fffafffffff19830531';
    public const DEFAULT_LOCAL_UUID = '20080911ffff4fffafffffff19830532';

    public function __construct()
    {
    }

    public function readExistingMappings(Context $context): void
    {
    }

    public function createNewUuid(
        string $profileId,
        string $entityName,
        string $oldId,
        Context $context,
        array $additionalData = null,
        string $newUuid = null
    ): string {
        $uuid = $this->getUuid($profileId, $entityName, $oldId, $context);
        if ($uuid !== null) {
            return $uuid;
        }

        $uuid = Uuid::uuid4()->getHex();
        if ($newUuid !== null) {
            $uuid = $newUuid;
        }

        $this->uuids[$profileId][$entityName][$oldId] = $uuid;

        return $uuid;
    }

    public function saveMapping(array $mapping): void
    {
    }

    public function setProfile(string $profileName): void
    {
    }

    public function getUuid(string $profileId, string $entityName, string $oldId, Context $context): ?string
    {
        if (isset($this->uuids[$profileId][$entityName][$oldId])) {
            return $this->uuids[$profileId][$entityName][$oldId];
        }

        return null;
    }

    public function deleteMapping(string $entityUuid, string $profileId, Context $context): void
    {
        foreach ($this->writeArray as $writeMapping) {
            if ($writeMapping['profile'] === $profileId && $writeMapping['entityUuid'] === $entityUuid) {
                unset($writeMapping);
                break;
            }
        }
    }

    public function getLanguageUuid(string $profileId, string $localeCode, Context $context): array
    {
        return [
            'uuid' => self::DEFAULT_LANGUAGE_UUID,
            'createData' => [
                'localeId' => self::DEFAULT_LOCAL_UUID,
                'localeCode' => 'en_GB',
            ],
        ];
    }

    public function getPaymentUuid(string $technicalName, Context $context): ?string
    {
        return null;
    }

    public function getCountryUuid(string $oldId, string $iso, string $iso3, string $profileId, Context $context): ?string
    {
        return null;
    }

    public function getCurrencyUuid(string $oldShortName, Context $context): ?string
    {
        return null;
    }

    public function getOrderStateUuid(int $oldStateId, Context $context): ?string
    {
        if ($oldStateId === 100) {
            return null;
        }

        return Uuid::uuid4()->getHex();
    }

    public function getTransactionStateUuid(int $oldStateId, Context $context): ?string
    {
        return null;
    }

    public function getPrivateUuidArray(): array
    {
        return $this->uuids;
    }
}
