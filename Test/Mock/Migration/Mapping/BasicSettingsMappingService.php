<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Mock\Migration\Mapping;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;

class BasicSettingsMappingService extends DummyMappingService
{
    public function getLanguageUuid(string $connectionId, string $localeCode, Context $context, bool $withoutMapping = false): ?string
    {
        return null;
    }

    public function getCurrencyUuid(string $connectionId, string $oldIsoCode, Context $context): ?string
    {
        return null;
    }

    public function getLocaleUuid(string $connectionId, string $localeCode, Context $context): string
    {
        return Uuid::randomHex();
    }
}
