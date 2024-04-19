<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Checkout\Promotion\Aggregate\PromotionDiscount\PromotionDiscountEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Rule\Container\AndRule;
use Shopware\Core\Framework\Rule\Container\OrRule;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\AssociationRequiredMissingLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('services-settings')]
abstract class PromotionConverter extends ShopwareConverter
{
    protected Context $context;

    protected string $connectionId;

    /**
     * @var list<string>
     */
    private array $productUuids;

    private string $runId;

    /**
     * @param EntityRepository<SalesChannelCollection> $salesChannelRepository
     */
    public function __construct(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        private readonly EntityRepository $salesChannelRepository
    ) {
        parent::__construct($mappingService, $loggingService);
    }

    public function getSourceIdentifier(array $data): string
    {
        return $data['id'];
    }

    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        $this->generateChecksum($data);
        $this->context = $context;
        $this->runId = $migrationContext->getRunUuid();

        $connection = $migrationContext->getConnection();
        $this->connectionId = '';
        if ($connection !== null) {
            $this->connectionId = $connection->getId();
        }

        $converted = [];
        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PROMOTION,
            $data['id'],
            $context,
            $this->checksum
        );
        $converted['id'] = $this->mainMapping['entityUuid'];
        $converted['active'] = true;
        $converted['useCodes'] = true;
        $converted['useIndividualCodes'] = false;

        if (isset($data['vouchercode']) && $data['vouchercode'] !== '') {
            $this->convertValue($converted, 'code', $data, 'vouchercode');
        }

        if ((!isset($data['vouchercode']) || $data['vouchercode'] === '') && isset($data['individualCodes'])) {
            $converted['useIndividualCodes'] = true;
            $this->setIndividualCodes($data, $converted);
        }

        $this->setSalesChannel($data, $converted);
        $this->setProductNumbers($data);
        $this->setDiscount($data, $converted);
        $this->setShippingDiscount($data, $converted);
        $this->setCartRule($data, $converted);
        $this->setCustomerRule($data, $converted);

        $this->convertValue($converted, 'name', $data, 'description');
        $this->convertValue($converted, 'validFrom', $data, 'valid_from', self::TYPE_DATETIME);
        $this->convertValue($converted, 'validUntil', $data, 'valid_to', self::TYPE_DATETIME);
        $this->convertValue($converted, 'maxRedemptionsGlobal', $data, 'numberofunits', self::TYPE_INTEGER);
        $this->convertValue($converted, 'maxRedemptionsPerCustomer', $data, 'numorder', self::TYPE_INTEGER);

        unset(
            $data['id'],
            $data['modus'],
            $data['taxconfig'],
            $data['customer_stream_ids'],
            $data['ordercode'],
            $data['shippingfree']
        );

        $returnData = $data;
        if (empty($returnData)) {
            $returnData = null;
        }
        $this->updateMainMapping($migrationContext, $context);

        return new ConvertStruct($converted, $returnData, $this->mainMapping['id'] ?? null);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $converted
     */
    private function setIndividualCodes(array &$data, array &$converted): void
    {
        $converted['individualCodes'] = [];
        foreach ($data['individualCodes'] as $code) {
            $codeMapping = $this->mappingService->getOrCreateMapping(
                $this->connectionId,
                DefaultEntities::PROMOTION_INDIVIDUAL_CODE,
                $code['id'],
                $this->context
            );

            $newCode = [];
            $newCode['id'] = $codeMapping['entityUuid'];
            $this->mappingIds[] = $codeMapping['id'];

            $this->convertValue($newCode, 'code', $code, 'code');

            $name = '';
            if (isset($code['firstname'])) {
                $name .= $code['firstname'];
                $newCode['payload']['customerName'] = $name;
            }

            if (isset($code['lastname'])) {
                $name .= ' ' . $code['lastname'];
                $newCode['payload']['customerName'] = $name;
            }

            if (isset($code['userID'])) {
                $customerMapping = $this->mappingService->getMapping(
                    $this->connectionId,
                    DefaultEntities::CUSTOMER,
                    $code['userID'],
                    $this->context
                );

                if ($customerMapping !== null) {
                    $newCode['payload']['customerId'] = $customerMapping['entityUuid'];
                    $this->mappingIds[] = $customerMapping['id'];
                }
            }

            $converted['individualCodes'][] = $newCode;
        }
        unset($data['individualCodes']);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $converted
     */
    private function setDiscount(array &$data, array &$converted): void
    {
        $type = PromotionDiscountEntity::TYPE_ABSOLUTE;
        if (isset($data['percental']) && (bool) $data['percental'] === true) {
            $type = PromotionDiscountEntity::TYPE_PERCENTAGE;
        }

        $discountMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PROMOTION_DISCOUNT,
            $data['id'],
            $this->context
        );

        $discount = [
            'id' => $discountMapping['entityUuid'],
            'scope' => PromotionDiscountEntity::SCOPE_CART,
            'type' => $type,
            'value' => (float) $data['value'],
            'considerAdvancedRules' => false,
        ];
        $this->mappingIds[] = $discountMapping['id'];

        if (
            isset($data['strict'])
            && ((int) $data['strict']) === 1
            && (!empty($this->productUuids) || isset($data['bindtosupplier']))
        ) {
            $this->setDiscountRule($data, $discount);
        }

        $converted['discounts'][] = $discount;
        unset(
            $data['value'],
            $data['percental'],
            $data['strict']
        );
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $discount
     */
    private function setDiscountRule(array $data, array &$discount): void
    {
        $rule = $this->getDiscountRule($data);

        if ($rule !== null) {
            $discount['considerAdvancedRules'] = true;
            $discount['applierKey'] = 'ALL';
            $discount['discountRules'][] = $rule;
        }
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>|null
     */
    private function getDiscountRule(array $data): ?array
    {
        $promotionRuleMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PROMOTION_DISCOUNT_RULE,
            $data['id'],
            $this->context
        );
        $this->mappingIds[] = $promotionRuleMapping['id'];

        $ruleMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PROMOTION_DISCOUNT_RULE . '_mainRule',
            $data['id'],
            $this->context
        );
        $this->mappingIds[] = $ruleMapping['id'];

        $orContainerMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PROMOTION_DISCOUNT_RULE . '_orContainer',
            $data['id'],
            $this->context
        );
        $this->mappingIds[] = $orContainerMapping['id'];

        $orConditionContainerMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PROMOTION_DISCOUNT_RULE . '_orConditionContainer',
            $data['id'],
            $this->context
        );
        $this->mappingIds[] = $orConditionContainerMapping['id'];

        $rule = [
            'id' => $ruleMapping['entityUuid'],
            'name' => 'Promotion discount rule: ' . $data['description'],
            'priority' => 0,
            'conditions' => [
                [
                    'id' => $orContainerMapping['entityUuid'],
                    'type' => (new OrRule())->getName(),
                    'value' => [],
                ],

                [
                    'id' => $orConditionContainerMapping['entityUuid'],
                    'type' => (new OrRule())->getName(),
                    'parentId' => $orContainerMapping['entityUuid'],
                    'value' => [],
                ],
            ],
        ];

        $oneRuleAdded = false;
        if (!empty($this->productUuids)) {
            $conditionMapping = $this->mappingService->getOrCreateMapping(
                $this->connectionId,
                DefaultEntities::PROMOTION_DISCOUNT_RULE . '_product_condition',
                $data['id'],
                $this->context
            );
            $this->mappingIds[] = $conditionMapping['id'];

            $rule['conditions'][] = [
                'id' => $conditionMapping['entityUuid'],
                'type' => 'cartLineItem',
                'parentId' => $orConditionContainerMapping['entityUuid'],
                'position' => 1,
                'value' => [
                    'identifiers' => $this->productUuids,
                    'operator' => '=',
                ],
            ];
            $oneRuleAdded = true;
        }

        if (isset($data['bindtosupplier'])) {
            $manufacturerMapping = $this->mappingService->getMapping(
                $this->connectionId,
                DefaultEntities::PRODUCT_MANUFACTURER,
                $data['bindtosupplier'],
                $this->context
            );

            if ($manufacturerMapping !== null) {
                $manufacturerConditionMapping = $this->mappingService->getOrCreateMapping(
                    $this->connectionId,
                    DefaultEntities::PROMOTION_DISCOUNT_RULE . '_manufacturer_condition',
                    $data['id'],
                    $this->context
                );

                $manufacturerRule = [
                    'id' => $manufacturerConditionMapping['entityUuid'],
                    'type' => 'cartLineItemOfManufacturer',
                    'parentId' => $orConditionContainerMapping['entityUuid'],
                    'position' => 1,
                    'value' => [
                        'manufacturerIds' => [$manufacturerMapping['entityUuid']],
                        'operator' => '=',
                    ],
                ];

                $rule['conditions'][] = $manufacturerRule;
                $oneRuleAdded = true;
            }
        }

        if (!$oneRuleAdded) {
            return null;
        }

        return $rule;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function setProductNumbers(array &$data): void
    {
        if (!isset($data['restrictarticles'])) {
            return;
        }

        $productNumbers = \array_filter(\explode(';', $data['restrictarticles']));

        if (!empty($productNumbers)) {
            $this->productUuids = [];
            foreach ($productNumbers as $productNumber) {
                $productMapping = $this->mappingService->getMapping(
                    $this->connectionId,
                    DefaultEntities::PRODUCT,
                    $productNumber,
                    $this->context
                );

                if ($productMapping === null) {
                    $this->loggingService->addLogEntry(
                        new AssociationRequiredMissingLog(
                            $this->runId,
                            DefaultEntities::PRODUCT,
                            $productNumber,
                            DefaultEntities::PROMOTION
                        )
                    );

                    continue;
                }

                $this->productUuids[] = (string) $productMapping['entityUuid'];
                $this->mappingIds[] = $productMapping['id'];
                unset($data['restrictarticles']);
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $converted
     */
    private function setCartRule(array &$data, array &$converted): void
    {
        if (empty($this->productUuids) && !isset($data['bindtosupplier']) && !isset($data['minimumcharge'])) {
            return;
        }

        $promotionRuleMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PROMOTION . '_rule',
            $data['id'],
            $this->context
        );
        $this->mappingIds[] = $promotionRuleMapping['id'];

        $ruleMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PROMOTION . '_rule_mainRule',
            $data['id'],
            $this->context
        );
        $this->mappingIds[] = $ruleMapping['id'];

        $orContainerMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PROMOTION . '_rule_orContainer',
            $data['id'],
            $this->context
        );
        $this->mappingIds[] = $orContainerMapping['id'];

        $andContainerMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PROMOTION . '_rule_andContainer',
            $data['id'],
            $this->context
        );
        $this->mappingIds[] = $andContainerMapping['id'];

        $rule = [
            'id' => $ruleMapping['entityUuid'],
            'name' => 'Promotion cart rule: ' . $data['description'],
            'priority' => 0,
            'conditions' => [
                [
                    'id' => $orContainerMapping['entityUuid'],
                    'type' => (new OrRule())->getName(),
                    'value' => [],
                ],

                [
                    'id' => $andContainerMapping['entityUuid'],
                    'type' => (new AndRule())->getName(),
                    'parentId' => $orContainerMapping['entityUuid'],
                    'value' => [],
                ],
            ],
        ];

        $oneRuleAdded = false;
        if (!empty($this->productUuids)) {
            $conditionMapping = $this->mappingService->getOrCreateMapping(
                $this->connectionId,
                DefaultEntities::PROMOTION . '_rule_product_condition',
                $data['id'],
                $this->context
            );
            $this->mappingIds[] = $conditionMapping['id'];

            $rule['conditions'][] = [
                'id' => $conditionMapping['entityUuid'],
                'type' => 'cartLineItem',
                'parentId' => $andContainerMapping['entityUuid'],
                'position' => 1,
                'value' => [
                    'identifiers' => $this->productUuids,
                    'operator' => '=',
                ],
            ];
            $oneRuleAdded = true;
        }

        if (isset($data['bindtosupplier'])) {
            $manufacturerMapping = $this->mappingService->getMapping(
                $this->connectionId,
                DefaultEntities::PRODUCT_MANUFACTURER,
                $data['bindtosupplier'],
                $this->context
            );

            if ($manufacturerMapping !== null) {
                $manufacturerConditionMapping = $this->mappingService->getOrCreateMapping(
                    $this->connectionId,
                    DefaultEntities::PROMOTION . '_rule_manufacturer_condition',
                    $data['id'],
                    $this->context
                );

                $manufacturerRule = [
                    'id' => $manufacturerConditionMapping['entityUuid'],
                    'type' => 'cartLineItemOfManufacturer',
                    'parentId' => $andContainerMapping['entityUuid'],
                    'position' => 1,
                    'value' => [
                        'manufacturerIds' => [$manufacturerMapping['entityUuid']],
                        'operator' => '=',
                    ],
                ];

                $rule['conditions'][] = $manufacturerRule;
                unset($data['bindtosupplier']);
                $oneRuleAdded = true;
            } else {
                $this->loggingService->addLogEntry(
                    new AssociationRequiredMissingLog(
                        $this->runId,
                        DefaultEntities::PRODUCT_MANUFACTURER,
                        $data['bindtosupplier'],
                        DefaultEntities::PROMOTION_DISCOUNT
                    )
                );
            }
        }

        if (isset($data['minimumcharge'])) {
            $cartGoodsPriceConditionMapping = $this->mappingService->getOrCreateMapping(
                $this->connectionId,
                DefaultEntities::PROMOTION . '_rule_cartGoodsPrice_condition',
                $data['id'],
                $this->context
            );

            $cartGoodsPriceRule = [
                'id' => $cartGoodsPriceConditionMapping['entityUuid'],
                'type' => 'cartGoodsPrice',
                'parentId' => $andContainerMapping['entityUuid'],
                'position' => 1,
                'value' => [
                    'amount' => (float) $data['minimumcharge'],
                    'operator' => '>=',
                ],
            ];

            $rule['conditions'][] = $cartGoodsPriceRule;
            $oneRuleAdded = true;
            unset($data['minimumcharge']);
        }

        if ($oneRuleAdded) {
            $converted['cartRules'][] = $rule;
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $converted
     */
    private function setSalesChannel(array &$data, array &$converted): void
    {
        if (isset($data['subshopID'])) {
            $salesChannelMapping = $this->mappingService->getMapping(
                $this->connectionId,
                DefaultEntities::SALES_CHANNEL,
                $data['subshopID'],
                $this->context
            );

            if ($salesChannelMapping === null) {
                $this->loggingService->addLogEntry(
                    new AssociationRequiredMissingLog(
                        $this->runId,
                        DefaultEntities::SALES_CHANNEL,
                        $data['subshopID'],
                        DefaultEntities::PROMOTION
                    )
                );

                return;
            }

            $salesChannelRelationMapping = $this->mappingService->getOrCreateMapping(
                $this->connectionId,
                DefaultEntities::SALES_CHANNEL . '_relation',
                $data['subshopID'],
                $this->context
            );
            $this->mappingIds[] = $salesChannelMapping['id'];
            $this->mappingIds[] = $salesChannelRelationMapping['id'];

            $converted['salesChannels'][] = [
                'id' => $salesChannelRelationMapping['entityUuid'],
                'salesChannelId' => $salesChannelMapping['entityUuid'],
                'priority' => 0,
            ];
            unset($data['subshopID']);
        } else {
            $salesChannelIds = $this->salesChannelRepository->searchIds(new Criteria(), $this->context)->getIds();

            $priority = 0;
            $promotionId = $converted['id'];
            foreach ($salesChannelIds as $salesChannelId) {
                if (\is_array($salesChannelId)) {
                    continue;
                }

                $salesChannelRelationMapping = $this->mappingService->getOrCreateMapping(
                    $this->connectionId,
                    DefaultEntities::SALES_CHANNEL . '_relation',
                    $promotionId . '_' . $salesChannelId,
                    $this->context
                );
                $this->mappingIds[] = $salesChannelRelationMapping['id'];

                $converted['salesChannels'][] = [
                    'id' => $salesChannelRelationMapping['entityUuid'],
                    'salesChannelId' => $salesChannelId,
                    'priority' => $priority++,
                ];
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $converted
     */
    private function setCustomerRule(array &$data, array &$converted): void
    {
        if (!isset($data['customergroup'])) {
            return;
        }

        $customerGroupMapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::CUSTOMER_GROUP,
            $data['customergroup'],
            $this->context
        );

        if ($customerGroupMapping === null) {
            $this->loggingService->addLogEntry(new AssociationRequiredMissingLog(
                $this->runId,
                DefaultEntities::CUSTOMER_GROUP,
                $data['customergroup'],
                DefaultEntities::PROMOTION
            ));

            return;
        }
        $this->mappingIds[] = $customerGroupMapping['id'];

        $promotionRuleMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PROMOTION_PERSONA_RULE . '_rule',
            $data['id'],
            $this->context
        );
        $this->mappingIds[] = $promotionRuleMapping['id'];

        $ruleMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PROMOTION_PERSONA_RULE . '_mainRule',
            $data['id'],
            $this->context
        );
        $this->mappingIds[] = $ruleMapping['id'];

        $orContainerMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PROMOTION_PERSONA_RULE . '_orContainer',
            $data['id'],
            $this->context
        );
        $this->mappingIds[] = $orContainerMapping['id'];

        $andContainerMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PROMOTION_PERSONA_RULE . '_andContainer',
            $data['id'],
            $this->context
        );
        $this->mappingIds[] = $andContainerMapping['id'];

        $conditionMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PROMOTION_PERSONA_RULE . '_condition',
            $data['id'],
            $this->context
        );
        $this->mappingIds[] = $conditionMapping['id'];

        $rule = [
            'id' => $ruleMapping['entityUuid'],
            'name' => 'Promotion customer rule: ' . $data['description'],
            'priority' => 0,
            'conditions' => [
                [
                    'id' => $orContainerMapping['entityUuid'],
                    'type' => (new OrRule())->getName(),
                    'value' => [],
                ],

                [
                    'id' => $andContainerMapping['entityUuid'],
                    'type' => (new AndRule())->getName(),
                    'parentId' => $orContainerMapping['entityUuid'],
                    'value' => [],
                ],
                [
                    'id' => $conditionMapping['entityUuid'],
                    'type' => 'customerCustomerGroup',
                    'parentId' => $andContainerMapping['entityUuid'],
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

        $converted['personaRules'][] = $rule;
        unset($data['customergroup']);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $converted
     */
    private function setShippingDiscount(array $data, array &$converted): void
    {
        if (!isset($data['shippingfree']) || (int) $data['shippingfree'] === 0) {
            return;
        }

        $deliveryDiscountMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PROMOTION_DISCOUNT . '_delivery',
            $data['id'],
            $this->context
        );

        $deliveryDiscount = [
            'id' => $deliveryDiscountMapping['entityUuid'],
            'scope' => PromotionDiscountEntity::SCOPE_DELIVERY,
            'type' => PromotionDiscountEntity::TYPE_PERCENTAGE,
            'value' => 100,
            'considerAdvancedRules' => false,
        ];
        $this->mappingIds[] = $deliveryDiscountMapping['id'];

        $converted['discounts'][] = $deliveryDiscount;
    }
}
