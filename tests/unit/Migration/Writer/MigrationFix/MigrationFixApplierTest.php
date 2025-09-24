<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\unit\Migration\Writer\MigrationFix;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\TestCase;
use SwagMigrationAssistant\Migration\Writer\MigrationFix\MigrationFixApplier;

class MigrationFixApplierTest extends TestCase
{
    public function testApply(): void
    {
        $expected = 'newValue';

        $mappings = [
            '1' => [
                'id' => '1',
                'connection_id' => '1',
                'entity' => 'test',
                'old_identifier' => 'test',
                'entity_uuid' => '1',
                'entity_value' => 'test',
                'checksum' => 'test',
                'additional_data' => '',
            ],
            '2' => [
                'id' => '2',
                'connection_id' => '1',
                'entity' => 'test',
                'old_identifier' => 'test2',
                'entity_uuid' => '2',
                'entity_value' => 'test',
                'checksum' => 'test',
                'additional_data' => '',
            ],
            '3' => [
                'id' => '3',
                'connection_id' => '1',
                'entity' => 'test',
                'old_identifier' => 'test2',
                'entity_uuid' => '3',
                'entity_value' => 'test',
                'checksum' => 'test',
                'additional_data' => '',
            ],
            '4' => [
                'id' => '4',
                'connection_id' => '1',
                'entity' => 'test',
                'old_identifier' => 'test2',
                'entity_uuid' => '4',
                'entity_value' => 'test',
                'checksum' => 'test',
                'additional_data' => '',
            ],
        ];

        $fixes = [
            [
                'id' => '2',
                'connection_id' => '1',
                'main_mapping_id' => '1',
                'value' => \json_encode($expected, \JSON_THROW_ON_ERROR),
                'path' => 'other.path.to.value',
            ],
            [
                'id' => '3',
                'connection_id' => '1',
                'main_mapping_id' => '1',
                'value' => \json_encode($expected, \JSON_THROW_ON_ERROR),
                'path' => 'the.path.to.value',
            ],
            [
                'id' => '4',
                'connection_id' => '1',
                'main_mapping_id' => '2',
                'value' => \json_encode($expected, \JSON_THROW_ON_ERROR),
                'path' => 'the.path.to.value',
            ],
            [
                'id' => '5',
                'connection_id' => '1',
                'main_mapping_id' => '3',
                'value' => \json_encode($expected, \JSON_THROW_ON_ERROR),
                'path' => 'other.path.to.value',
            ],
            [
                'id' => '6',
                'connection_id' => '1',
                'main_mapping_id' => '4',
                'value' => \json_encode($expected, \JSON_THROW_ON_ERROR),
                'path' => 'path.to.nested',
            ],
            [
                'id' => '7',
                'connection_id' => '1',
                'main_mapping_id' => '4',
                'value' => \json_encode($expected, \JSON_THROW_ON_ERROR),
                'path' => 'path.to.other.nested',
            ],
            [
                'id' => '8',
                'connection_id' => '1',
                'main_mapping_id' => '4',
                'value' => \json_encode($expected, \JSON_THROW_ON_ERROR),
                'path' => 'path.value',
            ],
        ];

        $data = [
            0 => [
                'id' => '1',
                'name' => 'test',
                'the' => ['path' => ['to' => ['value' => 'oldValue']]],
                'other' => ['path' => ['to' => ['value' => 'oldValue']]],
            ],
            1 => [
                'id' => '2',
                'name' => 'test',
                'the' => ['path' => ['to' => ['value' => 'oldValue']]],
                'other' => ['path' => ['to' => ['value' => 'oldValue']]],
            ],
            2 => [
                'id' => '3',
                'name' => 'test',
                'the' => ['path' => ['to' => ['value' => 'oldValue']]],
                'other' => ['path' => ['to' => ['value' => 'oldValue']]],
            ],
            3 => [
                'id' => '4',
                'name' => 'test',
                'untouchedKey' => 'untouchedValue',
                'path' => [
                    'untouchedKey' => 'untouchedValue',
                    'value' => 'oldValue',
                    'to' => [
                        'untouchedKey' => 'untouchedValue',
                        'nested' => 'oldValue',
                        'other' => [
                            'untouchedKey' => 'untouchedValue',
                            'nested' => 'oldValue',
                        ],
                    ],
                ],
            ],
        ];

        $migrationFixApplier = new MigrationFixApplier($this->createConnection($mappings, $fixes));

        $migrationFixApplier->apply($data, '1');

        static::assertSame($expected, $data[0]['the']['path']['to']['value']);
        static::assertSame($expected, $data[0]['other']['path']['to']['value']);

        static::assertSame($expected, $data[1]['the']['path']['to']['value']);

        static::assertSame('oldValue', $data[2]['the']['path']['to']['value']);
        static::assertSame($expected, $data[2]['other']['path']['to']['value']);

        static::assertSame($expected, $data[3]['path']['value']);
        static::assertSame($expected, $data[3]['path']['value']);
        static::assertSame($expected, $data[3]['path']['to']['nested']);
        static::assertSame($expected, $data[3]['path']['to']['other']['nested']);

        // It is important to check other values in the path to ensure that they have not been changed.
        static::assertSame('untouchedValue', $data[3]['untouchedKey']);
        static::assertSame('untouchedValue', $data[3]['path']['untouchedKey']);
        static::assertSame('untouchedValue', $data[3]['path']['to']['untouchedKey']);
        static::assertSame('untouchedValue', $data[3]['path']['to']['other']['untouchedKey']);
    }

    /**
     * @param array<int, array<string, string>> $mappings
     * @param array<int, array<string, string>> $fixes
     */
    private function createConnection(array $mappings, array $fixes): Connection
    {
        $resultMock = $this->createMock(Result::class);
        $resultMock->method('fetchAllAssociativeIndexed')->willReturn($mappings);
        $resultMock->method('fetchAllAssociative')->willReturn($fixes);

        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $queryBuilderMock->method('select')->willReturnSelf();
        $queryBuilderMock->method('from')->willReturnSelf();
        $queryBuilderMock->method('where')->willReturnSelf();
        $queryBuilderMock->method('andWhere')->willReturnSelf();
        $queryBuilderMock->method('setParameter')->willReturnSelf();
        $queryBuilderMock->method('executeQuery')->willReturn($resultMock);

        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->method('createQueryBuilder')->willReturn($queryBuilderMock);

        return $connectionMock;
    }
}
