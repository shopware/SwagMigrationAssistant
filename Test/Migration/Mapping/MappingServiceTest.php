<?php declare(strict_types=1);

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
            $this->getContainer()->get('sales_channel.repository'),
            $this->getContainer()->get('sales_channel_type.repository'),
            $this->getContainer()->get('payment_method.repository'),
            $this->getContainer()->get('shipping_method.repository'),
            $this->getContainer()->get('tax.repository'),
            $this->getContainer()->get('number_range.repository'),
            $this->getContainer()->get('rule.repository'),
            $this->getContainer()->get('media_thumbnail_size.repository'),
            $this->getContainer()->get('media_default_folder.repository'),
            $this->getContainer()->get('category.repository'),
            $this->getContainer()->get('cms_page.repository'),
            $this->entityWriter,
            $this->getContainer()->get(SwagMigrationMappingDefinition::class)
        );
    }

    public function testCreateNewUuid(): void
    {
        $context = Context::createDefaultContext();

        $uuid1 = $this->mappingService->createNewUuid(Uuid::randomHex(), 'product', '123', $context);
        static::assertNotNull($uuid1);

        $uuid2 = $this->mappingService->createNewUuid(Uuid::randomHex(), 'product', '123', $context);
        static::assertSame($uuid1, $uuid2);
    }

    public function testReadExistingMappings(): void
    {
        $context = Context::createDefaultContext();
        $uuid1 = $this->mappingService->createNewUuid($this->connectionId, 'product', '123', $context);

        $this->mappingService->writeMapping($context);
        $this->clearCacheBefore();

        $newMappingService = new MappingService(
            $this->getContainer()->get('swag_migration_mapping.repository'),
            $this->localeRepo,
            $this->getContainer()->get('language.repository'),
            $this->getContainer()->get('country.repository'),
            $this->getContainer()->get('currency.repository'),
            $this->getContainer()->get('sales_channel.repository'),
            $this->getContainer()->get('sales_channel_type.repository'),
            $this->getContainer()->get('payment_method.repository'),
            $this->getContainer()->get('shipping_method.repository'),
            $this->getContainer()->get('tax.repository'),
            $this->getContainer()->get('number_range.repository'),
            $this->getContainer()->get('rule.repository'),
            $this->getContainer()->get('media_thumbnail_size.repository'),
            $this->getContainer()->get('media_default_folder.repository'),
            $this->getContainer()->get('category.repository'),
            $this->getContainer()->get('cms_page.repository'),
            $this->entityWriter,
            $this->getContainer()->get(SwagMigrationMappingDefinition::class)
        );

        $uuid2 = $newMappingService->createNewUuid($this->connectionId, 'product', '123', $context);

        static::assertSame($uuid1, $uuid2);
    }

    public function testGetUuidReturnsNull(): void
    {
        $context = Context::createDefaultContext();
        static::assertNull($this->mappingService->getUuid(Uuid::randomHex(), 'product', '12345', $context));
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
        $languageUuid = $this->mappingService->createNewUuid($this->connectionId, DefaultEntities::LANGUAGE, $localeCode, $context);
        $this->mappingService->writeMapping($context);
        $response = $this->mappingService->getLanguageUuid($this->connectionId, 'en-GB', $context);

        static::assertSame($languageUuid, $response);
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

        $languageUuid = $this->mappingService->createNewUuid($this->connectionId, DefaultEntities::LANGUAGE, $localeCode, $context);
        $this->mappingService->writeMapping($context);
        $this->clearCacheBefore();

        $uuid = $this->mappingService->getUuid($this->connectionId, DefaultEntities::LANGUAGE, $localeCode, $context);
        static::assertSame($languageUuid, $uuid);

        $this->mappingService->createNewUuid($this->connectionId, DefaultEntities::LANGUAGE, 'en-GB', $context);
        $this->mappingService->writeMapping($context);

        $this->mappingService->deleteMapping($languageUuid, $this->connectionId, $context);
        $uuid = $this->mappingService->getUuid($this->connectionId, DefaultEntities::LANGUAGE, $localeCode, $context);

        static::assertNull($uuid);
    }
}
