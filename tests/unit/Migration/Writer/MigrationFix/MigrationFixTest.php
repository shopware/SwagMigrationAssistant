<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\unit\Migration\Writer\MigrationFix;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Writer\MigrationFix\MigrationFix;

#[Package('after-sales')]
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
        $this->expectException(MigrationException::class);
        $this->expectExceptionMessage(\sprintf('Missing key "%s" to construct MigrationFix.', $expectedMissingKey));

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
                    'main_mapping_id' => 'anyMappingId',
                    'value' => json_encode('anyValue', \JSON_THROW_ON_ERROR),
                ],
                'expectedMissingKey' => 'path',
            ],
        ];
    }
}
