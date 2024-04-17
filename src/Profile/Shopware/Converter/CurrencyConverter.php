<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('services-settings')]
abstract class CurrencyConverter extends ShopwareConverter
{
    protected string $mainLocale;

    protected Context $context;

    protected string $connectionId;

    public function getSourceIdentifier(array $data): string
    {
        return $data['currency'];
    }

    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        $this->generateChecksum($data);
        $this->context = $context;
        $this->mainLocale = $data['_locale'];

        $connection = $migrationContext->getConnection();
        $this->connectionId = '';
        if ($connection !== null) {
            $this->connectionId = $connection->getId();
        }

        $currencyUuid = $this->mappingService->getCurrencyUuid($this->connectionId, $data['currency'], $context);
        if ($currencyUuid !== null) {
            return new ConvertStruct(null, $data);
        }

        $converted = [];
        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::CURRENCY,
            $data['currency'],
            $context,
            $this->checksum
        );
        $converted['id'] = $this->mainMapping['entityUuid'];

        $converted['isDefault'] = false;
        unset($data['standard']);
        $this->getCurrencyTranslation($converted, $data);
        $converted['shortName'] = $data['currency'];
        $converted['isoCode'] = $data['currency'];
        unset($data['currency']);
        $this->convertValue($converted, 'name', $data, 'name');
        $this->convertValue($converted, 'factor', $data, 'factor', self::TYPE_FLOAT);
        $this->convertValue($converted, 'position', $data, 'position', self::TYPE_INTEGER);
        $this->convertValue($converted, 'symbol', $data, 'templatechar');
        $converted['placedInFront'] = ((int) $data['symbol_position']) > 16;

        $converted['itemRounding'] = [
            'decimals' => $context->getRounding()->getDecimals(),
            'interval' => 0.01,
            'roundForNet' => true,
        ];

        $converted['totalRounding'] = $converted['itemRounding'];

        unset(
            $data['id'],
            $data['symbol_position'],
            $data['_locale']
        );

        $returnData = $data;
        if (empty($returnData)) {
            $returnData = null;
        }
        $this->updateMainMapping($migrationContext, $context);

        return new ConvertStruct($converted, $returnData, $this->mainMapping['id'] ?? null);
    }

    protected function getCurrencyTranslation(array &$currency, array $data): void
    {
        $language = $this->mappingService->getDefaultLanguage($this->context);
        if ($language === null) {
            return;
        }

        $locale = $language->getLocale();
        if ($locale === null || $locale->getCode() === $this->mainLocale) {
            return;
        }

        $localeTranslation = [];

        $this->convertValue($localeTranslation, 'shortName', $data, 'currency');
        $this->convertValue($localeTranslation, 'name', $data, 'name');

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::CURRENCY_TRANSLATION,
            $data['id'] . ':' . $this->mainLocale,
            $this->context
        );
        $localeTranslation['id'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];
        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $this->mainLocale, $this->context);

        if ($languageUuid !== null) {
            $localeTranslation['languageId'] = $languageUuid;
            $currency['translations'][$languageUuid] = $localeTranslation;
        }
    }
}
