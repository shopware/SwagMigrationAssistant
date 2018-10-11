<?php declare(strict_types=1);

namespace SwagMigrationNext\Test;

use Shopware\Core\Framework\ORM\RepositoryInterface;
use SwagMigrationNext\Gateway\GatewayFactoryRegistry;
use SwagMigrationNext\Gateway\Shopware55\Api\Shopware55ApiFactory;
use SwagMigrationNext\Migration\Service\MigrationCollectService;
use SwagMigrationNext\Migration\Service\MigrationCollectServiceInterface;
use SwagMigrationNext\Profile\ProfileRegistry;
use SwagMigrationNext\Profile\Shopware55\Converter\AssetConverter;
use SwagMigrationNext\Profile\Shopware55\Converter\CategoryConverter;
use SwagMigrationNext\Profile\Shopware55\Converter\ConverterRegistry;
use SwagMigrationNext\Profile\Shopware55\Converter\CustomerConverter;
use SwagMigrationNext\Profile\Shopware55\Converter\ProductConverter;
use SwagMigrationNext\Profile\Shopware55\Converter\TranslationConverter;
use SwagMigrationNext\Profile\Shopware55\ConverterHelperService;
use SwagMigrationNext\Profile\Shopware55\Mapping\Shopware55MappingService;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use SwagMigrationNext\Test\Mock\DummyCollection;
use SwagMigrationNext\Test\Mock\Gateway\Dummy\Local\DummyLocalFactory;

trait MigrationServicesTrait
{
    protected function getMigrationCollectService(
        RepositoryInterface $migrationDataRepo,
        Shopware55MappingService $mappingService
    ): MigrationCollectServiceInterface {
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
            new Shopware55Profile($migrationDataRepo, $converterRegistry),
        ]));

        $gatewayFactoryRegistry = new GatewayFactoryRegistry(new DummyCollection([
            new Shopware55ApiFactory(),
            new DummyLocalFactory(),
        ]));

        return new MigrationCollectService($profileRegistry, $gatewayFactoryRegistry);
    }
}
