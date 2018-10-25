<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Mapping;

use Shopware\Core\Framework\Context;

interface MappingServiceInterface
{
    public function getUuid(string $profile, string $entityName, string $oldId, Context $context): ?string;

    public function createNewUuid(
        string $profile,
        string $entityName,
        string $oldId,
        Context $context,
        array $additionalData = null,
        string $newUuid = null
    ): string;

    public function getLanguageUuid(string $profile, string $localeCode, Context $context): array;

    public function getCountryUuid(string $oldId, string $iso, string $iso3, string $profile, Context $context): ?string;

    public function getCurrencyUuid(string $oldShortName, Context $context): ?string;

    public function deleteMapping(string $entityUuid, string $profile, Context $context): void;

    public function writeMapping(Context $context): void;
}
