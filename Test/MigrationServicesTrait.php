<?php declare(strict_types=1);

namespace SwagMigrationNext\Test;

use Shopware\Core\Framework\ORM\RepositoryInterface;
use SwagMigrationNext\Gateway\GatewayFactoryRegistry;
use SwagMigrationNext\Gateway\Shopware55\Api\Reader\Shopware55ApiReaderRegistry;
use SwagMigrationNext\Gateway\Shopware55\Api\Shopware55ApiFactory;
use SwagMigrationNext\Migration\MigrationCollectService;
use SwagMigrationNext\Migration\MigrationCollectServiceInterface;
use SwagMigrationNext\Profile\ProfileRegistry;
use SwagMigrationNext\Profile\Shopware55\Converter\ConverterRegistry;
use SwagMigrationNext\Profile\Shopware55\Converter\ProductConverter;
use SwagMigrationNext\Profile\Shopware55\Converter\ProductManufacturerConverter;
use SwagMigrationNext\Profile\Shopware55\Converter\TaxConverter;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use SwagMigrationNext\Test\Mock\DummyCollection;
use SwagMigrationNext\Test\Mock\Gateway\Dummy\Api\Reader\ApiDummyReader;
use SwagMigrationNext\Test\Mock\Gateway\Dummy\Local\DummyLocalFactory;

trait MigrationServicesTrait
{
    protected function getMigrationCollectService(RepositoryInterface $migrationDataRepo): MigrationCollectServiceInterface
    {
        $converterRegistry = new ConverterRegistry(new DummyCollection([new ProductManufacturerConverter(), new TaxConverter(), new ProductConverter()]));
        $profileRegistry = new ProfileRegistry(new DummyCollection([new Shopware55Profile($migrationDataRepo, $converterRegistry)]));

        $shopware55ApiReaderRegistry = new Shopware55ApiReaderRegistry(new DummyCollection([new ApiDummyReader()]));

        $gatewayFactoryRegistry = new GatewayFactoryRegistry(new DummyCollection([
            new Shopware55ApiFactory($shopware55ApiReaderRegistry),
            new DummyLocalFactory(),
        ]));

        return new MigrationCollectService($profileRegistry, $gatewayFactoryRegistry);
    }
}
