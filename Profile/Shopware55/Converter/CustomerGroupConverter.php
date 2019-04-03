<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Converter;

use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupDefinition;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroupTranslation\CustomerGroupTranslationDefinition;
use Shopware\Core\Framework\Context;
use SwagMigrationNext\Migration\Converter\AbstractConverter;
use SwagMigrationNext\Migration\Converter\ConvertStruct;
use SwagMigrationNext\Migration\Mapping\MappingServiceInterface;
use SwagMigrationNext\Migration\MigrationContextInterface;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class CustomerGroupConverter extends AbstractConverter
{
    /**
     * @var MappingServiceInterface
     */
    private $mappingService;

    /**
     * @var ConverterHelperService
     */
    private $helper;

    /**
     * @var string
     */
    private $connectionId;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var string
     */
    private $locale;

    public function __construct(MappingServiceInterface $mappingService, ConverterHelperService $converterHelperService)
    {
        $this->mappingService = $mappingService;
        $this->helper = $converterHelperService;
    }

    public function getSupportedEntityName(): string
    {
        return CustomerGroupDefinition::getEntityName();
    }

    public function getSupportedProfileName(): string
    {
        return Shopware55Profile::PROFILE_NAME;
    }

    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        $this->connectionId = $migrationContext->getConnection()->getId();
        $this->context = $context;
        $this->locale = $data['_locale'];
        unset($data['_locale']);

        $converted['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            CustomerGroupDefinition::getEntityName(),
            $data['id'],
            $context
        );

        $this->getCustomerGroupTranslation($converted, $data);
        $this->helper->convertValue($converted, 'displayGross', $data, 'tax', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($converted, 'inputGross', $data, 'taxinput', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($converted, 'hasGlobalDiscount', $data, 'mode', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($converted, 'percentageGlobalDiscount', $data, 'discount', $this->helper::TYPE_FLOAT);
        $this->helper->convertValue($converted, 'minimumOrderAmount', $data, 'minimumorder', $this->helper::TYPE_FLOAT);
        $this->helper->convertValue($converted, 'minimumOrderAmountSurcharge', $data, 'minimumordersurcharge', $this->helper::TYPE_FLOAT);
        $this->helper->convertValue($converted, 'name', $data, 'description');

        unset($data['id'], $data['groupkey']);
        if (empty($data)) {
            $data = null;
        }

        return new ConvertStruct($converted, $data);
    }

    public function getCustomerGroupTranslation(array &$customerGroup, array $data): void
    {
        $languageData = $this->mappingService->getDefaultLanguageUuid($this->context);
        if ($languageData['createData']['localeCode'] === $this->locale) {
            return;
        }

        $localeTranslation = [];
        $localeTranslation['customerGroupId'] = $customerGroup['id'];

        $this->helper->convertValue($localeTranslation, 'name', $data, 'description');

        $localeTranslation['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            CustomerGroupTranslationDefinition::getEntityName(),
            $data['id'] . ':' . $this->locale,
            $this->context
        );

        $languageData = $this->mappingService->getLanguageUuid($this->connectionId, $this->locale, $this->context);
        if (isset($languageData['createData']) && !empty($languageData['createData'])) {
            $localeTranslation['language']['id'] = $languageData['uuid'];
            $localeTranslation['language']['localeId'] = $languageData['createData']['localeId'];
            $localeTranslation['language']['name'] = $languageData['createData']['localeCode'];
        } else {
            $localeTranslation['languageId'] = $languageData['uuid'];
        }

        $customerGroup['translations'][$languageData['uuid']] = $localeTranslation;
    }

    public function writeMapping(Context $context): void
    {
        $this->mappingService->writeMapping($context);
    }
}
