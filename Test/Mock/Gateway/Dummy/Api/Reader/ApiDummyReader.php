<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Mock\Gateway\Dummy\Api\Reader;

use Shopware\Core\Content\Product\ProductDefinition;

class ApiDummyReader
{
    public function supports(): string
    {
        return ProductDefinition::getEntityName();
    }

    public function read(): array
    {
        return require __DIR__ . '/../../../../../_fixtures/product_data.php';
    }
}
