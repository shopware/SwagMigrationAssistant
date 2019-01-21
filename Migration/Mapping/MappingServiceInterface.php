<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Mapping;

use Shopware\Core\Framework\Context;

interface MappingServiceInterface
{
    public function getUuid(string $profileId, string $entityName, string $oldId, Context $context): ?string;

    public function createNewUuid(
        string $profileId,
        string $entityName,
        string $oldId,
        Context $context,
        array $additionalData = null,
        string $newUuid = null
    ): string;

    public function getLanguageUuid(string $profileId, string $localeCode, Context $context): array;

    public function getCountryUuid(string $oldId, string $iso, string $iso3, string $profileId, Context $context): ?string;

    public function getCurrencyUuid(string $oldShortName, Context $context): ?string;

    public function deleteMapping(string $entityUuid, string $profileId, Context $context): void;

    public function writeMapping(Context $context): void;

    public function createSalesChannelMapping(string $profileId, array $structure, Context $context): void;
}
