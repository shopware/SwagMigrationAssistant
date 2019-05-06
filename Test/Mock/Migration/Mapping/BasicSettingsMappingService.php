<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Mock\Migration\Mapping;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;

class BasicSettingsMappingService extends DummyMappingService
{
    public function getLanguageUuid(string $connectionId, string $localeCode, Context $context): ?string
    {
        return null;
    }

    public function getCurrencyUuid(string $connectionId, string $oldShortName, Context $context): ?string
    {
        return null;
    }

    public function getLocaleUuid(string $connectionId, string $localeCode, Context $context): string
    {
        return Uuid::randomHex();
    }
}
