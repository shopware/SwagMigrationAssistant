<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Mock\Gateway\Dummy\Local;

use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use SwagMigrationNext\Gateway\GatewayInterface;

class DummyLocalGateway implements GatewayInterface
{
    public const GATEWAY_TYPE = 'local';

    public function read(string $entityName, int $offset, int $limit): array
    {
        switch ($entityName) {
            case ProductDefinition::getEntityName():
                return require __DIR__ . '/../../../../_fixtures/product_data.php';
            break;
            case 'translation':
                return require __DIR__ . '/../../../../_fixtures/translation_data.php';
                break;
            case CategoryDefinition::getEntityName():
                return require __DIR__ . '/../../../../_fixtures/category_data.php';
                break;
            case MediaDefinition::getEntityName():
                return require __DIR__ . '/../../../../_fixtures/media_data.php';
                break;
            case CustomerDefinition::getEntityName():
                return require __DIR__ . '/../../../../_fixtures/customer_data.php';
                break;
        }
    }
}
