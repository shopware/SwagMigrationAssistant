<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration\Services;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Struct\Uuid;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use SwagMigrationNext\Migration\Converter\ConverterRegistry;
use SwagMigrationNext\Migration\Gateway\GatewayFactoryRegistry;
use SwagMigrationNext\Migration\Logging\LoggingService;
use SwagMigrationNext\Migration\Profile\ProfileRegistry;
use SwagMigrationNext\Migration\Run\RunService;
use SwagMigrationNext\Migration\Service\SwagMigrationAccessTokenService;
use SwagMigrationNext\Profile\Shopware55\Gateway\Api\Shopware55ApiFactory;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Shopware55LocalGateway;
use SwagMigrationNext\Profile\Shopware55\Mapping\Shopware55MappingService;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use SwagMigrationNext\Test\MigrationServicesTrait;
use SwagMigrationNext\Test\Mock\DummyCollection;
use SwagMigrationNext\Test\Mock\Gateway\Dummy\Local\DummyLocalFactory;
use SwagMigrationNext\Test\Mock\Migration\Mapping\DummyMappingService;
use SwagMigrationNext\Test\Mock\Migration\Media\DummyMediaFileService;
use SwagMigrationNext\Test\Mock\Migration\Service\DummyMigrationDataFetcher;

class RunServiceTest extends TestCase
{
    use MigrationServicesTrait,
        IntegrationTestBehaviour;

    /**
     * @var EntityRepositoryInterface
     */
    private $runRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $dataRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $profileRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $mappingRepo;

    /**
     * @var DummyMappingService
     */
    private $mappingService;

    /**
     * @var RunService
     */
    private $runService;

    /**
     * @var RunService
     */
    private $runServiceWithoutStructure;

    /**
     * @var string
     */
    private $profileId;

    protected function setUp()
    {
        $this->runRepo = $this->getContainer()->get('swag_migration_run.repository');
        $this->dataRepo = $this->getContainer()->get('swag_migration_data.repository');
        $this->profileRepo = $this->getContainer()->get('swag_migration_profile.repository');
        $this->mappingRepo = $this->getContainer()->get('swag_migration_mapping.repository');
        $loggingRepo = $this->getContainer()->get('swag_migration_logging.repository');

        $this->mappingService = new Shopware55MappingService(
            $this->mappingRepo,
            $this->getContainer()->get('locale.repository'),
            $this->getContainer()->get('language.repository'),
            $this->getContainer()->get('country.repository'),
            $this->getContainer()->get('payment_method.repository'),
            $this->getContainer()->get('state_machine.repository'),
            $this->getContainer()->get('state_machine_state.repository'),
            $this->getContainer()->get('currency.repository'),
            $this->getContainer()->get('sales_channel.repository'),
            $this->getContainer()->get('sales_channel_type.repository')
        );
        $loggingService = new LoggingService($loggingRepo);
        $mediaFileService = new DummyMediaFileService();

        $profileUuidService = new MigrationProfileUuidService(
            $this->profileRepo,
            Shopware55Profile::PROFILE_NAME,
            Shopware55LocalGateway::GATEWAY_TYPE
        );
        $this->profileId = $profileUuidService->getProfileUuid();

        $converterRegistry = new ConverterRegistry(new DummyCollection([]));

        $profileRegistry = new ProfileRegistry(new DummyCollection([
            new Shopware55Profile(
                $this->dataRepo,
                $converterRegistry,
                $mediaFileService,
                $loggingService
            ),
        ]));

        $gatewayFactoryRegistry = new GatewayFactoryRegistry(new DummyCollection([
            new Shopware55ApiFactory(),
            new DummyLocalFactory(),
        ]));

        $this->runService = new RunService(
            $this->runRepo,
            $this->profileRepo,
            $this->getMigrationDataFetcher(
                $this->dataRepo,
                $this->mappingService,
                $mediaFileService,
                $loggingRepo
            ),
            $this->mappingService,
            new SwagMigrationAccessTokenService($this->runRepo)
        );

        $this->runServiceWithoutStructure = new RunService(
            $this->runRepo,
            $this->profileRepo,
            new DummyMigrationDataFetcher(
                $profileRegistry,
                $gatewayFactoryRegistry,
                $loggingService
            ),
            $this->mappingService,
            new SwagMigrationAccessTokenService($this->runRepo)
        );
    }

    public function testCreateMigrationRunWithoutStructure(): void
    {
        $context = $context = Context::createDefaultContext();
        $customerId = Uuid::uuid4()->getHex();
        $context->getSourceContext()->setUserId($customerId);

        $beforeRunTotal = $this->runRepo->search(new Criteria(), $context)->getTotal();
        $beforeMappingTotal = $this->mappingRepo->search(new Criteria(), $context)->getTotal();
        $this->runServiceWithoutStructure->createMigrationRun(
            $this->profileId,
            [],
            [],
            $context
        );
        $afterRunTotal = $this->runRepo->search(new Criteria(), $context)->getTotal();
        $afterMappingTotal = $this->mappingRepo->search(new Criteria(), $context)->getTotal();

        $this->assertSame(1, $afterRunTotal - $beforeRunTotal);
        $this->assertSame(0, $afterMappingTotal - $beforeMappingTotal);
    }

    public function testCreateMigrationRunWithStructure(): void
    {
        $context = $context = Context::createDefaultContext();
        $customerId = Uuid::uuid4()->getHex();
        $context->getSourceContext()->setUserId($customerId);

        $beforeRunTotal = $this->runRepo->search(new Criteria(), $context)->getTotal();
        $beforeMappingTotal = $this->mappingRepo->search(new Criteria(), $context)->getTotal();
        $this->runService->createMigrationRun(
            $this->profileId,
            [],
            [],
            $context
        );
        $afterRunTotal = $this->runRepo->search(new Criteria(), $context)->getTotal();
        $afterMappingTotal = $this->mappingRepo->search(new Criteria(), $context)->getTotal();

        $this->assertSame(1, $afterRunTotal - $beforeRunTotal);
        $this->assertSame(2, $afterMappingTotal - $beforeMappingTotal);
    }
}
