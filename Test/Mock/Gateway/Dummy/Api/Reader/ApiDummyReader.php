<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Mock\Gateway\Dummy\Api\Reader;

use GuzzleHttp\Client;
use Shopware\Core\Content\Product\ProductDefinition;
use SwagMigrationNext\Gateway\Shopware55\Api\Reader\Shopware55ApiReaderInterface;

class ApiDummyReader implements Shopware55ApiReaderInterface
{
    public function supports(): string
    {
        return ProductDefinition::getEntityName();
    }

    public function read(Client $apiClient): array
    {
        return require __DIR__ . '/../../../../../_fixtures/product_data.php';
    }
}
