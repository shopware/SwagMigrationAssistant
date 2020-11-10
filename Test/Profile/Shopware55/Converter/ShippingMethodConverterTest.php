<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware55\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\Converter\ShippingMethodConverter;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ShippingMethodDataSet;
use SwagMigrationAssistant\Profile\Shopware\Logging\Log\UnsupportedShippingCalculationType;
use SwagMigrationAssistant\Profile\Shopware\Logging\Log\UnsupportedShippingPriceLog;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55ShippingMethodConverter;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\DummyMappingService;

class ShippingMethodConverterTest extends TestCase
{
    /**
     * @var DummyLoggingService
     */
    private $loggingService;

    /**
     * @var ShippingMethodConverter
     */
    private $shippingMethodConverter;

    /**
     * @var SwagMigrationConnectionEntity
     */
    private $connection;

    /**
     * @var MigrationContext
     */
    private $migrationContext;

    /**
     * @var string
     */
    private $runId;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var DummyMappingService
     */
    private $mappingService;

    protected function setUp(): void
    {
        $this->mappingService = new DummyMappingService();
        $this->loggingService = new DummyLoggingService();
        $this->shippingMethodConverter = new Shopware55ShippingMethodConverter($this->mappingService, $this->loggingService);

        $this->runId = Uuid::randomHex();
        $this->connection = new SwagMigrationConnectionEntity();
        $this->connection->setId(Uuid::randomHex());
        $this->connection->setProfileName(Shopware55Profile::PROFILE_NAME);

        $this->context = Context::createDefaultContext();
        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runId,
            new ShippingMethodDataSet(),
            0,
            250
        );

        $this->mappingService->getOrCreateMapping(
            $this->connection->getId(),
            DefaultEntities::DELIVERY_TIME,
            'default_delivery_time',
            $this->context,
            null,
            null,
            Uuid::randomHex()
        );

        $this->mappingService->getOrCreateMapping(
            $this->connection->getId(),
            DefaultEntities::DELIVERY_TIME,
            'default_delivery_time',
            $this->context,
            null,
            null,
            Uuid::randomHex()
        );

        $this->mappingService->getOrCreateMapping(
            $this->connection->getId(),
            DefaultEntities::CURRENCY,
            'EUR',
            $this->context,
            null,
            null,
            Uuid::randomHex()
        );
    }

    public function testSupports(): void
    {
        $supportsDefinition = $this->shippingMethodConverter->supports($this->migrationContext);

        static::assertTrue($supportsDefinition);
    }

    public function testConvert(): void
    {
        $shippingMethodData = require __DIR__ . '/../../../_fixtures/shipping_method_data.php';

        $convertResult = $this->shippingMethodConverter->convert($shippingMethodData[0], $this->context, $this->migrationContext);
        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($convertResult->getMappingUuid());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey(DummyMappingService::DEFAULT_LANGUAGE_UUID, $converted['translations']);
    }

    public function testConvertWithInvalidCalculation(): void
    {
        $shippingMethodData = require __DIR__ . '/../../../_fixtures/shipping_method_data.php';
        $shippingMethodData[0]['calculation'] = '5';

        $convertResult = $this->shippingMethodConverter->convert($shippingMethodData[0], $this->context, $this->migrationContext);
        $logs = $this->loggingService->getLoggingArray();
        $error = new UnsupportedShippingCalculationType('', DefaultEntities::SHIPPING_METHOD, '15', '5');

        static::assertNotNull($convertResult->getUnmapped());
        static::assertNull($convertResult->getConverted());
        static::assertCount(1, $logs);
        static::assertSame($error->getCode(), $logs[0]['code']);
        static::assertSame($error->getSourceId(), $logs[0]['sourceId']);
        static::assertSame($error->getEntity(), $logs[0]['entity']);
        static::assertSame($error->getParameters()['type'], $logs[0]['parameters']['type']);
    }

    public function testConvertWithFactor(): void
    {
        $shippingMethodData = require __DIR__ . '/../../../_fixtures/shipping_method_data.php';
        $shippingMethodData[0]['shippingCosts'][0]['factor'] = 100.0;

        $convertResult = $this->shippingMethodConverter->convert($shippingMethodData[0], $this->context, $this->migrationContext);
        $logs = $this->loggingService->getLoggingArray();
        $error = new UnsupportedShippingPriceLog('', DefaultEntities::SHIPPING_METHOD_PRICE, '309', '15');

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($convertResult->getConverted());
        static::assertCount(1, $logs);
        static::assertSame($error->getCode(), $logs[0]['code']);
        static::assertSame($error->getSourceId(), $logs[0]['sourceId']);
        static::assertSame($error->getEntity(), $logs[0]['entity']);
        static::assertSame($error->getParameters()['shippingMethodId'], $logs[0]['parameters']['shippingMethodId']);
    }

    public function conditionDataProvider(): array
    {
        return [
            'fromAndToTimeRange' => [
                'bindValues' => [
                    'bind_time_from' => 25200,
                    'bind_time_to' => 28800,
                ],

                'expectedConditions' => [
                    [
                        'type' => 'timeRange',
                        'value' => [
                            'fromTime' => '07:00',
                            'toTime' => '08:00',
                        ],
                    ],
                ],
            ],

            'lastStock' => [
                'bindValues' => [
                    'bind_laststock' => true,
                ],

                'expectedConditions' => [
                    [
                        'type' => 'cartLineItemClearanceSale',
                        'value' => [
                            'clearanceSale' => true,
                        ],
                    ],
                ],
            ],

            'fromAndToWeight' => [
                'bindValues' => [
                    'bind_weight_from' => '10',
                    'bind_weight_to' => '100',
                ],

                'expectedConditions' => [
                    [
                        'type' => 'cartWeight',
                        'value' => [
                            'operator' => '>=',
                            'weight' => 10.0,
                        ],
                    ],
                    [
                        'type' => 'cartWeight',
                        'value' => [
                            'operator' => '<=',
                            'weight' => 100.0,
                        ],
                    ],
                ],
            ],

            'onlyFromWeight' => [
                'bindValues' => [
                    'bind_weight_from' => '10',
                ],

                'expectedConditions' => [
                    [
                        'type' => 'cartWeight',
                        'value' => [
                            'operator' => '>=',
                            'weight' => 10.0,
                        ],
                    ],
                ],
            ],

            'onlyToWeight' => [
                'bindValues' => [
                    'bind_weight_to' => '100',
                ],

                'expectedConditions' => [
                    [
                        'type' => 'cartWeight',
                        'value' => [
                            'operator' => '<=',
                            'weight' => 100.0,
                        ],
                    ],
                ],
            ],

            'fromAndToPrice' => [
                'bindValues' => [
                    'bind_price_from' => '10',
                    'bind_price_to' => '100',
                ],

                'expectedConditions' => [
                    [
                        'type' => 'cartLineItemTotalPrice',
                        'value' => [
                            'operator' => '>=',
                            'amount' => 10.0,
                        ],
                    ],
                    [
                        'type' => 'cartLineItemTotalPrice',
                        'value' => [
                            'operator' => '<=',
                            'amount' => 100.0,
                        ],
                    ],
                ],
            ],

            'onlyFromPrice' => [
                'bindValues' => [
                    'bind_price_from' => '10',
                ],

                'expectedConditions' => [
                    [
                        'type' => 'cartLineItemTotalPrice',
                        'value' => [
                            'operator' => '>=',
                            'amount' => 10.0,
                        ],
                    ],
                ],
            ],

            'onlyToPrice' => [
                'bindValues' => [
                    'bind_price_to' => '100',
                ],

                'expectedConditions' => [
                    [
                        'type' => 'cartLineItemTotalPrice',
                        'value' => [
                            'operator' => '<=',
                            'amount' => 100.0,
                        ],
                    ],
                ],
            ],

            'fromAndToWeekday' => [
                'bindValues' => [
                    'bind_weekday_from' => '1',
                    'bind_weekday_to' => '5',
                ],

                'expectedConditions' => [
                    [
                        'type' => 'orContainer',
                        'children' => [
                            [
                                'type' => 'dayOfWeek',
                                'value' => [
                                    'operator' => '=',
                                    'dayOfWeek' => 1,
                                ],
                            ],
                            [
                                'type' => 'dayOfWeek',
                                'value' => [
                                    'operator' => '=',
                                    'dayOfWeek' => 2,
                                ],
                            ],
                            [
                                'type' => 'dayOfWeek',
                                'value' => [
                                    'operator' => '=',
                                    'dayOfWeek' => 3,
                                ],
                            ],
                            [
                                'type' => 'dayOfWeek',
                                'value' => [
                                    'operator' => '=',
                                    'dayOfWeek' => 4,
                                ],
                            ],
                            [
                                'type' => 'dayOfWeek',
                                'value' => [
                                    'operator' => '=',
                                    'dayOfWeek' => 5,
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            'onlyFromWeekday' => [
                'bindValues' => [
                    'bind_weekday_from' => '5',
                ],

                'expectedConditions' => [
                    [
                        'type' => 'orContainer',
                        'children' => [
                            [
                                'type' => 'dayOfWeek',
                                'value' => [
                                    'operator' => '=',
                                    'dayOfWeek' => 5,
                                ],
                            ],
                            [
                                'type' => 'dayOfWeek',
                                'value' => [
                                    'operator' => '=',
                                    'dayOfWeek' => 6,
                                ],
                            ],
                            [
                                'type' => 'dayOfWeek',
                                'value' => [
                                    'operator' => '=',
                                    'dayOfWeek' => 7,
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            'onlyToWeekday' => [
                'bindValues' => [
                    'bind_weekday_to' => '3',
                ],

                'expectedConditions' => [
                    [
                        'type' => 'orContainer',
                        'children' => [
                            [
                                'type' => 'dayOfWeek',
                                'value' => [
                                    'operator' => '=',
                                    'dayOfWeek' => 1,
                                ],
                            ],
                            [
                                'type' => 'dayOfWeek',
                                'value' => [
                                    'operator' => '=',
                                    'dayOfWeek' => 2,
                                ],
                            ],
                            [
                                'type' => 'dayOfWeek',
                                'value' => [
                                    'operator' => '=',
                                    'dayOfWeek' => 3,
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            'fromBiggerThenToWeekday' => [
                'bindValues' => [
                    'bind_weekday_from' => '5',
                    'bind_weekday_to' => '2',
                ],

                'expectedConditions' => [
                    [
                        'type' => 'orContainer',
                        'children' => [
                            [
                                'type' => 'dayOfWeek',
                                'value' => [
                                    'operator' => '=',
                                    'dayOfWeek' => 1,
                                ],
                            ],
                            [
                                'type' => 'dayOfWeek',
                                'value' => [
                                    'operator' => '=',
                                    'dayOfWeek' => 2,
                                ],
                            ],
                            [
                                'type' => 'dayOfWeek',
                                'value' => [
                                    'operator' => '=',
                                    'dayOfWeek' => 5,
                                ],
                            ],
                            [
                                'type' => 'dayOfWeek',
                                'value' => [
                                    'operator' => '=',
                                    'dayOfWeek' => 6,
                                ],
                            ],
                            [
                                'type' => 'dayOfWeek',
                                'value' => [
                                    'operator' => '=',
                                    'dayOfWeek' => 7,
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            'allExtendedConfigurationsInOne' => [
                'bindValues' => [
                    'bind_time_from' => 25200,
                    'bind_time_to' => 28800,
                    'bind_laststock' => true,
                    'bind_weight_from' => '10',
                    'bind_weight_to' => '100',
                    'bind_price_from' => '10',
                    'bind_price_to' => '100',
                    'bind_weekday_from' => '1',
                    'bind_weekday_to' => '3',
                ],

                'expectedConditions' => [
                    [
                        'type' => 'orContainer',
                        'children' => [
                            [
                                'type' => 'dayOfWeek',
                                'value' => [
                                    'operator' => '=',
                                    'dayOfWeek' => 1,
                                ],
                            ],
                            [
                                'type' => 'dayOfWeek',
                                'value' => [
                                    'operator' => '=',
                                    'dayOfWeek' => 2,
                                ],
                            ],
                            [
                                'type' => 'dayOfWeek',
                                'value' => [
                                    'operator' => '=',
                                    'dayOfWeek' => 3,
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'timeRange',
                        'value' => [
                            'fromTime' => '07:00',
                            'toTime' => '08:00',
                        ],
                    ],
                    [
                        'type' => 'cartLineItemClearanceSale',
                        'value' => [
                            'clearanceSale' => true,
                        ],
                    ],
                    [
                        'type' => 'cartWeight',
                        'value' => [
                            'operator' => '>=',
                            'weight' => 10.0,
                        ],
                    ],
                    [
                        'type' => 'cartWeight',
                        'value' => [
                            'operator' => '<=',
                            'weight' => 100.0,
                        ],
                    ],
                    [
                        'type' => 'cartLineItemTotalPrice',
                        'value' => [
                            'operator' => '>=',
                            'amount' => 10.0,
                        ],
                    ],
                    [
                        'type' => 'cartLineItemTotalPrice',
                        'value' => [
                            'operator' => '<=',
                            'amount' => 100.0,
                        ],
                    ],
                ],
            ],

            'shippingCountries' => [
                'bindValues' => [
                    'shippingCountries' => [
                        [
                            'countryID' => '1',
                            'countryiso' => 'DE',
                            'iso3' => 'DEU',
                        ],
                        [
                            'countryID' => '2',
                            'countryiso' => 'GB',
                            'iso3' => 'GBK',
                        ],
                    ],
                ],

                'expectedConditions' => [
                    [
                        'type' => 'customerShippingCountry',
                        'value' => [
                            'operator' => '=',
                            'countryIds' => [
                                DummyMappingService::DEFAULT_GERMANY_UUID,
                                DummyMappingService::DEFAULT_UK_UUID,
                            ],
                        ],
                    ],
                ],
            ],

            'paymentMethods' => [
                'bindValues' => [
                    'paymentMethods' => [
                        '1',
                        '2',
                        '3',
                    ],
                ],

                'expectedConditions' => [
                    [
                        'type' => 'paymentMethod',
                        'value' => [
                            'operator' => '=',
                            'paymentMethodIds' => [
                                Uuid::randomHex(),
                                Uuid::randomHex(),
                                Uuid::randomHex(),
                            ],
                        ],
                    ],
                ],
            ],

            'excludedCategories' => [
                'bindValues' => [
                    'excludedCategories' => [
                        '1',
                        '2',
                        '3',
                    ],
                ],

                'expectedConditions' => [
                    [
                        'type' => 'cartLineItemInCategory',
                        'value' => [
                            'operator' => '!=',
                            'categoryIds' => [
                                Uuid::randomHex(),
                                Uuid::randomHex(),
                                Uuid::randomHex(),
                            ],
                        ],
                    ],
                ],
            ],

            'freeShipping' => [
                'bindValues' => [
                    'bind_shippingfree' => '1',
                ],

                'expectedConditions' => [
                    [
                        'type' => 'cartHasDeliveryFreeItem',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider conditionDataProvider
     */
    public function testConvertCondition(array $bindValues, array $excpetedConditions): void
    {
        $shippingMethodData = require __DIR__ . '/../../../_fixtures/shipping_method_data.php';

        foreach ($bindValues as $key => $bindValue) {
            $shippingMethodData[0][$key] = $bindValue;
        }

        if (isset($excpetedConditions[0]['type'], $excpetedConditions[0]['value']['paymentMethodIds']) && $excpetedConditions[0]['type'] === 'paymentMethod') {
            $this->mappingService->getOrCreateMapping(
                $this->connection->getId(),
                DefaultEntities::PAYMENT_METHOD,
                '1',
                $this->context,
                null,
                null,
                $excpetedConditions[0]['value']['paymentMethodIds'][0]
            );

            $this->mappingService->getOrCreateMapping(
                $this->connection->getId(),
                DefaultEntities::PAYMENT_METHOD,
                '2',
                $this->context,
                null,
                null,
                $excpetedConditions[0]['value']['paymentMethodIds'][1]
            );

            $this->mappingService->getOrCreateMapping(
                $this->connection->getId(),
                DefaultEntities::PAYMENT_METHOD,
                '3',
                $this->context,
                null,
                null,
                $excpetedConditions[0]['value']['paymentMethodIds'][2]
            );
        }

        if (isset($excpetedConditions[0]['type'], $excpetedConditions[0]['value']['categoryIds']) && $excpetedConditions[0]['type'] === 'cartLineItemInCategory') {
            $this->mappingService->getOrCreateMapping(
                $this->connection->getId(),
                DefaultEntities::CATEGORY,
                '1',
                $this->context,
                null,
                null,
                $excpetedConditions[0]['value']['categoryIds'][0]
            );

            $this->mappingService->getOrCreateMapping(
                $this->connection->getId(),
                DefaultEntities::CATEGORY,
                '2',
                $this->context,
                null,
                null,
                $excpetedConditions[0]['value']['categoryIds'][1]
            );

            $this->mappingService->getOrCreateMapping(
                $this->connection->getId(),
                DefaultEntities::CATEGORY,
                '3',
                $this->context,
                null,
                null,
                $excpetedConditions[0]['value']['categoryIds'][2]
            );
        }

        $convertResult = $this->shippingMethodConverter->convert($shippingMethodData[0], $this->context, $this->migrationContext);
        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($converted);
        static::assertNotNull($convertResult->getMappingUuid());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('availabilityRule', $converted);
        static::assertArrayHasKey('conditions', $converted['availabilityRule']);

        $availabilityRule = $converted['availabilityRule'];
        static::assertArrayHasKey('children', $availabilityRule['conditions'][0]['children'][0]);
        $conditions = $availabilityRule['conditions'][0]['children'][0]['children'];

        foreach ($conditions as &$condition) {
            unset(
                $condition['id'],
                $condition['ruleId'],
                $condition['parentId'],
                $condition['position']
            );

            if (isset($condition['children'])) {
                foreach ($condition['children'] as &$child) {
                    unset(
                        $child['id'],
                        $child['ruleId'],
                        $child['parentId'],
                        $child['position']
                    );
                }
            }
        }

        static::assertSame($excpetedConditions, $conditions);
    }
}
