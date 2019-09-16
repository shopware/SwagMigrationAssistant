<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

abstract class CustomerGroupConverter extends ShopwareConverter
{
    /**
     * @var MappingServiceInterface
     */
    protected $mappingService;

    /**
     * @var string
     */
    protected $connectionId;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var string
     */
    protected $locale;

    public function __construct(MappingServiceInterface $mappingService)
    {
        $this->mappingService = $mappingService;
    }

    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        $this->connectionId = $migrationContext->getConnection()->getId();
        $this->context = $context;
        $this->locale = $data['_locale'];
        unset($data['_locale']);

        $converted['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::CUSTOMER_GROUP,
            $data['id'],
            $context
        );

        if (isset($data['attributes'])) {
            $converted['customFields'] = $this->getAttributes($data['attributes'], DefaultEntities::CUSTOMER_GROUP, $migrationContext->getConnection()->getName(), ['id', 'customerGroupID']);
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
        if (empty($data)) {
            $data = null;
        }

        return new ConvertStruct($converted, $data);
    }

    public function getCustomerGroupTranslation(array &$customerGroup, array $data): void
    {
        $language = $this->mappingService->getDefaultLanguage($this->context);
        if ($language->getLocale()->getCode() === $this->locale) {
            return;
        }

        $localeTranslation = [];
        $localeTranslation['customerGroupId'] = $customerGroup['id'];

        $this->convertValue($localeTranslation, 'name', $data, 'description');

        $localeTranslation['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::CUSTOMER_GROUP_TRANSLATION,
            $data['id'] . ':' . $this->locale,
            $this->context
        );

        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $this->locale, $this->context);
        $localeTranslation['languageId'] = $languageUuid;

        if (isset($customerGroup['customFields'])) {
            $localeTranslation['customFields'] = $customerGroup['customFields'];
        }

        $customerGroup['translations'][$languageUuid] = $localeTranslation;
    }

    public function writeMapping(Context $context): void
    {
        $this->mappingService->writeMapping($context);
    }
}
