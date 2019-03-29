<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration\Mapping;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Language\LanguageDefinition;
use SwagMigrationNext\Exception\LocaleNotFoundException;
use SwagMigrationNext\Migration\Mapping\MappingService;
use SwagMigrationNext\Migration\Mapping\MappingServiceInterface;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Test\Migration\Services\MigrationProfileUuidService;
use Symfony\Component\HttpFoundation\Response;

class MappingServiceTest extends TestCase
{
    use IntegrationTestBehaviour;

    /**
     * @var MappingServiceInterface
     */
    private $mappingService;

    /**
     * @var MigrationProfileUuidService
     */
    private $profileUuidService;

    /**
     * @var EntityRepositoryInterface
     */
    private $localeRepo;

    /**
     * @var string
     */
    private $connectionId;

    protected function setUp(): void
    {
        $context = Context::createDefaultContext();
        $connectionRepo = $this->getContainer()->get('swag_migration_connection.repository');
        $this->localeRepo = $this->getContainer()->get('locale.repository');
        $this->profileUuidService = new MigrationProfileUuidService($this->getContainer()->get('swag_migration_profile.repository'));

        $context->scope(MigrationContext::SOURCE_CONTEXT, function (Context $context) use ($connectionRepo) {
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
                        'profileId' => $this->profileUuidService->getProfileUuid(),
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
            $this->getContainer()->get('payment_method.repository')
        );
    }

    public function testCreateNewUuid(): void
    {
        $context = Context::createDefaultContext();

        $uuid1 = $this->mappingService->createNewUuid($this->profileUuidService->getProfileUuid(), 'product', '123', $context);
        static::assertNotNull($uuid1);

        $uuid2 = $this->mappingService->createNewUuid($this->profileUuidService->getProfileUuid(), 'product', '123', $context);
        static::assertSame($uuid1, $uuid2);
    }

    public function testReadExistingMappings(): void
    {
        $context = Context::createDefaultContext();
        $uuid1 = $this->mappingService->createNewUuid($this->connectionId, 'product', '123', $context);

        $this->mappingService->writeMapping($context);

        $newMappingService = new MappingService(
            $this->getContainer()->get('swag_migration_mapping.repository'),
            $this->localeRepo,
            $this->getContainer()->get('language.repository'),
            $this->getContainer()->get('country.repository'),
            $this->getContainer()->get('currency.repository'),
            $this->getContainer()->get('sales_channel.repository'),
            $this->getContainer()->get('sales_channel_type.repository'),
            $this->getContainer()->get('payment_method.repository')
        );

        $uuid2 = $newMappingService->createNewUuid($this->connectionId, 'product', '123', $context);

        static::assertSame($uuid1, $uuid2);
    }

    public function testGetUuidReturnsNull(): void
    {
        $context = Context::createDefaultContext();
        static::assertNull($this->mappingService->getUuid($this->profileUuidService->getProfileUuid(), 'product', '12345', $context));
    }

    public function testLocaleNotFoundException(): void
    {
        $context = Context::createDefaultContext();

        try {
            $this->mappingService->getLanguageUuid($this->profileUuidService->getProfileUuid(), 'swagMigrationTestingLocaleCode', $context);
        } catch (\Exception $e) {
            /* @var LocaleNotFoundException $e */
            static::assertInstanceOf(LocaleNotFoundException::class, $e);
            static::assertSame(Response::HTTP_NOT_FOUND, $e->getStatusCode());
        }
    }

    public function testGetLanguageUuid(): void
    {
        $context = Context::createDefaultContext();
        $localeCode = 'en_GB';

        $this->mappingService->writeMapping($context);
        $languageUuid = $this->mappingService->createNewUuid($this->connectionId, LanguageDefinition::getEntityName(), $localeCode, $context);
        $this->mappingService->writeMapping($context);
        $response = $this->mappingService->getLanguageUuid($this->connectionId, '', $context);

        static::assertSame($languageUuid, $response['uuid']);
        static::assertSame($localeCode, $response['createData']['localeCode']);
    }

    public function testGetLanguageUuidWithNewLanguage(): void
    {
        $context = Context::createDefaultContext();
        $profileId = $this->profileUuidService->getProfileUuid();
        $localeCode = 'swagMigrationTestingLocaleCode';

        $uuid = $this->localeRepo->create(
            [
                [
                    'code' => $localeCode,
                    'name' => 'Testing Locale Name',
                    'territory' => 'Testing Locale Territory',
                ],
            ],
            $context
        );
        /** @var EntityWrittenEvent $writtenEvent */
        $writtenEvent = $uuid->getEvents()->first();
        $localeIds = $writtenEvent->getIds()[0];
        $localeId = $localeIds['localeId'];

        $response = $this->mappingService->getLanguageUuid($profileId, 'swagMigrationTestingLocaleCode', $context);

        static::assertSame($localeId, $response['createData']['localeId']);
        static::assertSame($localeCode, $response['createData']['localeCode']);
    }

    public function testGetCountryUuidWithNoResult(): void
    {
        $context = Context::createDefaultContext();
        $profileId = $this->profileUuidService->getProfileUuid();

        $response = $this->mappingService->getCountryUuid('testId', 'testIso', 'testIso3', $profileId, $context);
        static::assertNull($response);
    }

    public function testDeleteMapping(): void
    {
        $context = Context::createDefaultContext();
        $localeCode = 'swagMigrationTestingLocaleCode';

        $languageUuid = $this->mappingService->createNewUuid($this->connectionId, LanguageDefinition::getEntityName(), $localeCode, $context);
        $this->mappingService->writeMapping($context);
        $uuid = $this->mappingService->getUuid($this->connectionId, LanguageDefinition::getEntityName(), $localeCode, $context);
        static::assertSame($languageUuid, $uuid);

        $this->mappingService->createNewUuid($this->connectionId, LanguageDefinition::getEntityName(), 'en_GB', $context);
        $this->mappingService->writeMapping($context);

        $this->mappingService->deleteMapping($languageUuid, $this->connectionId, $context);
        $uuid = $this->mappingService->getUuid($this->connectionId, LanguageDefinition::getEntityName(), $localeCode, $context);

        static::assertNull($uuid);
    }
}
