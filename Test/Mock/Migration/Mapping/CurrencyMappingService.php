<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Mock\Migration\Mapping;

use Shopware\Core\Framework\Context;

class CurrencyMappingService extends BasicSettingsMappingService
{
    public function getLanguageUuid(string $connectionId, string $localeCode, Context $context, bool $withoutMapping = false): ?string
    {
        return self::DEFAULT_LANGUAGE_UUID;
    }

    public function getCurrencyUuidWithoutMapping(string $connectionId, string $oldIsoCode, Context $context): ?string
    {
        return null;
    }
}
