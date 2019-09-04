<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Rule\Container\AndRule;
use Shopware\Core\Framework\Rule\Container\OrRule;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\EmptyNecessaryFieldRunLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Logging\Log\UnsupportedShippingCalculationType;
use SwagMigrationAssistant\Profile\Shopware\Logging\Log\UnsupportedShippingPriceLog;

abstract class ShippingMethodConverter extends ShopwareConverter
{
    protected const CALCULATION_TYPE_MAPPING = [
        0 => 3, // Weight
        1 => 2, // Price
        2 => 1, // Quantity
    ];

    /**
     * @var MappingServiceInterface
     */
    protected $mappingService;

    /**
     * @var LoggingServiceInterface
     */
    protected $loggingService;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var string
     */
    protected $runId;

    /**
     * @var string
     */
    protected $connectionId;

    /**
     * @var string
     */
    protected $oldShippingMethod;

    /**
     * @var string
     */
    protected $mainLocale;

    /**
     * @var string[]
     */
    protected $requiredDataFields = [
        'deliveryTimeId' => 'delivery_time',
        'availabilityRuleId' => 'availability_rule_id',
    ];

    public function __construct(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService
    ) {
        $this->mappingService = $mappingService;
        $this->loggingService = $loggingService;
    }

    public function writeMapping(Context $context): void
    {
        $this->mappingService->writeMapping($context);
    }

    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        $this->context = $context;
        $this->runId = $migrationContext->getRunUuid();
        $this->connectionId = $migrationContext->getConnection()->getId();
        $this->oldShippingMethod = $data['id'];
        $this->mainLocale = $data['_locale'];

        if (!isset($data['calculation'])
            || !array_key_exists($data['calculation'], self::CALCULATION_TYPE_MAPPING)
        ) {
            $this->loggingService->addLogEntry(new UnsupportedShippingCalculationType(
                $this->runId,
                DefaultEntities::SHIPPING_METHOD,
                $this->oldShippingMethod,
                $data['calculation']
            ));

            return new ConvertStruct(null, $data);
        }

        $converted = [];
        $converted['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::SHIPPING_METHOD,
            $data['id'],
            $this->context
        );

        $defaultDeliveryTimeUuid = $this->mappingService->getUuid(
            $this->connectionId,
            DefaultEntities::DELIVERY_TIME,
            'default_delivery_time',
            $this->context
        );

        if ($defaultDeliveryTimeUuid !== null) {
            $converted['deliveryTimeId'] = $defaultDeliveryTimeUuid;
        }

        $defaultAvailabilityRuleUuid = $this->mappingService->getDefaultAvailabilityRule($this->context);
        if ($defaultAvailabilityRuleUuid !== null) {
            $converted['availabilityRuleId'] = $defaultAvailabilityRuleUuid;
        }

        $fields = $this->checkForEmptyRequiredConvertedFields($converted, $this->requiredDataFields);
        if (!empty($fields)) {
            foreach ($fields as $field) {
                $this->loggingService->addLogEntry(new EmptyNecessaryFieldRunLog(
                    $this->runId,
                    DefaultEntities::SHIPPING_METHOD,
                    $this->oldShippingMethod,
                    $field
                ));
            }

            return new ConvertStruct(null, $data);
        }

        $this->getShippingMethodTranslation($converted, $data);
        $this->convertValue($converted, 'bindShippingfree', $data, 'bind_shippingfree', self::TYPE_BOOLEAN);
        $this->convertValue($converted, 'active', $data, 'active', self::TYPE_BOOLEAN);
        $this->convertValue($converted, 'shippingFree', $data, 'shippingfree', self::TYPE_FLOAT);
        $this->convertValue($converted, 'name', $data, 'name');
        $this->convertValue($converted, 'description', $data, 'description');
        $this->convertValue($converted, 'comment', $data, 'comment');

        $priceRule = null;
        if (isset($data['multishopID']) && !isset($data['customergroupID'])) {
            $priceRule = $this->getSalesChannelCalculationRule($data);
        }

        if (isset($data['customergroupID']) && !isset($data['multishopID'])) {
            $priceRule = $this->getCustomerGroupCalculationRule($data);
        }

        if (isset($data['multishopID'], $data['customergroupID'])) {
            $priceRule = $this->getSalesChannelAndCustomerGroupCalculationRule($data);
        }

        if (isset($data['shippingCosts'])) {
            $calculationType = self::CALCULATION_TYPE_MAPPING[$data['calculation']];
            $converted['prices'] = $this->getShippingCosts($data['shippingCosts'], $calculationType, $priceRule);
        }

        unset(
            // Used
            $data['id'],
            $data['type'],
            $data['position'],
            $data['calculation'],
            $data['multishopID'],
            $data['customergroupID'],
            $data['shop'],
            $data['customerGroup'],
            $data['shippingCosts'],
            $data['_locale'],

            // Unused
            $data['surcharge_calculation'],
            $data['tax_calculation'],
            $data['bind_time_from'],
            $data['bind_time_to'],
            $data['bind_instock'],
            $data['bind_laststock'],
            $data['bind_weekday_from'],
            $data['bind_weekday_to'],
            $data['bind_weight_from'],
            $data['bind_weight_to'],
            $data['bind_price_from'],
            $data['bind_price_to'],
            $data['bind_sql'],
            $data['status_link'],
            $data['calculation_sql']
        );

        if (empty($data)) {
            $data = null;
        }

        return new ConvertStruct($converted, $data);
    }

    protected function getShippingMethodTranslation(array &$shippingMethod, array $data): void
    {
        $language = $this->mappingService->getDefaultLanguage($this->context);
        if ($language->getLocale()->getCode() === $this->mainLocale) {
            return;
        }

        $localeTranslation = [];
        $localeTranslation['shippingMethodId'] = $shippingMethod['id'];

        $this->convertValue($localeTranslation, 'name', $data, 'name');
        $this->convertValue($localeTranslation, 'description', $data, 'description');
        $this->convertValue($localeTranslation, 'comment', $data, 'comment');

        $localeTranslation['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::SHIPPING_METHOD_TRANSLATION,
            $data['id'] . ':' . $this->mainLocale,
            $this->context
        );

        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $this->mainLocale, $this->context);
        $localeTranslation['languageId'] = $languageUuid;

        $shippingMethod['translations'][$languageUuid] = $localeTranslation;
    }

    protected function getCustomerGroupCalculationRule(array $data): array
    {
        $customerGroupId = $data['customergroupID'];
        $customerGroupUuid = $this->mappingService->getUuid(
            $this->connectionId,
            DefaultEntities::CUSTOMER_GROUP,
            $customerGroupId,
            $this->context
        );

        if (!isset($customerGroupUuid)) {
            return [];
        }

        $customerGroupName = $customerGroupId;
        if (isset($data['customerGroup']['description'])) {
            $customerGroupName = $data['customerGroup']['description'];
        }

        $priceRuleUuid = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::RULE,
            'customerGroupRule_' . $customerGroupId,
            $this->context
        );

        $orContainerUuid = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::RULE,
            'customerGroupRule_orContainer_' . $customerGroupId,
            $this->context
        );

        $andContainerUuid = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::RULE,
            'customerGroupRule_andContainer_' . $customerGroupId,
            $this->context
        );

        $conditionUuid = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::RULE,
            'customerGroupRule_condition_' . $customerGroupId,
            $this->context
        );

        $rule = [
            'id' => $priceRuleUuid,
            'name' => 'Customer Group: ' . $customerGroupName,
            'priority' => 0,
            'moduleTypes' => [
                'types' => [
                    'price',
                ],
            ],
            'conditions' => [
                [
                    'id' => $orContainerUuid,
                    'type' => (new OrRule())->getName(),
                ],

                [
                    'id' => $andContainerUuid,
                    'type' => (new AndRule())->getName(),
                    'parentId' => $orContainerUuid,
                ],

                [
                    'id' => $conditionUuid,
                    'type' => 'customerCustomerGroup',
                    'parentId' => $andContainerUuid,
                    'position' => 1,
                    'value' => [
                        'customerGroupIds' => [
                            $customerGroupUuid,
                        ],
                        'operator' => '=',
                    ],
                ],
            ],
        ];

        return $rule;
    }

    protected function getSalesChannelCalculationRule(array $data): array
    {
        $shopId = $data['multishopID'];
        $salesChannelUuid = $this->mappingService->getUuid(
            $this->connectionId,
            DefaultEntities::SALES_CHANNEL,
            $shopId,
            $this->context
        );

        if (!isset($salesChannelUuid)) {
            return [];
        }

        $salesChannelName = $shopId;
        if (isset($data['shop']['name'])) {
            $salesChannelName = $data['shop']['name'];
        }

        $priceRuleUuid = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::RULE,
            'salesChannelRule_' . $shopId,
            $this->context
        );

        $orContainerUuid = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::RULE,
            'salesChannelRule_orContainer_' . $shopId,
            $this->context
        );

        $andContainerUuid = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::RULE,
            'salesChannelRule_andContainer_' . $shopId,
            $this->context
        );

        $conditionUuid = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::RULE,
            'salesChannelRule_condition_' . $shopId,
            $this->context
        );

        $rule = [
            'id' => $priceRuleUuid,
            'name' => 'Sales channel: ' . $salesChannelName,
            'priority' => 0,
            'moduleTypes' => [
                'types' => [
                    'price',
                ],
            ],
            'conditions' => [
                [
                    'id' => $orContainerUuid,
                    'type' => (new OrRule())->getName(),
                ],

                [
                    'id' => $andContainerUuid,
                    'type' => (new AndRule())->getName(),
                    'parentId' => $orContainerUuid,
                ],

                [
                    'id' => $conditionUuid,
                    'type' => 'salesChannel',
                    'parentId' => $andContainerUuid,
                    'position' => 1,
                    'value' => [
                        'salesChannelIds' => [
                            $salesChannelUuid,
                        ],
                        'operator' => '=',
                    ],
                ],
            ],
        ];

        return $rule;
    }

    protected function getSalesChannelAndCustomerGroupCalculationRule(array $data): array
    {
        $shopId = $data['multishopID'];
        $salesChannelUuid = $this->mappingService->getUuid(
            $this->connectionId,
            DefaultEntities::SALES_CHANNEL,
            $shopId,
            $this->context
        );

        $customerGroupId = $data['customergroupID'];
        $customerGroupUuid = $this->mappingService->getUuid(
            $this->connectionId,
            DefaultEntities::CUSTOMER_GROUP,
            $customerGroupId,
            $this->context
        );

        if (!isset($salesChannelUuid, $customerGroupUuid)) {
            return [];
        }

        $customerGroupName = $customerGroupId;
        if (isset($data['customerGroup']['description'])) {
            $customerGroupName = $data['customerGroup']['description'];
        }

        $salesChannelName = $shopId;
        if (isset($data['shop']['name'])) {
            $salesChannelName = $data['shop']['name'];
        }

        $priceRuleUuid = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::RULE,
            'salesChannelAndCustomerGroupRule_' . $shopId . '_' . $customerGroupId,
            $this->context
        );

        $orContainerUuid = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::RULE,
            'salesChannelAndCustomerGroupRule_orContainer_' . $shopId . '_' . $customerGroupId,
            $this->context
        );

        $andContainerUuid = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::RULE,
            'salesChannelAndCustomerGroupRule_andContainer_' . $shopId . '_' . $customerGroupId,
            $this->context
        );

        $conditionUuid = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::RULE,
            'salesChannelAndCustomerGroupRule_condition_' . $shopId . '_' . $customerGroupId,
            $this->context
        );

        $rule = [
            'id' => $priceRuleUuid,
            'name' => 'Sales channel: ' . $salesChannelName . ', Customer group: ' . $customerGroupName,
            'priority' => 0,
            'moduleTypes' => [
                'types' => [
                    'price',
                ],
            ],
            'conditions' => [
                [
                    'id' => $orContainerUuid,
                    'type' => (new OrRule())->getName(),
                ],

                [
                    'id' => $andContainerUuid,
                    'type' => (new AndRule())->getName(),
                    'parentId' => $orContainerUuid,
                ],

                [
                    'id' => $conditionUuid,
                    'type' => 'salesChannel',
                    'parentId' => $andContainerUuid,
                    'position' => 1,
                    'value' => [
                        'salesChannelIds' => [
                            $salesChannelUuid,
                        ],
                        'operator' => '=',
                    ],
                ],

                [
                    'id' => $conditionUuid,
                    'type' => 'customerCustomerGroup',
                    'parentId' => $andContainerUuid,
                    'position' => 1,
                    'value' => [
                        'customerGroupIds' => [
                            $customerGroupUuid,
                        ],
                        'operator' => '=',
                    ],
                ],
            ],
        ];

        return $rule;
    }

    protected function getShippingCosts(array $shippingCosts, int $calculationType, ?array $rule): array
    {
        $convertedCosts = [];
        foreach ($shippingCosts as $key => $shippingCost) {
            $cost = [];

            $cost['id'] = $this->mappingService->createNewUuid(
                $this->connectionId,
                DefaultEntities::SHIPPING_METHOD_PRICE,
                $shippingCost['id'],
                $this->context
            );

            $cost['calculation'] = $calculationType;
            $cost['shippingMethodId'] = $this->oldShippingMethod;

            $currencyUuid = null;
            if (isset($shippingCost['currencyShortName'])) {
                $currencyUuid = $this->mappingService->getUuid(
                    $this->connectionId,
                    DefaultEntities::CURRENCY,
                    $shippingCost['currencyShortName'],
                    $this->context
                );
            }

            if ($currencyUuid === null) {
                $this->loggingService->addLogEntry(new EmptyNecessaryFieldRunLog(
                    $this->runId,
                    DefaultEntities::SHIPPING_METHOD_PRICE,
                    $shippingCost['id'],
                    'currency'
                ));

                continue;
            }

            $cost['currencyId'] = $currencyUuid;
            if (isset($shippingCosts[$key + 1])) {
                $cost['quantityEnd'] = $shippingCosts[$key + 1]['from'] - 0.01;
            }

            if (isset($shippingCost['factor']) && $shippingCost['factor'] > 0) {
                $this->loggingService->addLogEntry(new UnsupportedShippingPriceLog(
                    $this->runId,
                    DefaultEntities::SHIPPING_METHOD_PRICE,
                    $shippingCost['id'],
                    $this->oldShippingMethod
                ));

                continue;
            }

            $this->convertValue($cost, 'quantityStart', $shippingCost, 'from', self::TYPE_FLOAT);
            $this->convertValue($cost, 'price', $shippingCost, 'value', self::TYPE_FLOAT);

            if ($rule !== null) {
                $cost['rule'] = $rule;
            }

            $convertedCosts[] = $cost;
        }

        return $convertedCosts;
    }
}
