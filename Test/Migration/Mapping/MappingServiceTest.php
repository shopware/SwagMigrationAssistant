<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration\Mapping;

use Exception;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use SwagMigrationNext\Exception\LocaleNotFoundException;
use SwagMigrationNext\Migration\Mapping\MappingService;
use SwagMigrationNext\Migration\Mapping\MappingServiceInterface;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use Symfony\Component\HttpFoundation\Response;

class MappingServiceTest extends TestCase
{
    use IntegrationTestBehaviour;

    /**
     * @var MappingServiceInterface
     */
    private $mappingService;

    protected function setUp()
    {
        $this->mappingService = new MappingService(
            $this->getContainer()->get('swag_migration_mapping.repository'),
            $this->getContainer()->get('locale.repository'),
            $this->getContainer()->get('language.repository'),
            $this->getContainer()->get('country.repository')
        );
    }

    public function testCreateNewUuid(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);

        $uuid1 = $this->mappingService->createNewUuid(Shopware55Profile::PROFILE_NAME, 'product', '123', $context);
        static::assertNotNull($uuid1);

        $uuid2 = $this->mappingService->createNewUuid(Shopware55Profile::PROFILE_NAME, 'product', '123', $context);
        static::assertEquals($uuid1, $uuid2);
    }

    public function testReadExistingMappings(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $uuid1 = $this->mappingService->createNewUuid(Shopware55Profile::PROFILE_NAME, 'product', '123', $context);
        $context = Context::createDefaultContext(Defaults::TENANT_ID);

        $newMappingService = new MappingService(
            $this->getContainer()->get('swag_migration_mapping.repository'),
            $this->getContainer()->get('locale.repository'),
            $this->getContainer()->get('language.repository'),
            $this->getContainer()->get('country.repository')
        );

        $uuid2 = $newMappingService->createNewUuid(Shopware55Profile::PROFILE_NAME, 'product', '123', $context);

        static::assertEquals($uuid1, $uuid2);
    }

    public function testGetUuidReturnsNull(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        static::assertNull($this->mappingService->getUuid(Shopware55Profile::PROFILE_NAME, 'product', '12345', $context));
    }

    public function testLocaleNotFoundException(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);

        try {
            $this->mappingService->getLanguageUuid(Shopware55Profile::PROFILE_NAME, 'foobar', $context);
        } catch (Exception $e) {
            /* @var LocaleNotFoundException $e */
            self::assertInstanceOf(LocaleNotFoundException::class, $e);
            self::assertEquals(Response::HTTP_NOT_FOUND, $e->getStatusCode());
        }
    }
}
