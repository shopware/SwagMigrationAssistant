<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\unit\Migration\ErrorResolution;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\ErrorResolution\MigrationFix;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
#[CoversClass(MigrationFix::class)]
class MigrationFixTest extends TestCase
{
    public function testApplyFix(): void
    {
        $expectedValue = 'This is the new Value';

        $fix = new MigrationFix(
            'anyId',
            \json_encode($expectedValue, \JSON_THROW_ON_ERROR),
            'path.to.the.value.which.needs.to.be.replaced',
        );

        $item = [
            'id' => 'anyId',
            'doNotTouch' => 'untouchedValue',
            'path' => [
                'doNotTouch' => 'untouchedValue',
                'to' => [
                    'doNotTouch' => 'untouchedValue',
                    'the' => [
                        'doNotTouch' => 'untouchedValue',
                        'value' => [
                            'doNotTouch' => 'untouchedValue',
                            'which' => [
                                'doNotTouch' => 'untouchedValue',
                                'needs' => [
                                    'doNotTouch' => 'untouchedValue',
                                    'to' => [
                                        'doNotTouch' => 'untouchedValue',
                                        'be' => [
                                            'doNotTouch' => 'untouchedValue',
                                            'replaced' => 'anyOldValue',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $fix->apply($item);

        static::assertSame($expectedValue, $item['path']['to']['the']['value']['which']['needs']['to']['be']['replaced']);

        // Check other nested values are not affected
        static::assertSame('untouchedValue', $item['doNotTouch']);
        static::assertSame('untouchedValue', $item['path']['doNotTouch']);
        static::assertSame('untouchedValue', $item['path']['to']['doNotTouch']);
        static::assertSame('untouchedValue', $item['path']['to']['the']['doNotTouch']);
        static::assertSame('untouchedValue', $item['path']['to']['the']['value']['doNotTouch']);
        static::assertSame('untouchedValue', $item['path']['to']['the']['value']['which']['doNotTouch']);
        static::assertSame('untouchedValue', $item['path']['to']['the']['value']['which']['needs']['doNotTouch']);
        static::assertSame('untouchedValue', $item['path']['to']['the']['value']['which']['needs']['to']['doNotTouch']);
        static::assertSame('untouchedValue', $item['path']['to']['the']['value']['which']['needs']['to']['be']['doNotTouch']);
    }

    public function testApplyFixToArrayAssociation(): void
    {
        $expectedValue = 'fixedTypeId';

        $fix = new MigrationFix(
            'anyId',
            \json_encode($expectedValue, \JSON_THROW_ON_ERROR),
            'numberRangeSalesChannels.numberRangeTypeId',
        );

        $item = [
            'id' => 'anyId',
            'name' => 'Order Number',
            'numberRangeSalesChannels' => [
                [
                    'id' => 'channel1',
                    'numberRangeTypeId' => null,
                    'salesChannelId' => 'sc1',
                ],
                [
                    'id' => 'channel2',
                    'numberRangeTypeId' => null,
                    'salesChannelId' => 'sc2',
                ],
                [
                    'id' => 'channel3',
                    'numberRangeTypeId' => 'existingValue',
                    'salesChannelId' => 'sc3',
                ],
            ],
        ];

        $fix->apply($item);

        // All array items should have the fix applied
        static::assertSame($expectedValue, $item['numberRangeSalesChannels'][0]['numberRangeTypeId']);
        static::assertSame($expectedValue, $item['numberRangeSalesChannels'][1]['numberRangeTypeId']);
        static::assertSame($expectedValue, $item['numberRangeSalesChannels'][2]['numberRangeTypeId']);

        // Other fields should remain untouched
        static::assertSame('Order Number', $item['name']);
        static::assertSame('channel1', $item['numberRangeSalesChannels'][0]['id']);
        static::assertSame('sc1', $item['numberRangeSalesChannels'][0]['salesChannelId']);
        static::assertSame('channel2', $item['numberRangeSalesChannels'][1]['id']);
        static::assertSame('sc2', $item['numberRangeSalesChannels'][1]['salesChannelId']);
    }

    public function testApplyFixToEmptyArray(): void
    {
        $fix = new MigrationFix(
            'anyId',
            \json_encode('fixedValue', \JSON_THROW_ON_ERROR),
            'items.fieldName',
        );

        $item = [
            'id' => 'anyId',
            'items' => [],
        ];

        // Should not crash when array is empty
        $fix->apply($item);

        static::assertSame([], $item['items']);
    }

    public function testApplyFixToDeeplyNestedArrays(): void
    {
        $expectedValue = 'deepFixedValue';

        $fix = new MigrationFix(
            'anyId',
            \json_encode($expectedValue, \JSON_THROW_ON_ERROR),
            'categories.children.name',
        );

        $item = [
            'id' => 'anyId',
            'categories' => [
                [
                    'id' => 'cat1',
                    'children' => [
                        ['id' => 'child1', 'name' => null],
                        ['id' => 'child2', 'name' => null],
                    ],
                ],
                [
                    'id' => 'cat2',
                    'children' => [
                        ['id' => 'child3', 'name' => 'existingName'],
                    ],
                ],
            ],
        ];

        $fix->apply($item);

        // All nested array items should have the fix applied
        static::assertSame($expectedValue, $item['categories'][0]['children'][0]['name']);
        static::assertSame($expectedValue, $item['categories'][0]['children'][1]['name']);
        static::assertSame($expectedValue, $item['categories'][1]['children'][0]['name']);

        // Other fields should remain untouched
        static::assertSame('cat1', $item['categories'][0]['id']);
        static::assertSame('cat2', $item['categories'][1]['id']);
        static::assertSame('child1', $item['categories'][0]['children'][0]['id']);
    }

    public function testApplyFixToMixedAssociativeAndArrayPaths(): void
    {
        $expectedValue = 'mixedPathValue';

        $fix = new MigrationFix(
            'anyId',
            \json_encode($expectedValue, \JSON_THROW_ON_ERROR),
            'product.prices.currencyId',
        );

        $item = [
            'id' => 'anyId',
            'product' => [
                'id' => 'prod1',
                'name' => 'Test Product',
                'prices' => [
                    ['id' => 'price1', 'currencyId' => null, 'gross' => 100],
                    ['id' => 'price2', 'currencyId' => null, 'gross' => 200],
                ],
            ],
        ];

        $fix->apply($item);

        // Fix should be applied to all price items
        static::assertSame($expectedValue, $item['product']['prices'][0]['currencyId']);
        static::assertSame($expectedValue, $item['product']['prices'][1]['currencyId']);

        // Other fields should remain untouched
        static::assertSame('prod1', $item['product']['id']);
        static::assertSame('Test Product', $item['product']['name']);
        static::assertSame(100, $item['product']['prices'][0]['gross']);
        static::assertSame(200, $item['product']['prices'][1]['gross']);
    }

    public function testApplyFixCreatesPathIfNotExists(): void
    {
        $expectedValue = 'newValue';

        $fix = new MigrationFix(
            'anyId',
            \json_encode($expectedValue, \JSON_THROW_ON_ERROR),
            'new.path.field',
        );

        $item = [
            'id' => 'anyId',
            'existingField' => 'existingValue',
        ];

        $fix->apply($item);

        static::assertSame($expectedValue, $item['new']['path']['field']);
        static::assertSame('existingValue', $item['existingField']);
    }

    public function testApplyFixWithArrayValue(): void
    {
        $expectedValue = ['id1', 'id2', 'id3'];

        $fix = new MigrationFix(
            'anyId',
            \json_encode($expectedValue, \JSON_THROW_ON_ERROR),
            'tags',
        );

        $item = [
            'id' => 'anyId',
            'tags' => [],
        ];

        $fix->apply($item);

        static::assertSame($expectedValue, $item['tags']);
    }

    public function testCreateFromDatabaseQuery(): void
    {
        $data = [
            'id' => Uuid::randomBytes(),
            'value' => json_encode('anyValue', \JSON_THROW_ON_ERROR),
            'path' => 'any.path',
        ];

        $migrationFix = MigrationFix::fromDatabaseQuery($data);

        static::assertSame(Uuid::fromBytesToHex($data['id']), $migrationFix->id);
        static::assertSame($data['value'], $migrationFix->value);
        static::assertSame($data['path'], $migrationFix->path);
    }

    /**
     * @param array<string, string> $data
     */
    #[DataProvider('dataWithMissingKeys')]
    public function testCreateFromDatabaseQueryWithErrors(array $data, string $expectedMissingKey): void
    {
        $this->expectExceptionObject(MigrationException::couldNotConvertFix($expectedMissingKey));

        MigrationFix::fromDatabaseQuery($data);
    }

    /**
     * @return array<string, array<string, array<string, string>|string>>
     */
    public static function dataWithMissingKeys(): array
    {
        return [
            'id is missing' => [
                'data' => [
                    'value' => json_encode('anyValue', \JSON_THROW_ON_ERROR),
                    'path' => 'any.path',
                ],
                'expectedMissingKey' => 'id',
            ],
            'value is missing' => [
                'data' => [
                    'id' => 'anyIdentifier',
                    'path' => 'any.path',
                ],
                'expectedMissingKey' => 'value',
            ],
            'path is missing' => [
                'data' => [
                    'id' => 'anyIdentifier',
                    'connection_id' => 'anyConnectionIdentifier',
                    'entity_id' => 'anyEntityId',
                    'value' => json_encode('anyValue', \JSON_THROW_ON_ERROR),
                ],
                'expectedMissingKey' => 'path',
            ],
        ];
    }
}
