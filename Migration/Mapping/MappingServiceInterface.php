<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Mapping;

use Shopware\Core\Framework\Context;

interface MappingServiceInterface
{
    public function getUuid(string $connectionId, string $entityName, string $oldId, Context $context): ?string;

    public function createNewUuid(
        string $connectionId,
        string $entityName,
        string $oldId,
        Context $context,
        array $additionalData = null,
        string $newUuid = null
    ): string;

    public function getLanguageUuid(string $connectionId, string $localeCode, Context $context): array;

    public function getCountryUuid(string $oldId, string $iso, string $iso3, string $connectionId, Context $context): ?string;

    public function getCurrencyUuid(string $oldShortName, Context $context): ?string;

    public function deleteMapping(string $entityUuid, string $connectionId, Context $context): void;

    public function writeMapping(Context $context): void;

    public function createSalesChannelMapping(string $connectionId, array $structure, Context $context): void;

    public function getPaymentUuid(string $technicalName, Context $context): ?string;
}
