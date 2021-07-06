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
     * @var array
     */
    protected $relevantForAvailabilityRule = [
        'bind_time_from',
        'bind_time_to',
        'bind_laststock',
        'bind_weekday_from',
        'bind_weekday_to',
        'bind_weight_from',
        'bind_weight_to',
        'bind_price_from',
        'bind_price_to',
        'shippingCountries',
        'paymentMethods',
        'excludedCategories',
        'bind_shippingfree',
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
            || !\array_key_exists($data['calculation'], self::CALCULATION_TYPE_MAPPING)
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
        $this->convertValue($converted, 'active', $data, 'active', self::TYPE_BOOLEAN);
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

        $this->setCustomAvailabilityRule($data, $converted);

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
            $data['bind_time_from'],
            $data['bind_time_to'],
            $data['bind_laststock'],
            $data['bind_weekday_from'],
            $data['bind_weekday_to'],
            $data['bind_weight_from'],
            $data['bind_weight_to'],
            $data['bind_price_from'],
            $data['bind_price_to'],
            $data['shippingCountries'],
            $data['paymentMethods'],
            $data['excludedCategories'],
            $data['bind_shippingfree'],

            // Unused
            $data['surcharge_calculation'],
            $data['tax_calculation'],
            $data['bind_instock'],
            $data['bind_sql'],
            $data['status_link'],
            $data['calculation_sql'],
            $data['shippingfree']
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

    private function setCustomAvailabilityRule(array $data, array &$converted): void
    {
        $ruleData = $this->getRelevantDataForAvailabilityRule($data);

        /** @var string $jsonRuleData */
        $jsonRuleData = \json_encode($ruleData);
        $hash = \md5($jsonRuleData);

        $mainRuleMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::SHIPPING_METHOD . '_availability_rule_hash',
            $hash,
            $this->context
        );
        $this->mappingIds[] = $mainRuleMapping['id'];

        $mainRule = [
            'id' => $mainRuleMapping['entityUuid'],
            'priority' => 100,
            'moduleTypes' => [
                'types' => [
                    'shipping',
                ],
            ],
            'name' => $converted['name'],
            'description' => 'Migrated at ' . (new \DateTime())->format('d.m.Y H:i'),
        ];

        $mainOrContainerMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::SHIPPING_METHOD . '_availability_rule_main_orContainer',
            $hash,
            $this->context
        );
        $this->mappingIds[] = $mainOrContainerMapping['id'];

        $mainAndContainerMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::SHIPPING_METHOD . '_availability_rule_main_andContainer',
            $hash,
            $this->context
        );
        $this->mappingIds[] = $mainAndContainerMapping['id'];

        $mainOrContainer = [
            'id' => $mainOrContainerMapping['entityUuid'],
            'ruleId' => $mainRuleMapping['entityUuid'],
            'type' => 'orContainer',
            'position' => 0,
            'children' => [
                [
                    'id' => $mainAndContainerMapping['entityUuid'],
                    'ruleId' => $mainRuleMapping['entityUuid'],
                    'parentId' => $mainOrContainerMapping['entityUuid'],
                    'type' => 'andContainer',
                    'position' => 0,
                ],
            ],
        ];

        $position = 0;
        $this->setWeekdayCondition($ruleData, $hash, $mainOrContainer);
        $this->setBindTimeCondition($ruleData, $hash, $position, $mainOrContainer);
        $this->setLastStockCondition($ruleData, $hash, $position, $mainOrContainer);
        $this->setOtherConditions($ruleData, $hash, $position, $mainOrContainer);
        $this->setShippingCountries($ruleData, $hash, $position, $mainOrContainer);
        $this->setPaymentMethods($ruleData, $hash, $position, $mainOrContainer);
        $this->setExcludedCategories($ruleData, $hash, $position, $mainOrContainer);
        $this->setFreeShipping($ruleData, $hash, $position, $mainOrContainer);

        if (!isset($mainOrContainer['children'][0]['children'])) {
            return;
        }

        $mainRule['conditions'][] = $mainOrContainer;
        $converted['availabilityRule'] = $mainRule;
        unset($converted['availabilityRuleId']);
    }

    private function getDayOfWeekChildren(int $from, int $to, string $ruleId, string $parentId): array
    {
        $values = [];
        $oldTo = null;
        if ($from > $to) {
            $oldTo = $to;
            $to = 7;
        }

        if ($oldTo !== null) {
            $this->setDayOfWeekValues($values, 1, $oldTo, $ruleId, $parentId);
        }

        $this->setDayOfWeekValues($values, $from, $to, $ruleId, $parentId);

        return $values;
    }

    private function setDayOfWeekValues(array &$values, int $from, int $to, string $ruleId, string $parentId): void
    {
        $days = \range($from, $to);
        foreach ($days as $day) {
            $dayMapping = $this->mappingService->getOrCreateMapping(
                $this->connectionId,
                DefaultEntities::SHIPPING_METHOD . '_avaibility_rule_bind_weekday_from_value',
                $ruleId . '_dayOfWeek_' . $day,
                $this->context
            );

            $value = [
                'id' => $dayMapping['entityUuid'],
                'type' => 'dayOfWeek',
                'ruleId' => $ruleId,
                'parentId' => $parentId,
                'value' => [
                    'operator' => '=',
                    'dayOfWeek' => $day,
                ],
            ];

            $values[] = $value;
        }
    }

    private function getRelevantDataForAvailabilityRule(array $data): array
    {
        $relevantData = [];
        foreach ($this->relevantForAvailabilityRule as $key) {
            if (isset($data[$key]) && $data[$key] !== null) {
                $relevantData[$key] = $data[$key];
            }
        }

        return $relevantData;
    }

    private function setWeekdayCondition(
        array &$ruleData,
        string $hash,
        array &$mainOrContainer
    ): void {
        if (!isset($ruleData['bind_weekday_from']) && !isset($ruleData['bind_weekday_to'])) {
            return;
        }

        if (!isset($ruleData['bind_weekday_from'])) {
            $ruleData['bind_weekday_from'] = 1;
        }

        if (!isset($ruleData['bind_weekday_to'])) {
            $ruleData['bind_weekday_to'] = 7;
        }

        $mainAndContainerUuid = $mainOrContainer['children'][0]['id'];
        $mainRuleUuid = $mainOrContainer['ruleId'];
        $dayOfWeekOrContainerMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::SHIPPING_METHOD . '_availability_rule_main_bind_weekday_from_orContainer',
            $hash,
            $this->context
        );
        $this->mappingIds[] = $dayOfWeekOrContainerMapping['id'];

        $dayOfWeekOrContainer = [
            'id' => $dayOfWeekOrContainerMapping['entityUuid'],
            'ruleId' => $mainRuleUuid,
            'parentId' => $mainAndContainerUuid,
            'type' => 'orContainer',
            'position' => 0,
            'children' => $this->getDayOfWeekChildren(
                (int) $ruleData['bind_weekday_from'],
                (int) $ruleData['bind_weekday_to'],
                $mainRuleUuid,
                $mainAndContainerUuid
            ),
        ];

        $mainOrContainer['children'][0]['children'][] = $dayOfWeekOrContainer;
        unset($ruleData['bind_weekday_from'], $ruleData['bind_weekday_to']);
    }

    private function setBindTimeCondition(
        array &$ruleData,
        string $hash,
        int &$position,
        array &$mainOrContainer
    ): void {
        if (!isset($ruleData['bind_time_from']) && !isset($ruleData['bind_time_to'])) {
            return;
        }

        $mainAndContainerUuid = $mainOrContainer['children'][0]['id'];
        $mainRuleUuid = $mainOrContainer['ruleId'];
        $conditionMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::SHIPPING_METHOD . '_condition_bind_time',
            $hash,
            $this->context
        );

        $value = [];
        if (!isset($ruleData['bind_time_from'])) {
            $value['fromTime'] = '00:00';
        } else {
            $secs = $ruleData['bind_time_from'];
            $min = \str_pad((string) ($secs % 3600), 2, '0', \STR_PAD_LEFT);
            $hour = \str_pad((string) ($secs / 3600), 2, '0', \STR_PAD_LEFT);
            $value['fromTime'] = $hour . ':' . $min;
        }

        if (!isset($ruleData['bind_time_to'])) {
            $value['toTime'] = '00:00';
        } else {
            $secs = $ruleData['bind_time_to'];
            $min = \str_pad((string) ($secs % 3600), 2, '0', \STR_PAD_LEFT);
            $hour = \str_pad((string) ($secs / 3600), 2, '0', \STR_PAD_LEFT);
            $value['toTime'] = $hour . ':' . $min;
        }

        $condition = [
            'id' => $conditionMapping['entityUuid'],
            'ruleId' => $mainRuleUuid,
            'parentId' => $mainAndContainerUuid,
            'position' => ++$position,
            'type' => 'timeRange',
            'value' => $value,
        ];

        $mainOrContainer['children'][0]['children'][] = $condition;
        unset($ruleData['bind_time_from'], $ruleData['bind_time_to']);
    }

    private function setLastStockCondition(
        array &$ruleData,
        string $hash,
        int &$position,
        array &$mainOrContainer
    ): void {
        if (!isset($ruleData['bind_laststock']) || (int) $ruleData['bind_laststock'] !== 1) {
            return;
        }

        $mainAndContainerUuid = $mainOrContainer['children'][0]['id'];
        $mainRuleUuid = $mainOrContainer['ruleId'];
        $conditionMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::SHIPPING_METHOD . '_condition_bind_laststock',
            $hash,
            $this->context
        );

        $condition = [
            'id' => $conditionMapping['entityUuid'],
            'ruleId' => $mainRuleUuid,
            'parentId' => $mainAndContainerUuid,
            'type' => 'cartLineItemClearanceSale',
            'position' => ++$position,
            'value' => [
                'clearanceSale' => true,
            ],
        ];

        $mainOrContainer['children'][0]['children'][] = $condition;
        unset($ruleData['bind_laststock']);
    }

    private function setOtherConditions(
        array $ruleData,
        string $hash,
        int &$position,
        array &$mainOrContainer
    ): void {
        $mainAndContainerUuid = $mainOrContainer['children'][0]['id'];
        $mainRuleUuid = $mainOrContainer['ruleId'];
        $conditionValueMapping = [
            'bind_weight_from' => ['type' => 'cartWeight', 'operator' => '>=', 'value' => 'weight'],
            'bind_weight_to' => ['type' => 'cartWeight', 'operator' => '<=', 'value' => 'weight'],
            'bind_price_from' => ['type' => 'cartLineItemTotalPrice', 'operator' => '>=', 'value' => 'amount'],
            'bind_price_to' => ['type' => 'cartLineItemTotalPrice', 'operator' => '<=', 'value' => 'amount'],
        ];

        foreach ($ruleData as $key => $data) {
            if (!isset($conditionValueMapping[$key])) {
                continue;
            }

            $conditionMapping = $this->mappingService->getOrCreateMapping(
                $this->connectionId,
                DefaultEntities::SHIPPING_METHOD . '_condition_' . $key,
                $hash,
                $this->context
            );

            $condition = [
                'id' => $conditionMapping['entityUuid'],
                'ruleId' => $mainRuleUuid,
                'parentId' => $mainAndContainerUuid,
                'type' => $conditionValueMapping[$key]['type'],
                'position' => ++$position,
                'value' => [
                    'operator' => $conditionValueMapping[$key]['operator'],
                    $conditionValueMapping[$key]['value'] => (float) $data,
                ],
            ];

            $mainOrContainer['children'][0]['children'][] = $condition;
        }
    }

    private function setShippingCountries(array &$ruleData, string $hash, int &$position, array &$mainOrContainer): void
    {
        if (!isset($ruleData['shippingCountries']) || empty($ruleData['shippingCountries'])) {
            return;
        }

        $mainAndContainerUuid = $mainOrContainer['children'][0]['id'];
        $mainRuleUuid = $mainOrContainer['ruleId'];
        $conditionMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::SHIPPING_METHOD . '_condition_countries',
            $hash,
            $this->context
        );

        $countries = [];
        foreach ($ruleData['shippingCountries'] as $country) {
            $country = $this->mappingService->getCountryUuid($country['countryID'], $country['countryiso'], $country['iso3'], $this->connectionId, $this->context);

            if ($country === null) {
                continue;
            }

            $countries[] = $country;
        }

        if (empty($countries)) {
            return;
        }

        $condition = [
            'id' => $conditionMapping['entityUuid'],
            'ruleId' => $mainRuleUuid,
            'parentId' => $mainAndContainerUuid,
            'type' => 'customerShippingCountry',
            'position' => ++$position,
            'value' => [
                'operator' => '=',
                'countryIds' => $countries,
            ],
        ];

        $mainOrContainer['children'][0]['children'][] = $condition;
        unset($ruleData['shippingCountries']);
    }

    private function setPaymentMethods(array &$ruleData, string $hash, int &$position, array &$mainOrContainer): void
    {
        if (!isset($ruleData['paymentMethods']) || empty($ruleData['paymentMethods'])) {
            return;
        }

        $mainAndContainerUuid = $mainOrContainer['children'][0]['id'];
        $mainRuleUuid = $mainOrContainer['ruleId'];
        $conditionMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::SHIPPING_METHOD . '_condition_paymentMethods',
            $hash,
            $this->context
        );

        $paymentMethods = [];
        foreach ($ruleData['paymentMethods'] as $paymentMethodId) {
            $paymentMethodMapping = $this->mappingService->getMapping(
                $this->connectionId,
                DefaultEntities::PAYMENT_METHOD,
                $paymentMethodId,
                $this->context
            );

            if ($paymentMethodMapping === null) {
                continue;
            }

            $this->mappingIds[] = $paymentMethodMapping['id'];
            $paymentMethods[] = $paymentMethodMapping['entityUuid'];
        }

        if (empty($paymentMethods)) {
            return;
        }

        $condition = [
            'id' => $conditionMapping['entityUuid'],
            'ruleId' => $mainRuleUuid,
            'parentId' => $mainAndContainerUuid,
            'type' => 'paymentMethod',
            'position' => ++$position,
            'value' => [
                'operator' => '=',
                'paymentMethodIds' => $paymentMethods,
            ],
        ];

        $mainOrContainer['children'][0]['children'][] = $condition;
        unset($ruleData['paymentMethods']);
    }

    private function setExcludedCategories(array &$ruleData, string $hash, int &$position, array &$mainOrContainer): void
    {
        if (!isset($ruleData['excludedCategories']) || empty($ruleData['excludedCategories'])) {
            return;
        }

        $mainAndContainerUuid = $mainOrContainer['children'][0]['id'];
        $mainRuleUuid = $mainOrContainer['ruleId'];
        $conditionMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::SHIPPING_METHOD . '_condition_excludedCategories',
            $hash,
            $this->context
        );

        $excludedCategories = [];
        foreach ($ruleData['excludedCategories'] as $categoryId) {
            $categoryMapping = $this->mappingService->getMapping(
                $this->connectionId,
                DefaultEntities::CATEGORY,
                $categoryId,
                $this->context
            );

            if ($categoryMapping === null) {
                continue;
            }

            $this->mappingIds[] = $categoryMapping['id'];
            $excludedCategories[] = $categoryMapping['entityUuid'];
        }

        if (empty($excludedCategories)) {
            return;
        }

        $condition = [
            'id' => $conditionMapping['entityUuid'],
            'ruleId' => $mainRuleUuid,
            'parentId' => $mainAndContainerUuid,
            'type' => 'cartLineItemInCategory',
            'position' => ++$position,
            'value' => [
                'operator' => '!=',
                'categoryIds' => $excludedCategories,
            ],
        ];

        $mainOrContainer['children'][0]['children'][] = $condition;
        unset($ruleData['excludedCategories']);
    }

    private function setFreeShipping(
        array &$ruleData,
        string $hash,
        int &$position,
        array &$mainOrContainer
    ): void {
        if (!isset($ruleData['bind_shippingfree']) || (int) $ruleData['bind_shippingfree'] !== 1) {
            return;
        }

        $mainAndContainerUuid = $mainOrContainer['children'][0]['id'];
        $mainRuleUuid = $mainOrContainer['ruleId'];
        $conditionMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::SHIPPING_METHOD . '_condition_bind_shippingfree',
            $hash,
            $this->context
        );

        $condition = [
            'id' => $conditionMapping['entityUuid'],
            'ruleId' => $mainRuleUuid,
            'parentId' => $mainAndContainerUuid,
            'type' => 'cartHasDeliveryFreeItem',
            'position' => ++$position,
        ];

        $mainOrContainer['children'][0]['children'][] = $condition;
        unset($ruleData['bind_shippingfree']);
    }
}
