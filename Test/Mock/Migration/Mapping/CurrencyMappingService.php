<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Mock\Migration\Mapping;

use Shopware\Core\Framework\Context;

class CurrencyMappingService extends BasicSettingsMappingService
{
    public function getLanguageUuid(string $connectionId, string $localeCode, Context $context): ?string
    {
        return self::DEFAULT_LANGUAGE_UUID;
    }

    public function getCurrencyUuidWithoutMapping(string $connectionId, string $oldShortName, Context $context): ?string
    {
        return null;
    }
}
