<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration\Mapping;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use SwagMigrationNext\Migration\Mapping\MappingService;
use SwagMigrationNext\Migration\Mapping\MappingServiceInterface;
use SwagMigrationNext\Migration\Mapping\ProfileForMappingMissingException;
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

        $this->mappingService = new MappingService(self::$container->get('swag_migration_mapping.repository'));
    }

    protected function tearDown()
    {
        $this->connection->rollBack();
        parent::tearDown();
    }

    public function testCreateNewUuid(): void
    {
        $this->mappingService->setProfile('shopware55');
        $uuid1 = $this->mappingService->createNewUuid('product', '123');
        static::assertNotNull($uuid1);

        $uuid2 = $this->mappingService->createNewUuid('product', '123');
        static::assertEquals($uuid1, $uuid2);
    }

    public function testCreateNewUuidMissingProfile(): void
    {
        try {
            $this->mappingService->createNewUuid('product', '123');
        } catch (\Exception $e) {
            /* @var ProfileForMappingMissingException $e */
            self::assertInstanceOf(ProfileForMappingMissingException::class, $e);
            self::assertEquals(Response::HTTP_NOT_FOUND, $e->getStatusCode());
        }
    }

    public function testReadExistingMappings(): void
    {
        $this->mappingService->setProfile('shopware55');
        $uuid1 = $this->mappingService->createNewUuid('product', '123');
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $this->mappingService->writeMapping($context);

        $newMappingService = new MappingService(self::$container->get('swag_migration_mapping.repository'));
        $newMappingService->setProfile('shopware55');

        $newMappingService->readExistingMappings($context);
        $uuid2 = $newMappingService->createNewUuid('product', '123');

        static::assertEquals($uuid1, $uuid2);
    }

    public function testWriteMappingEmptyData(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        static::assertNull($this->mappingService->writeMapping($context));
    }

    public function testReadExistingMappingsEmptyData()
    {
        $this->mappingService->setProfile('shopware55');
        $this->mappingService->createNewUuid('product', '123');
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        static::assertNull($this->mappingService->readExistingMappings($context));
    }
}
