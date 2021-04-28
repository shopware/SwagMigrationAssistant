<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Mapping;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriter;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Exception\LocaleNotFoundException;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Mapping\MappingService;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\SwagMigrationMappingDefinition;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use Symfony\Component\HttpFoundation\Response;

class MappingServiceTest extends TestCase
{
    use IntegrationTestBehaviour;

    /**
     * @var MappingServiceInterface
     */
    private $mappingService;

    /**
     * @var EntityRepositoryInterface
     */
    private $localeRepo;

    /**
     * @var string
     */
    private $connectionId;

    /**
     * @var EntityWriterInterface
     */
    private $entityWriter;

    protected function setUp(): void
    {
        $context = Context::createDefaultContext();
        $this->entityWriter = $this->getContainer()->get(EntityWriter::class);
        $connectionRepo = $this->getContainer()->get('swag_migration_connection.repository');
        $this->localeRepo = $this->getContainer()->get('locale.repository');

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

        $this->mappingService = new MappingService(
            $this->getContainer()->get('swag_migration_mapping.repository'),
            $this->localeRepo,
            $this->getContainer()->get('language.repository'),
            $this->getContainer()->get('country.repository'),
            $this->getContainer()->get('currency.repository'),
            $this->getContainer()->get('tax.repository'),
            $this->getContainer()->get('number_range.repository'),
            $this->getContainer()->get('rule.repository'),
            $this->getContainer()->get('media_thumbnail_size.repository'),
            $this->getContainer()->get('media_default_folder.repository'),
            $this->getContainer()->get('category.repository'),
            $this->getContainer()->get('cms_page.repository'),
            $this->getContainer()->get('delivery_time.repository'),
            $this->getContainer()->get('document_type.repository'),
            $this->entityWriter,
            $this->getContainer()->get(SwagMigrationMappingDefinition::class)
        );
    }

    public function testGetOrCreateMapping(): void
    {
        $context = Context::createDefaultContext();

        $mapping1 = $this->mappingService->getOrCreateMapping(Uuid::randomHex(), 'product', '123', $context);
        static::assertNotNull($mapping1['id']);
        static::assertNotNull($mapping1['entityUuid']);

        $mapping2 = $this->mappingService->getOrCreateMapping(Uuid::randomHex(), 'product', '123', $context);
        static::assertSame($mapping1, $mapping2);

        $uuid = Uuid::randomHex();
        $additionalData = [
            'key' => 'value',
        ];
        $expectedData = $mapping2;
        $expectedData['entityUuid'] = $uuid;
        $expectedData['additionalData'] = $additionalData;
        $mapping3 = $this->mappingService->getOrCreateMapping(Uuid::randomHex(), 'product', '123', $context, null, ['key' => 'value'], $uuid);
        static::assertSame($expectedData, $mapping3);
    }

    public function testReadExistingMappings(): void
    {
        $context = Context::createDefaultContext();
        $mapping1 = $this->mappingService->getOrCreateMapping($this->connectionId, 'product', 'abc', $context);

        $this->mappingService->writeMapping($context);
        $this->clearCacheData();

        $newMappingService = new MappingService(
            $this->getContainer()->get('swag_migration_mapping.repository'),
            $this->localeRepo,
            $this->getContainer()->get('language.repository'),
            $this->getContainer()->get('country.repository'),
            $this->getContainer()->get('currency.repository'),
            $this->getContainer()->get('tax.repository'),
            $this->getContainer()->get('number_range.repository'),
            $this->getContainer()->get('rule.repository'),
            $this->getContainer()->get('media_thumbnail_size.repository'),
            $this->getContainer()->get('media_default_folder.repository'),
            $this->getContainer()->get('category.repository'),
            $this->getContainer()->get('cms_page.repository'),
            $this->getContainer()->get('delivery_time.repository'),
            $this->getContainer()->get('document_type.repository'),
            $this->entityWriter,
            $this->getContainer()->get(SwagMigrationMappingDefinition::class)
        );

        $mapping2 = $newMappingService->getOrCreateMapping($this->connectionId, 'product', 'abc', $context);

        static::assertSame($mapping1['id'], $mapping2['id']);
    }

    public function testGetMappingReturnsNull(): void
    {
        $context = Context::createDefaultContext();
        static::assertNull($this->mappingService->getMapping(Uuid::randomHex(), 'product', '12345', $context));
    }

    public function testLocaleNotFoundException(): void
    {
        $context = Context::createDefaultContext();

        try {
            $this->mappingService->getLanguageUuid(Uuid::randomHex(), 'swagMigrationTestingLocaleCode', $context);
        } catch (\Exception $e) {
            /* @var LocaleNotFoundException $e */
            static::assertInstanceOf(LocaleNotFoundException::class, $e);
            static::assertSame(Response::HTTP_NOT_FOUND, $e->getStatusCode());
        }
    }

    public function testGetLanguageUuid(): void
    {
        $context = Context::createDefaultContext();
        $localeCode = 'en-GB';

        $this->mappingService->writeMapping($context);
        $languageMapping = $this->mappingService->getOrCreateMapping($this->connectionId, DefaultEntities::LANGUAGE, $localeCode, $context);
        $this->mappingService->writeMapping($context);
        $response = $this->mappingService->getLanguageUuid($this->connectionId, 'en-GB', $context);

        static::assertSame($languageMapping['entityUuid'], $response);
    }

    public function testGetCountryUuidWithNoResult(): void
    {
        $context = Context::createDefaultContext();
        $response = $this->mappingService->getCountryUuid('testId', 'testIso', 'testIso3', Uuid::randomHex(), $context);
        static::assertNull($response);
    }

    public function testDeleteMapping(): void
    {
        $context = Context::createDefaultContext();
        $localeCode = 'swagMigrationTestingLocaleCode';

        $languageMapping = $this->mappingService->getOrCreateMapping($this->connectionId, DefaultEntities::LANGUAGE, $localeCode, $context);
        $this->mappingService->writeMapping($context);
        $this->clearCacheData();

        $mapping = $this->mappingService->getMapping($this->connectionId, DefaultEntities::LANGUAGE, $localeCode, $context);
        static::assertNotNull($mapping);
        static::assertSame($languageMapping['id'], $mapping['id']);

        $this->mappingService->getOrCreateMapping($this->connectionId, DefaultEntities::LANGUAGE, 'en-GB', $context);
        $this->mappingService->writeMapping($context);

        $this->mappingService->deleteMapping($languageMapping['entityUuid'], $this->connectionId, $context);
        $mapping = $this->mappingService->getMapping($this->connectionId, DefaultEntities::LANGUAGE, $localeCode, $context);

        static::assertNull($mapping);
    }
}
