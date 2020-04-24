<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Rule\Container\AndRule;
use Shopware\Core\Framework\Rule\Container\OrRule;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\EmptyNecessaryFieldRunLog;
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

    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        $this->generateChecksum($data);
        $this->context = $context;
        $this->runId = $migrationContext->getRunUuid();
        $this->oldShippingMethod = $data['id'];
        $this->mainLocale = $data['_locale'];

        $connection = $migrationContext->getConnection();
        $this->connectionId = '';
        if ($connection !== null) {
            $this->connectionId = $connection->getId();
        }

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
        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::SHIPPING_METHOD,
            $data['id'],
            $this->context,
            $this->checksum
        );
        $converted['id'] = $this->mainMapping['entityUuid'];

        $defaultDeliveryTimeMapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::DELIVERY_TIME,
            'default_delivery_time',
            $this->context
        );

        if ($defaultDeliveryTimeMapping !== null) {
            $converted['deliveryTimeId'] = $defaultDeliveryTimeMapping['entityUuid'];
            $this->mappingIds[] = $defaultDeliveryTimeMapping['id'];
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

        $returnData = $data;
        if (empty($returnData)) {
            $returnData = null;
        }
        $this->updateMainMapping($migrationContext, $context);

        return new ConvertStruct($converted, $returnData, $this->mainMapping['id']);
    }

    protected function getShippingMethodTranslation(array &$shippingMethod, array $data): void
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
        $localeTranslation['shippingMethodId'] = $shippingMethod['id'];

        $this->convertValue($localeTranslation, 'name', $data, 'name');
        $this->convertValue($localeTranslation, 'description', $data, 'description');
        $this->convertValue($localeTranslation, 'comment', $data, 'comment');

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::SHIPPING_METHOD_TRANSLATION,
            $data['id'] . ':' . $this->mainLocale,
            $this->context
        );
        $localeTranslation['id'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];

        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $this->mainLocale, $this->context);

        if ($languageUuid !== null) {
            $localeTranslation['languageId'] = $languageUuid;
            $shippingMethod['translations'][$languageUuid] = $localeTranslation;
        }
    }

    protected function getCustomerGroupCalculationRule(array $data): array
    {
        $customerGroupId = $data['customergroupID'];
        $customerGroupMapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::CUSTOMER_GROUP,
            $customerGroupId,
            $this->context
        );

        if ($customerGroupMapping === null) {
            return [];
        }
        $this->mappingIds[] = $customerGroupMapping['id'];
        $customerGroupName = $customerGroupId;
        if (isset($data['customerGroup']['description'])) {
            $customerGroupName = $data['customerGroup']['description'];
        }

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::RULE,
            'customerGroupRule_' . $customerGroupId,
            $this->context
        );
        $priceRuleUuid = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::RULE,
            'customerGroupRule_orContainer_' . $customerGroupId,
            $this->context
        );
        $orContainerUuid = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::RULE,
            'customerGroupRule_andContainer_' . $customerGroupId,
            $this->context
        );
        $andContainerUuid = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::RULE,
            'customerGroupRule_condition_' . $customerGroupId,
            $this->context
        );
        $conditionUuid = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];

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
                            $customerGroupMapping['entityUuid'],
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
        $salesChannelMapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::SALES_CHANNEL,
            $shopId,
            $this->context
        );

        if ($salesChannelMapping === null) {
            return [];
        }

        $salesChannelUuid = $salesChannelMapping['entityUuid'];
        $this->mappingIds[] = $salesChannelMapping['id'];
        $salesChannelName = $shopId;
        if (isset($data['shop']['name'])) {
            $salesChannelName = $data['shop']['name'];
        }

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::RULE,
            'salesChannelRule_' . $shopId,
            $this->context
        );
        $priceRuleUuid = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::RULE,
            'salesChannelRule_orContainer_' . $shopId,
            $this->context
        );
        $orContainerUuid = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::RULE,
            'salesChannelRule_andContainer_' . $shopId,
            $this->context
        );
        $andContainerUuid = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::RULE,
            'salesChannelRule_condition_' . $shopId,
            $this->context
        );
        $conditionUuid = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];

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
        $salesChannelMapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::SALES_CHANNEL,
            $shopId,
            $this->context
        );

        if ($salesChannelMapping === null) {
            return [];
        }
        $salesChannelUuid = $salesChannelMapping['entityUuid'];
        $this->mappingIds[] = $salesChannelMapping['id'];

        $customerGroupId = $data['customergroupID'];
        $customerGroupMapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::CUSTOMER_GROUP,
            $customerGroupId,
            $this->context
        );

        if ($customerGroupMapping === null) {
            return [];
        }

        $customerGroupUuid = $customerGroupMapping['entityUuid'];
        $this->mappingIds[] = $customerGroupMapping['id'];

        $customerGroupName = $customerGroupId;
        if (isset($data['customerGroup']['description'])) {
            $customerGroupName = $data['customerGroup']['description'];
        }

        $salesChannelName = $shopId;
        if (isset($data['shop']['name'])) {
            $salesChannelName = $data['shop']['name'];
        }

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::RULE,
            'salesChannelAndCustomerGroupRule_' . $shopId . '_' . $customerGroupId,
            $this->context
        );
        $priceRuleUuid = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::RULE,
            'salesChannelAndCustomerGroupRule_orContainer_' . $shopId . '_' . $customerGroupId,
            $this->context
        );
        $orContainerUuid = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::RULE,
            'salesChannelAndCustomerGroupRule_andContainer_' . $shopId . '_' . $customerGroupId,
            $this->context
        );
        $andContainerUuid = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::RULE,
            'salesChannelAndCustomerGroupRule_condition_' . $shopId . '_' . $customerGroupId,
            $this->context
        );
        $conditionUuid = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];

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
            $key = (int) $key;
            $cost = [];

            $mapping = $this->mappingService->getOrCreateMapping(
                $this->connectionId,
                DefaultEntities::SHIPPING_METHOD_PRICE,
                $shippingCost['id'],
                $this->context
            );
            $cost['id'] = $mapping['entityUuid'];
            $this->mappingIds[] = $mapping['id'];

            $cost['calculation'] = $calculationType;
            $cost['shippingMethodId'] = $this->oldShippingMethod;

            if (isset($shippingCost['currencyShortName'])) {
                $currencyMapping = $this->mappingService->getMapping(
                    $this->connectionId,
                    DefaultEntities::CURRENCY,
                    $shippingCost['currencyShortName'],
                    $this->context
                );
            }

            if (!isset($currencyMapping)) {
                $this->loggingService->addLogEntry(new EmptyNecessaryFieldRunLog(
                    $this->runId,
                    DefaultEntities::SHIPPING_METHOD_PRICE,
                    $shippingCost['id'],
                    'currency'
                ));

                continue;
            }
            $currencyUuid = $currencyMapping['entityUuid'];
            $this->mappingIds[] = $currencyMapping['id'];
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
