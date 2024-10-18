<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Mapping;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Mapping\MappingService;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\SwagMigrationMappingCollection;
use SwagMigrationAssistant\Migration\Mapping\SwagMigrationMappingDefinition;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\Premapping\NewsletterRecipientStatusReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use Symfony\Contracts\Service\ResetInterface;

#[Package('services-settings')]
class MappingServiceTest extends TestCase
{
    use IntegrationTestBehaviour;

    private MappingServiceInterface&ResetInterface $mappingService;

    private string $connectionId;

    /**
     * @var EntityRepository<SwagMigrationMappingCollection>
     */
    private EntityRepository $mappingRepo;

    protected function setUp(): void
    {
        $context = Context::createDefaultContext();
        $connectionRepo = $this->getContainer()->get('swag_migration_connection.repository');
        $this->mappingRepo = $this->getContainer()->get('swag_migration_mapping.repository');

        $context->scope(MigrationContext::SOURCE_CONTEXT, function (Context $context) use ($connectionRepo): void {
            $this->connectionId = Uuid::randomHex();
            $connectionRepo->create(
                [
                    [
                        'id' => $this->connectionId,
                        'name' => 'myConnection',
                        'credentialFields' => [
                            'endpoint' => 'testEndpoint',
                            'apiUser' => 'testUser',
                            'apiKey' => 'testKey',
                        ],
                        'profileName' => Shopware55Profile::PROFILE_NAME,
                        'gatewayName' => ShopwareLocalGateway::GATEWAY_NAME,
                    ],
                ],
                $context
            );
        });

        $this->createMappingService();
    }

    public function testGetOrCreateMapping(): void
    {
        $context = Context::createDefaultContext();

        $mapping1 = $this->mappingService->getOrCreateMapping($this->connectionId, 'product', '123', $context);
        static::assertNotNull($mapping1['id']);
        static::assertNotNull($mapping1['entityUuid']);
        static::assertNull($mapping1['entityValue']);

        $mapping2 = $this->mappingService->getOrCreateMapping($this->connectionId, 'product', '123', $context);
        static::assertSame($mapping1, $mapping2);

        $uuid = Uuid::randomHex();
        $additionalData = [
            'key' => 'value',
        ];

        $expectedData = $mapping2;
        $expectedData['entityUuid'] = $uuid;
        $expectedData['additionalData'] = $additionalData;
        $mapping3 = $this->mappingService->getOrCreateMapping($this->connectionId, 'product', '123', $context, null, ['key' => 'value'], $uuid);
        static::assertSame($expectedData, $mapping3);

        $this->mappingService->writeMapping();
    }

    public function testReadExistingMappings(): void
    {
        $context = Context::createDefaultContext();
        $mapping1 = $this->mappingService->getOrCreateMapping($this->connectionId, 'product', 'abc', $context);
        $this->mappingService->writeMapping();

        // reset mapping and DB cache
        $this->clearCacheData();
        $this->createMappingService();

        $mapping2 = $this->mappingService->getOrCreateMapping($this->connectionId, 'product', 'abc', $context);
        static::assertSame($mapping1['id'], $mapping2['id']);
    }

    public function testGetMappingReturnsNull(): void
    {
        $context = Context::createDefaultContext();
        static::assertNull($this->mappingService->getMapping(Uuid::randomHex(), 'product', '12345', $context));
    }

    public function testDeleteMapping(): void
    {
        $context = Context::createDefaultContext();
        $localeCode = 'swagMigrationTestingLocaleCode';

        $languageMapping = $this->mappingService->getOrCreateMapping($this->connectionId, DefaultEntities::LANGUAGE, $localeCode, $context);
        $this->mappingService->writeMapping();
        $this->clearCacheData();
        $this->createMappingService();

        $mapping = $this->mappingService->getMapping($this->connectionId, DefaultEntities::LANGUAGE, $localeCode, $context);
        static::assertNotNull($mapping);
        static::assertSame($languageMapping['id'], $mapping['id']);

        $this->mappingService->getOrCreateMapping($this->connectionId, DefaultEntities::LANGUAGE, 'en-GB', $context);
        $this->mappingService->writeMapping();

        $this->mappingService->deleteMapping((string) $languageMapping['entityUuid'], $this->connectionId, $context);
        $mapping = $this->mappingService->getMapping($this->connectionId, DefaultEntities::LANGUAGE, $localeCode, $context);

        static::assertNull($mapping);
    }

    public function testValueMapping(): void
    {
        $context = Context::createDefaultContext();

        $value = 'unspecified';
        $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            NewsletterRecipientStatusReader::getMappingName(),
            NewsletterRecipientStatusReader::SOURCE_ID,
            $context,
            null,
            null,
            null,
            $value
        );
        $this->mappingService->writeMapping();

        $retrieved1 = $this->mappingService->getValue(
            $this->connectionId,
            NewsletterRecipientStatusReader::getMappingName(),
            NewsletterRecipientStatusReader::SOURCE_ID,
            $context
        );
        static::assertSame($value, $retrieved1);

        // reset the mapping and DB cache
        $this->clearCacheData();
        $this->createMappingService();

        $retrieved2 = $this->mappingService->getValue(
            $this->connectionId,
            NewsletterRecipientStatusReader::getMappingName(),
            NewsletterRecipientStatusReader::SOURCE_ID,
            $context
        );
        static::assertSame($value, $retrieved2);
    }

    public function testPreloadMapping(): void
    {
        $context = Context::createDefaultContext();

        $mapping = $this->mappingService->createMapping(
            $this->connectionId,
            DefaultEntities::PRODUCT,
            '1',
        );
        $value = 'unspecified';
        $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            NewsletterRecipientStatusReader::getMappingName(),
            NewsletterRecipientStatusReader::SOURCE_ID,
            $context,
            null,
            null,
            null,
            $value
        );
        $this->mappingService->writeMapping();

        // fetch mapping ids
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('connectionId', $this->connectionId));
        $mappingIds = $this->mappingRepo->searchIds($criteria, $context)->getIds();
        static::assertCount(2, $mappingIds);

        // reset mapping and DB cache
        $this->clearCacheData();
        $this->createMappingService();
        // populate mapping cache by preloading mappings
        $this->mappingService->preloadMappings($mappingIds, $context);

        $mapping2 = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::PRODUCT,
            '1',
            $context
        );
        static::assertSame($mapping, $mapping2);
        $value2 = $this->mappingService->getValue(
            $this->connectionId,
            NewsletterRecipientStatusReader::getMappingName(),
            NewsletterRecipientStatusReader::SOURCE_ID,
            $context
        );
        static::assertSame($value, $value2);
    }

    /**
     * @param array<int, array<string, string>> $dataset
     */
    #[DataProvider('getMappings')]
    public function testWriteMapping(array $dataset): void
    {
        $this->mappingService->reset();

        foreach ($dataset as $set) {
            $this->mappingService->getOrCreateMapping(
                $this->connectionId,
                $set['entity'],
                $set['oldIdentifier'],
                Context::createDefaultContext(),
                \md5($set['entity'] . $set['oldIdentifier']),
                null,
                Uuid::randomHex(),
                $set['value']
            );
        }

        $mapping = $this->getMapping();
        static::assertCount(\count($dataset), $mapping);

        $this->mappingService->writeMapping();

        $sql = 'SELECT * FROM swag_migration_mapping';
        $result = $this->getContainer()->get(Connection::class)->fetchAllAssociative($sql);

        static::assertCount(\count($dataset), $result);
        foreach ($dataset as $set) {
            foreach ($result as $resultEntry) {
                if ($set['oldIdentifier'] === $resultEntry['old_identifier']) {
                    static::assertSame($set['entity'], $resultEntry['entity']);
                    static::assertSame($set['value'], $resultEntry['entity_value']);
                    static::assertSame(\md5($set['entity'] . $set['oldIdentifier']), $resultEntry['checksum']);
                }
            }
        }

        $shouldEmptyMapping = $this->getMapping();
        static::assertCount(0, $shouldEmptyMapping);

        foreach ($mapping as &$mapped) {
            static::assertIsArray($mapped);
            $mapped['entityValue'] = 'EV_newValue';
        }

        $this->setMappingAndWriteArray($mapping);
        $this->mappingService->writeMapping();

        $result = $this->getContainer()->get(Connection::class)->fetchAllAssociative($sql);

        static::assertCount(\count($dataset), $result);
        foreach ($dataset as $set) {
            foreach ($result as $resultEntry) {
                if ($set['oldIdentifier'] === $resultEntry['old_identifier']) {
                    static::assertSame($set['entity'], $resultEntry['entity']);
                    static::assertSame('EV_newValue', $resultEntry['entity_value']);
                    static::assertSame(\md5($set['entity'] . $set['oldIdentifier']), $resultEntry['checksum']);
                }
            }
        }
    }

    /**
     * @param array<int, array<string, string>> $dataset
     */
    #[DataProvider('getMappings')]
    public function testWritePerEntry(array $dataset): void
    {
        $this->mappingService->reset();

        foreach ($dataset as $set) {
            $this->mappingService->getOrCreateMapping(
                $this->connectionId,
                $set['entity'],
                $set['oldIdentifier'],
                Context::createDefaultContext(),
                \md5($set['entity'] . $set['oldIdentifier']),
                null,
                Uuid::randomHex(),
                $set['value']
            );
        }

        $mapping = $this->getMapping();
        static::assertCount(\count($dataset), $mapping);

        $reflectionMethod = (new \ReflectionClass(MappingService::class))->getMethod('writePerEntry');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($this->mappingService);

        $sql = 'SELECT * FROM swag_migration_mapping';
        $result = $this->getContainer()->get(Connection::class)->fetchAllAssociative($sql);

        static::assertCount(\count($dataset), $result);
        foreach ($dataset as $set) {
            foreach ($result as $resultEntry) {
                if ($set['oldIdentifier'] === $resultEntry['old_identifier']) {
                    static::assertSame($set['entity'], $resultEntry['entity']);
                    static::assertSame($set['value'], $resultEntry['entity_value']);
                    static::assertSame(\md5($set['entity'] . $set['oldIdentifier']), $resultEntry['checksum']);
                }
            }
        }

        foreach ($mapping as &$mapped) {
            $mapped['entityValue'] = 'EV_newValue';
        }

        $this->setMappingAndWriteArray($mapping);
        $reflectionMethod->invoke($this->mappingService);

        $result = $this->getContainer()->get(Connection::class)->fetchAllAssociative($sql);

        static::assertCount(\count($dataset), $result);
        foreach ($dataset as $set) {
            foreach ($result as $resultEntry) {
                if ($set['oldIdentifier'] === $resultEntry['old_identifier']) {
                    static::assertSame($set['entity'], $resultEntry['entity']);
                    static::assertSame('EV_newValue', $resultEntry['entity_value']);
                    static::assertSame(\md5($set['entity'] . $set['oldIdentifier']), $resultEntry['checksum']);
                }
            }
        }
    }

    /**
     * @return array<int, array<string, array<int, array<string, string>>>>
     */
    public static function getMappings(): array
    {
        return [
            [
                'dataset' => [
                    ['entity' => DefaultEntities::PRODUCT, 'oldIdentifier' => 'PRODUCT_OI_1', 'value' => 'PRODUCT_VALUE_1'],
                    ['entity' => DefaultEntities::PRODUCT, 'oldIdentifier' => 'PRODUCT_OI_2', 'value' => 'PRODUCT_VALUE_2'],
                    ['entity' => DefaultEntities::PRODUCT, 'oldIdentifier' => 'PRODUCT_OI_3', 'value' => 'PRODUCT_VALUE_3'],
                ],
            ],
            [
                'dataset' => [
                    ['entity' => DefaultEntities::CATEGORY, 'oldIdentifier' => 'CATEGORY_OI_1', 'value' => 'CATEGORY_VALUE_1'],
                    ['entity' => DefaultEntities::CATEGORY, 'oldIdentifier' => 'CATEGORY_OI_2', 'value' => 'CATEGORY_VALUE_2'],
                    ['entity' => DefaultEntities::CATEGORY, 'oldIdentifier' => 'CATEGORY_OI_3', 'value' => 'CATEGORY_VALUE_3'],
                ],
            ],
            [
                'dataset' => [
                    ['entity' => DefaultEntities::PRODUCT_MANUFACTURER, 'oldIdentifier' => 'MANUFACTURER_OI_1', 'value' => 'MANUFACTURER_VALUE_1'],
                    ['entity' => DefaultEntities::PRODUCT_MANUFACTURER, 'oldIdentifier' => 'MANUFACTURER_OI_2', 'value' => 'MANUFACTURER_VALUE_2'],
                    ['entity' => DefaultEntities::PRODUCT_MANUFACTURER, 'oldIdentifier' => 'MANUFACTURER_OI_3', 'value' => 'MANUFACTURER_VALUE_3'],
                ],
            ],
            [
                'dataset' => [
                    ['entity' => DefaultEntities::PRODUCT, 'oldIdentifier' => 'PRODUCT_OI_1', 'value' => 'PRODUCT_VALUE_1'],
                    ['entity' => DefaultEntities::PRODUCT, 'oldIdentifier' => 'PRODUCT_OI_2', 'value' => 'PRODUCT_VALUE_2'],
                    ['entity' => DefaultEntities::PRODUCT, 'oldIdentifier' => 'PRODUCT_OI_3', 'value' => 'PRODUCT_VALUE_3'],
                    ['entity' => DefaultEntities::CATEGORY, 'oldIdentifier' => 'CATEGORY_OI_1', 'value' => 'CATEGORY_VALUE_1'],
                    ['entity' => DefaultEntities::CATEGORY, 'oldIdentifier' => 'CATEGORY_OI_2', 'value' => 'CATEGORY_VALUE_2'],
                    ['entity' => DefaultEntities::CATEGORY, 'oldIdentifier' => 'CATEGORY_OI_3', 'value' => 'CATEGORY_VALUE_3'],
                    ['entity' => DefaultEntities::PRODUCT_MANUFACTURER, 'oldIdentifier' => 'MANUFACTURER_OI_1', 'value' => 'MANUFACTURER_VALUE_1'],
                    ['entity' => DefaultEntities::PRODUCT_MANUFACTURER, 'oldIdentifier' => 'MANUFACTURER_OI_2', 'value' => 'MANUFACTURER_VALUE_2'],
                    ['entity' => DefaultEntities::PRODUCT_MANUFACTURER, 'oldIdentifier' => 'MANUFACTURER_OI_3', 'value' => 'MANUFACTURER_VALUE_3'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function getMapping(): array
    {
        $reflectionProperty = $this->getMappingPropperty();

        return $reflectionProperty->getValue($this->mappingService);
    }

    /**
     * @param array<array<string, string>> $mapping
     */
    private function setMappingAndWriteArray(array $mapping): void
    {
        $reflectionProperty = $this->getMappingPropperty();
        $reflectionProperty->setValue($this->mappingService, $mapping);

        $reflectionProperty = $this->getWriteArrayPropperty();
        $reflectionProperty->setValue($this->mappingService, \array_values($mapping));
    }

    private function getMappingPropperty(): \ReflectionProperty
    {
        $reflectionProperty = (new \ReflectionClass(MappingService::class))->getProperty('mappings');
        $reflectionProperty->setAccessible(true);

        return $reflectionProperty;
    }

    private function getWriteArrayPropperty(): \ReflectionProperty
    {
        $reflectionProperty = (new \ReflectionClass(MappingService::class))->getProperty('writeArray');
        $reflectionProperty->setAccessible(true);

        return $reflectionProperty;
    }

    private function createMappingService(): void
    {
        $this->mappingService = new MappingService(
            $this->mappingRepo,
            $this->getContainer()->get(EntityWriter::class),
            $this->getContainer()->get(SwagMigrationMappingDefinition::class),
            $this->getContainer()->get(Connection::class),
            new NullLogger()
        );
    }
}
