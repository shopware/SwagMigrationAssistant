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
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Writer\MigrationFix\MigrationFix;
use SwagMigrationAssistant\Migration\Writer\MigrationFix\MigrationFixMapping;

#[Package('after-sales')]
class MigrationFixMappingTest extends TestCase
{
    public function testCreateFromDatabaseQuery(): void
    {
        $data = [
            'id' => 'anyIdentifier',
            'connection_id' => 'anyConnectionIdentifier',
            'entity' => 'anyEntity',
            'old_identifier' => 'anyOldIdentifier',
            'entity_uuid' => 'anyEntityUuid',
            'entity_value' => 'anyEntityValue',
            'checksum' => 'anyChecksum',
            'additional_data' => 'anyAdditionalData',
        ];

        $migrationFixMapping = MigrationFixMapping::fromDatabaseQuery($data);
        static::assertSame($data['id'], $migrationFixMapping->id);
        static::assertSame($data['connection_id'], $migrationFixMapping->connectionId);
        static::assertSame($data['entity'], $migrationFixMapping->entity);
        static::assertSame($data['old_identifier'], $migrationFixMapping->oldIdentifier);
        static::assertSame($data['entity_uuid'], $migrationFixMapping->entityUuid);
        static::assertSame($data['entity_value'], $migrationFixMapping->entityValue);
        static::assertSame($data['checksum'], $migrationFixMapping->checksum);
        static::assertSame($data['additional_data'], $migrationFixMapping->additionalData);
    }

    /**
     * @param array<string, string> $data
     */
    #[DataProvider('dataWithMissingKeys')]
    public function testCreateFromDatabaseQueryWithErrors(array $data, string $expectedMissingKey): void
    {
        $this->expectException(MigrationException::class);
        $this->expectExceptionMessage(\sprintf('Missing key "%s" to construct MigrationFixMapping.', $expectedMissingKey));

        MigrationFixMapping::fromDatabaseQuery($data);
    }

    /**
     * @return array<string, array<string, array<string, string>|string>>
     */
    public static function dataWithMissingKeys(): array
    {
        return [
            'id is missing' => [
                'data' => [
                    'connection_id' => 'anyConnectionIdentifier',
                    'entity' => 'anyEntity',
                    'old_identifier' => 'anyOldIdentifier',
                    'entity_uuid' => 'anyEntityUuid',
                    'entity_value' => 'anyEntityValue',
                    'checksum' => 'anyChecksum',
                    'additional_data' => 'anyAdditionalData',
                ],
                'expectedMissingKey' => 'id',
            ],
            'connection_id is missing' => [
                'data' => [
                    'id' => 'anyIdentifier',
                    'entity' => 'anyEntity',
                    'old_identifier' => 'anyOldIdentifier',
                    'entity_uuid' => 'anyEntityUuid',
                    'entity_value' => 'anyEntityValue',
                    'checksum' => 'anyChecksum',
                    'additional_data' => 'anyAdditionalData',
                ],
                'expectedMissingKey' => 'connection_id',
            ],
            'entity is missing' => [
                'data' => [
                    'id' => 'anyIdentifier',
                    'connection_id' => 'anyConnectionIdentifier',
                    'old_identifier' => 'anyOldIdentifier',
                    'entity_uuid' => 'anyEntityUuid',
                    'entity_value' => 'anyEntityValue',
                    'checksum' => 'anyChecksum',
                    'additional_data' => 'anyAdditionalData',
                ],
                'expectedMissingKey' => 'entity',
            ],
            'old_identifier is missing' => [
                'data' => [
                    'id' => 'anyIdentifier',
                    'connection_id' => 'anyConnectionIdentifier',
                    'entity' => 'anyEntity',
                    'entity_uuid' => 'anyEntityUuid',
                    'entity_value' => 'anyEntityValue',
                    'checksum' => 'anyChecksum',
                    'additional_data' => 'anyAdditionalData',
                ],
                'expectedMissingKey' => 'old_identifier',
            ],
            'entity_uuid is missing' => [
                'data' => [
                    'id' => 'anyIdentifier',
                    'connection_id' => 'anyConnectionIdentifier',
                    'entity' => 'anyEntity',
                    'old_identifier' => 'anyOldIdentifier',
                    'entity_value' => 'anyEntityValue',
                    'checksum' => 'anyChecksum',
                    'additional_data' => 'anyAdditionalData',
                ],
                'expectedMissingKey' => 'entity_uuid',
            ],
            'entity_value is missing' => [
                'data' => [
                    'id' => 'anyIdentifier',
                    'connection_id' => 'anyConnectionIdentifier',
                    'entity' => 'anyEntity',
                    'old_identifier' => 'anyOldIdentifier',
                    'entity_uuid' => 'anyEntityUuid',
                    'checksum' => 'anyChecksum',
                    'additional_data' => 'anyAdditionalData',
                ],
                'expectedMissingKey' => 'entity_value',
            ],
            'checksum is missing' => [
                'data' => [
                    'id' => 'anyIdentifier',
                    'connection_id' => 'anyConnectionIdentifier',
                    'entity' => 'anyEntity',
                    'old_identifier' => 'anyOldIdentifier',
                    'entity_uuid' => 'anyEntityUuid',
                    'entity_value' => 'anyEntityValue',
                    'additional_data' => 'anyAdditionalData',
                ],
                'expectedMissingKey' => 'checksum',
            ],
            'additional_data is missing' => [
                'data' => [
                    'id' => 'anyIdentifier',
                    'connection_id' => 'anyConnectionIdentifier',
                    'entity' => 'anyEntity',
                    'old_identifier' => 'anyOldIdentifier',
                    'entity_uuid' => 'anyEntityUuid',
                    'entity_value' => 'anyEntityValue',
                    'checksum' => 'anyChecksum',
                ],
                'expectedMissingKey' => 'additional_data',
            ],
        ];
    }

    public function testAddMigrationFix(): void
    {
        $mappingFix = MigrationFixMapping::fromDatabaseQuery([
            'id' => 'anyIdentifier',
            'connection_id' => 'anyConnectionIdentifier',
            'entity' => 'anyEntity',
            'old_identifier' => 'anyOldIdentifier',
            'entity_uuid' => 'anyEntityUuid',
            'entity_value' => 'anyEntityValue',
            'checksum' => 'anyChecksum',
            'additional_data' => 'anyAdditionalData',
        ]);

        static::assertFalse($mappingFix->hasFix);
        static::assertCount(0, $mappingFix->migrationFixes);

        $mappingFix->addMigrationFix(
            MigrationFix::fromDatabaseQuery([
                'id' => 'anyIdentifier',
                'connection_id' => 'anyConnectionIdentifier',
                'main_mapping_id' => 'anyMappingId',
                'value' => json_encode('anyValue', \JSON_THROW_ON_ERROR),
                'path' => 'any.path',
            ])
        );

        static::assertTrue($mappingFix->hasFix);
        static::assertCount(1, $mappingFix->migrationFixes);

        $mappingFix->addMigrationFix(
            MigrationFix::fromDatabaseQuery([
                'id' => 'anyIdentifier',
                'connection_id' => 'anyConnectionIdentifier',
                'main_mapping_id' => 'anyMappingId',
                'value' => json_encode('anyValue', \JSON_THROW_ON_ERROR),
                'path' => 'any.path',
            ])
        );

        static::assertTrue($mappingFix->hasFix);
        static::assertCount(2, $mappingFix->migrationFixes);
    }

    public function testApplyFixes(): void
    {
        $mappingFix = MigrationFixMapping::fromDatabaseQuery([
            'id' => 'anyIdentifier',
            'connection_id' => 'anyConnectionIdentifier',
            'entity' => 'anyEntity',
            'old_identifier' => 'anyOldIdentifier',
            'entity_uuid' => 'anyEntityUuid',
            'entity_value' => 'anyEntityValue',
            'checksum' => 'anyChecksum',
            'additional_data' => 'anyAdditionalData',
        ]);

        $mappingFix->addMigrationFix(
            MigrationFix::fromDatabaseQuery([
                'id' => 'anyIdentifier',
                'connection_id' => 'anyConnectionIdentifier',
                'main_mapping_id' => 'anyMappingId',
                'value' => json_encode('newValueOne', \JSON_THROW_ON_ERROR),
                'path' => 'any.path',
            ])
        );

        $mappingFix->addMigrationFix(
            MigrationFix::fromDatabaseQuery([
                'id' => 'anyIdentifier',
                'connection_id' => 'anyConnectionIdentifier',
                'main_mapping_id' => 'anyMappingId',
                'value' => json_encode('newValueTwo', \JSON_THROW_ON_ERROR),
                'path' => 'any.other.path',
            ])
        );

        $item = [
            'id' => 'anyIdentifier',
            'any' => ['path' => 'anyOldValue', 'other' => ['path' => 'anyOtherOldValue']],
        ];

        $mappingFix->applyFixes($item);

        static::assertSame('newValueOne', $item['any']['path']);
        static::assertSame('newValueTwo', $item['any']['other']['path']);
    }
}
