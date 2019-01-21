<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration\Mapping;

use Exception;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\Language\LanguageDefinition;
use SwagMigrationNext\Exception\LocaleNotFoundException;
use SwagMigrationNext\Migration\Mapping\MappingService;
use SwagMigrationNext\Migration\Mapping\MappingServiceInterface;
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

    protected function setUp(): void
    {
        $this->localeRepo = $this->getContainer()->get('locale.repository');
        $this->profileUuidService = new MigrationProfileUuidService($this->getContainer()->get('swag_migration_profile.repository'));

        $this->mappingService = new MappingService(
            $this->getContainer()->get('swag_migration_mapping.repository'),
            $this->localeRepo,
            $this->getContainer()->get('language.repository'),
            $this->getContainer()->get('country.repository'),
            $this->getContainer()->get('currency.repository'),
            $this->getContainer()->get('sales_channel.repository'),
            $this->getContainer()->get('sales_channel_type.repository')
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
        $uuid1 = $this->mappingService->createNewUuid($this->profileUuidService->getProfileUuid(), 'product', '123', $context);

        $this->mappingService->writeMapping($context);

        $newMappingService = new MappingService(
            $this->getContainer()->get('swag_migration_mapping.repository'),
            $this->localeRepo,
            $this->getContainer()->get('language.repository'),
            $this->getContainer()->get('country.repository'),
            $this->getContainer()->get('currency.repository'),
            $this->getContainer()->get('sales_channel.repository'),
            $this->getContainer()->get('sales_channel_type.repository')
        );

        $uuid2 = $newMappingService->createNewUuid($this->profileUuidService->getProfileUuid(), 'product', '123', $context);

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
        } catch (Exception $e) {
            /* @var LocaleNotFoundException $e */
            self::assertInstanceOf(LocaleNotFoundException::class, $e);
            self::assertSame(Response::HTTP_NOT_FOUND, $e->getStatusCode());
        }
    }

    public function testGetLanguageUuid(): void
    {
        $context = Context::createDefaultContext();
        $profileId = $this->profileUuidService->getProfileUuid();
        $localeCode = 'en_GB';

        $this->mappingService->writeMapping($context);
        $languageUuid = $this->mappingService->createNewUuid($profileId, LanguageDefinition::getEntityName(), $localeCode, $context);
        $this->mappingService->writeMapping($context);
        $response = $this->mappingService->getLanguageUuid($profileId, '', $context);

        self::assertSame($languageUuid, $response['uuid']);
        self::assertSame($localeCode, $response['createData']['localeCode']);
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

        self::assertSame($localeId, $response['createData']['localeId']);
        self::assertSame($localeCode, $response['createData']['localeCode']);
    }

    public function testGetCountryUuidWithNoResult(): void
    {
        $context = Context::createDefaultContext();
        $profileId = $this->profileUuidService->getProfileUuid();

        $response = $this->mappingService->getCountryUuid('testId', 'testIso', 'testIso3', $profileId, $context);
        self::assertNull($response);
    }

    public function testDeleteMapping(): void
    {
        $context = Context::createDefaultContext();
        $profileId = $this->profileUuidService->getProfileUuid();
        $localeCode = 'swagMigrationTestingLocaleCode';

        $languageUuid = $this->mappingService->createNewUuid($profileId, LanguageDefinition::getEntityName(), $localeCode, $context);
        $this->mappingService->writeMapping($context);
        $uuid = $this->mappingService->getUuid($profileId, LanguageDefinition::getEntityName(), $localeCode, $context);
        self::assertSame($languageUuid, $uuid);

        $this->mappingService->createNewUuid($profileId, LanguageDefinition::getEntityName(), 'en_GB', $context);
        $this->mappingService->writeMapping($context);

        $this->mappingService->deleteMapping($languageUuid, $profileId, $context);
        $uuid = $this->mappingService->getUuid($profileId, LanguageDefinition::getEntityName(), $localeCode, $context);

        self::assertNull($uuid);
    }
}
