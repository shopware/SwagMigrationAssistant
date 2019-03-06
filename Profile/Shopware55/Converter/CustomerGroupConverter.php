<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Converter;

use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupDefinition;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroupDiscount\CustomerGroupDiscountDefinition;
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
        $locale = $data['_locale'];
        unset($data['_locale']);

        $converted['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            CustomerGroupDefinition::getEntityName(),
            $data['id'],
            $context
        );

        $translation['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            CustomerGroupTranslationDefinition::getEntityName(),
            $data['id'] . ':' . $locale,
            $context
        );
        unset($data['id'], $data['groupkey']);

        $translation['customerGroupId'] = $converted['id'];
        $this->helper->convertValue($translation, 'name', $data, 'description');

        $this->helper->convertValue($converted, 'displayGross', $data, 'tax', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($converted, 'inputGross', $data, 'taxinput', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($converted, 'hasGlobalDiscount', $data, 'mode', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($converted, 'percentageGlobalDiscount', $data, 'discount', $this->helper::TYPE_FLOAT);
        $this->helper->convertValue($converted, 'minimumOrderAmount', $data, 'minimumorder', $this->helper::TYPE_FLOAT);
        $this->helper->convertValue($converted, 'minimumOrderAmountSurcharge', $data, 'minimumordersurcharge', $this->helper::TYPE_FLOAT);

        if (isset($data['discounts'])) {
            $converted['discounts'] = $this->getCustomerGroupDiscount($data['discounts'], $converted['id']);
            unset($data['discounts']);
        }
        $languageData = $this->mappingService->getLanguageUuid($this->connectionId, $locale, $context);

        if (isset($languageData['createData']) && !empty($languageData['createData'])) {
            $translation['language']['id'] = $languageData['uuid'];
            $translation['language']['localeId'] = $languageData['createData']['localeId'];
            $translation['language']['name'] = $languageData['createData']['localeCode'];
        } else {
            $translation['languageId'] = $languageData['uuid'];
        }

        $converted['translations'][$languageData['uuid']] = $translation;

        if (empty($data)) {
            $data = null;
        }

        return new ConvertStruct($converted, $data);
    }

    public function writeMapping(Context $context): void
    {
        $this->mappingService->writeMapping($context);
    }

    private function getCustomerGroupDiscount(array $oldDiscounts, $groupId): array
    {
        $discounts = [];
        foreach ($oldDiscounts as $oldDiscount) {
            $discount['id'] = $this->mappingService->createNewUuid(
                $this->connectionId,
                CustomerGroupDiscountDefinition::getEntityName(),
                (string) $oldDiscount['id'],
                $this->context
            );

            $discount['customerGroupId'] = $groupId;
            $this->helper->convertValue($discount, 'percentageDiscount', $oldDiscount, 'basketdiscount', $this->helper::TYPE_FLOAT);
            $this->helper->convertValue($discount, 'minimumCartAmount', $oldDiscount, 'basketdiscountstart', $this->helper::TYPE_FLOAT);

            $discounts[] = $discount;
        }

        return $discounts;
    }
}
