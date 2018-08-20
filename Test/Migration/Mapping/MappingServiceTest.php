<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration\Mapping;

use Doctrine\DBAL\Connection;
use Exception;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use SwagMigrationNext\Exception\LocaleNotFoundException;
use SwagMigrationNext\Migration\Mapping\MappingService;
use SwagMigrationNext\Migration\Mapping\MappingServiceInterface;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Response;

class MappingServiceTest extends KernelTestCase
{
    /**
     * @var MappingServiceInterface
     */
    private $mappingService;

    /**
     * @var Connection
     */
    private $connection;

    protected function setUp()
    {
        parent::setUp();
        self::bootKernel();

        $this->connection = self::$container->get(Connection::class);
        $this->connection->beginTransaction();

        $this->mappingService = new MappingService(
            self::$container->get('swag_migration_mapping.repository'),
            self::$container->get('locale.repository'),
            self::$container->get('language.repository'),
            self::$container->get('payment_method.repository'),
            self::$container->get('country.repository'),
            self::$container->get('order_state.repository'),
            self::$container->get('order_transaction_state.repository')
        );
    }

    protected function tearDown()
    {
        $this->connection->rollBack();
        parent::tearDown();
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
            self::$container->get('swag_migration_mapping.repository'),
            self::$container->get('locale.repository'),
            self::$container->get('language.repository'),
            self::$container->get('payment_method.repository'),
            self::$container->get('country.repository'),
            self::$container->get('order_state.repository'),
            self::$container->get('order_transaction_state.repository')
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
