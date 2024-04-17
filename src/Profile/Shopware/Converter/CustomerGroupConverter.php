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
abstract class CustomerGroupConverter extends ShopwareConverter
{
    protected string $connectionId;

    protected Context $context;

    protected string $locale;

    protected string $connectionName;

    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        $this->generateChecksum($data);
        $this->context = $context;
        $this->locale = $data['_locale'];
        $this->migrationContext = $migrationContext;
        unset($data['_locale']);

        $connection = $migrationContext->getConnection();
        $this->connectionId = '';
        $this->connectionName = '';
        if ($connection !== null) {
            $this->connectionId = $connection->getId();
            $this->connectionName = $connection->getName();
        }

        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::CUSTOMER_GROUP,
            $data['id'],
            $context,
            $this->checksum
        );

        $converted = [];
        $converted['id'] = $this->mainMapping['entityUuid'];

        if (isset($data['attributes'])) {
            $converted['customFields'] = $this->getAttributes($data['attributes'], DefaultEntities::CUSTOMER_GROUP, $this->connectionName, ['id', 'customerGroupID'], $this->context);
        }

        $this->getCustomerGroupTranslation($converted, $data);
        $this->convertValue($converted, 'displayGross', $data, 'tax', self::TYPE_BOOLEAN);
        $this->convertValue($converted, 'inputGross', $data, 'taxinput', self::TYPE_BOOLEAN);
        $this->convertValue($converted, 'hasGlobalDiscount', $data, 'mode', self::TYPE_BOOLEAN);
        $this->convertValue($converted, 'percentageGlobalDiscount', $data, 'discount', self::TYPE_FLOAT);
        $this->convertValue($converted, 'minimumOrderAmount', $data, 'minimumorder', self::TYPE_FLOAT);
        $this->convertValue($converted, 'minimumOrderAmountSurcharge', $data, 'minimumordersurcharge', self::TYPE_FLOAT);
        $this->convertValue($converted, 'name', $data, 'description');

        unset($data['id'], $data['groupkey'], $data['discounts']);

        $returnData = $data;
        if (empty($returnData)) {
            $returnData = null;
        }
        $this->updateMainMapping($migrationContext, $context);

        return new ConvertStruct($converted, $returnData, $this->mainMapping['id'] ?? null);
    }

    public function getCustomerGroupTranslation(array &$customerGroup, array $data): void
    {
        $language = $this->mappingService->getDefaultLanguage($this->context);
        if ($language === null) {
            return;
        }

        $locale = $language->getLocale();
        if ($locale === null || $locale->getCode() === $this->locale) {
            return;
        }

        $localeTranslation = [];
        $localeTranslation['customerGroupId'] = $customerGroup['id'];

        $this->convertValue($localeTranslation, 'name', $data, 'description');

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::CUSTOMER_GROUP_TRANSLATION,
            $data['id'] . ':' . $this->locale,
            $this->context
        );
        $localeTranslation['id'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];

        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $this->locale, $this->context);
        $localeTranslation['languageId'] = $languageUuid;

        if (isset($customerGroup['customFields'])) {
            $localeTranslation['customFields'] = $customerGroup['customFields'];
        }

        if ($languageUuid !== null) {
            $customerGroup['translations'][$languageUuid] = $localeTranslation;
        }
    }
}
