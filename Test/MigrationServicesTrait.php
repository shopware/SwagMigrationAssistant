<?php declare(strict_types=1);

namespace SwagMigrationNext\Test;

use Shopware\Core\Checkout\Cart\Price\PriceRounding;
use Shopware\Core\Checkout\Cart\Tax\TaxCalculator;
use Shopware\Core\Checkout\Cart\Tax\TaxRuleCalculator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use SwagMigrationNext\Migration\Converter\ConverterRegistry;
use SwagMigrationNext\Migration\Gateway\GatewayFactoryRegistry;
use SwagMigrationNext\Migration\Logging\LoggingService;
use SwagMigrationNext\Migration\Media\MediaFileServiceInterface;
use SwagMigrationNext\Migration\Profile\ProfileRegistry;
use SwagMigrationNext\Migration\Service\MigrationDataFetcher;
use SwagMigrationNext\Migration\Service\MigrationDataFetcherInterface;
use SwagMigrationNext\Profile\Shopware55\Converter\CategoryConverter;
use SwagMigrationNext\Profile\Shopware55\Converter\ConverterHelperService;
use SwagMigrationNext\Profile\Shopware55\Converter\CustomerConverter;
use SwagMigrationNext\Profile\Shopware55\Converter\MediaConverter;
use SwagMigrationNext\Profile\Shopware55\Converter\OrderConverter;
use SwagMigrationNext\Profile\Shopware55\Converter\ProductConverter;
use SwagMigrationNext\Profile\Shopware55\Converter\TranslationConverter;
use SwagMigrationNext\Profile\Shopware55\Gateway\Api\Shopware55ApiFactory;
use SwagMigrationNext\Profile\Shopware55\Mapping\Shopware55MappingService;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use SwagMigrationNext\Test\Mock\DummyCollection;
use SwagMigrationNext\Test\Mock\Gateway\Dummy\Local\DummyLocalFactory;
use SwagMigrationNext\Test\Mock\Profile\Dummy\DummyInvalidCustomerConverter;

trait MigrationServicesTrait
{
    protected function getMigrationDataFetcher(
        EntityRepositoryInterface $migrationDataRepo,
        Shopware55MappingService $mappingService,
        MediaFileServiceInterface $mediaFileService,
        EntityRepositoryInterface $loggingRepo
    ): MigrationDataFetcherInterface {
        $loggingService = new LoggingService($loggingRepo);
        $priceRounding = new PriceRounding(2);
        $converterRegistry = new ConverterRegistry(
            new DummyCollection(
                [
                    new ProductConverter($mappingService, new ConverterHelperService(), $mediaFileService, $loggingService),
                    new TranslationConverter($mappingService, new ConverterHelperService(), $loggingService),
                    new CategoryConverter($mappingService, new ConverterHelperService(), $loggingService),
                    new MediaConverter($mappingService, new ConverterHelperService(), $mediaFileService),
                    new CustomerConverter($mappingService, new ConverterHelperService(), $loggingService),
                    new CustomerConverter($mappingService, new ConverterHelperService(), $loggingService),
                    new OrderConverter(
                        $mappingService,
                        new ConverterHelperService(),
                        new TaxCalculator(
                            $priceRounding,
                            new TaxRuleCalculator($priceRounding)
                        ),
                        $loggingService
                    ),
                    new DummyInvalidCustomerConverter($mappingService, new ConverterHelperService(), $loggingService),
                ]
            )
        );

        $profileRegistry = new ProfileRegistry(new DummyCollection([
            new Shopware55Profile(
                $migrationDataRepo,
                $converterRegistry,
                $mediaFileService,
                $loggingService
            ),
        ]));

        $gatewayFactoryRegistry = new GatewayFactoryRegistry(new DummyCollection([
            new Shopware55ApiFactory(),
            new DummyLocalFactory(),
        ]));

        return new MigrationDataFetcher($profileRegistry, $gatewayFactoryRegistry, $loggingService);
    }
}
