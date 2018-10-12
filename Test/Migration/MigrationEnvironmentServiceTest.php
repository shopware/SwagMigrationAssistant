<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\Struct\Uuid;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use SwagMigrationNext\Gateway\GatewayFactoryRegistry;
use SwagMigrationNext\Gateway\GatewayFactoryRegistryInterface;
use SwagMigrationNext\Gateway\Shopware55\Api\Shopware55ApiFactory;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Service\MigrationEnvironmentService;
use SwagMigrationNext\Migration\Service\MigrationEnvironmentServiceInterface;
use SwagMigrationNext\Profile\ProfileRegistry;
use SwagMigrationNext\Profile\Shopware55\Converter\AssetConverter;
use SwagMigrationNext\Profile\Shopware55\Converter\CategoryConverter;
use SwagMigrationNext\Profile\Shopware55\Converter\ConverterRegistry;
use SwagMigrationNext\Profile\Shopware55\Converter\CustomerConverter;
use SwagMigrationNext\Profile\Shopware55\Converter\ProductConverter;
use SwagMigrationNext\Profile\Shopware55\Converter\TranslationConverter;
use SwagMigrationNext\Profile\Shopware55\ConverterHelperService;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use SwagMigrationNext\Test\Mock\DummyCollection;
use SwagMigrationNext\Test\Mock\Gateway\Dummy\Local\DummyLocalFactory;
use SwagMigrationNext\Test\Mock\Migration\Mapping\DummyMappingService;

class MigrationEnvironmentServiceTest extends TestCase
{
    use IntegrationTestBehaviour;

    /**
     * @var MigrationEnvironmentServiceInterface
     */
    private $migrationEnvironmentService;

    protected function setUp()
    {
        $mappingService = new DummyMappingService();
        $converterRegistry = new ConverterRegistry(
            new DummyCollection(
                [
                    new ProductConverter($mappingService, new ConverterHelperService()),
                    new TranslationConverter($mappingService, new ConverterHelperService()),
                    new CategoryConverter($mappingService, new ConverterHelperService()),
                    new AssetConverter($mappingService, new ConverterHelperService()),
                    new CustomerConverter($mappingService, new ConverterHelperService()),
                ]
            )
        );

        $profileRegistry = new ProfileRegistry(new DummyCollection([
            new Shopware55Profile($this->getContainer()->get('swag_migration_data.repository'), $converterRegistry),
        ]));
        /** @var GatewayFactoryRegistryInterface $gatewayFactoryRegistry */
        $gatewayFactoryRegistry = new GatewayFactoryRegistry(new DummyCollection([
            new Shopware55ApiFactory(),
            new DummyLocalFactory(),
        ]));

        $this->migrationEnvironmentService = new MigrationEnvironmentService($profileRegistry, $gatewayFactoryRegistry);
    }

    public function testGetEntityTotal(): void
    {
        $migrationContext = new MigrationContext(
            Uuid::uuid4()->getHex(),
            Shopware55Profile::PROFILE_NAME,
            'local',
            CustomerDefinition::getEntityName(),
            [],
            0,
            250
        );

        $total = $this->migrationEnvironmentService->getEntityTotal($migrationContext);

        self::assertSame(2, $total);

        $migrationContext = new MigrationContext(
            '',
            Shopware55Profile::PROFILE_NAME,
            'local',
            ProductDefinition::getEntityName(),
            [],
            0,
            250
        );

        $total = $this->migrationEnvironmentService->getEntityTotal($migrationContext);

        self::assertSame(37, $total);

        $migrationContext = new MigrationContext(
            '',
            Shopware55Profile::PROFILE_NAME,
            'local',
            CategoryDefinition::getEntityName(),
            [],
            0,
            250
        );

        $total = $this->migrationEnvironmentService->getEntityTotal($migrationContext);

        self::assertSame(8, $total);

        $migrationContext = new MigrationContext(
            '',
            Shopware55Profile::PROFILE_NAME,
            'local',
            MediaDefinition::getEntityName(),
            [],
            0,
            250
        );

        $total = $this->migrationEnvironmentService->getEntityTotal($migrationContext);

        self::assertSame(23, $total);
    }
}
