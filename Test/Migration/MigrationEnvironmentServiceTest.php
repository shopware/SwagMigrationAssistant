<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\Struct\Uuid;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use SwagMigrationNext\Migration\Asset\MediaFileService;
use SwagMigrationNext\Migration\Converter\ConverterRegistry;
use SwagMigrationNext\Migration\Gateway\GatewayFactoryRegistry;
use SwagMigrationNext\Migration\Gateway\GatewayFactoryRegistryInterface;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Profile\ProfileRegistry;
use SwagMigrationNext\Migration\Service\MigrationEnvironmentService;
use SwagMigrationNext\Migration\Service\MigrationEnvironmentServiceInterface;
use SwagMigrationNext\Profile\Shopware55\Converter\AssetConverter;
use SwagMigrationNext\Profile\Shopware55\Converter\CategoryConverter;
use SwagMigrationNext\Profile\Shopware55\Converter\ConverterHelperService;
use SwagMigrationNext\Profile\Shopware55\Converter\CustomerConverter;
use SwagMigrationNext\Profile\Shopware55\Converter\ProductConverter;
use SwagMigrationNext\Profile\Shopware55\Converter\TranslationConverter;
use SwagMigrationNext\Profile\Shopware55\Gateway\Api\Shopware55ApiFactory;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use SwagMigrationNext\Test\Migration\Services\MigrationProfileUuidService;
use SwagMigrationNext\Test\Mock\DummyCollection;
use SwagMigrationNext\Test\Mock\Gateway\Dummy\Local\DummyLocalFactory;
use SwagMigrationNext\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationNext\Test\Mock\Migration\Mapping\DummyMappingService;

class MigrationEnvironmentServiceTest extends TestCase
{
    use IntegrationTestBehaviour;

    /**
     * @var MigrationEnvironmentServiceInterface
     */
    private $migrationEnvironmentService;

    /**
     * @var MigrationProfileUuidService
     */
    private $profileUuidService;

    protected function setUp()
    {
        $mediaFileService = $this->getContainer()->get(MediaFileService::class);
        $loggingService = new DummyLoggingService();
        $mappingService = new DummyMappingService();
        $converterRegistry = new ConverterRegistry(
            new DummyCollection(
                [
                    new ProductConverter($mappingService, new ConverterHelperService(), $mediaFileService, $loggingService),
                    new TranslationConverter($mappingService, new ConverterHelperService(), $loggingService),
                    new CategoryConverter($mappingService, new ConverterHelperService(), $loggingService),
                    new AssetConverter($mappingService, new ConverterHelperService(), $mediaFileService),
                    new CustomerConverter($mappingService, new ConverterHelperService(), $loggingService),
                ]
            )
        );

        $profileRegistry = new ProfileRegistry(new DummyCollection([
            new Shopware55Profile($this->getContainer()->get('swag_migration_data.repository'), $converterRegistry, $mediaFileService, $loggingService),
        ]));
        /** @var GatewayFactoryRegistryInterface $gatewayFactoryRegistry */
        $gatewayFactoryRegistry = new GatewayFactoryRegistry(new DummyCollection([
            new Shopware55ApiFactory(),
            new DummyLocalFactory(),
        ]));

        $this->migrationEnvironmentService = new MigrationEnvironmentService($profileRegistry, $gatewayFactoryRegistry);
        $this->profileUuidService = new MigrationProfileUuidService($this->getContainer()->get('swag_migration_profile.repository'));
    }

    public function testGetEntityTotal(): void
    {
        $this->profileUuidService = new MigrationProfileUuidService($this->getContainer()->get('swag_migration_profile.repository'));

        $migrationContext = new MigrationContext(
            Uuid::uuid4()->getHex(),
            $this->profileUuidService->getProfileUuid(),
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
            $this->profileUuidService->getProfileUuid(),
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
            $this->profileUuidService->getProfileUuid(),
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
            $this->profileUuidService->getProfileUuid(),
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
