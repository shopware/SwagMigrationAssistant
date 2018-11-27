<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Mock\Gateway\Dummy\Local;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use SwagMigrationNext\Migration\Gateway\AbstractGateway;

class DummyLocalGateway extends AbstractGateway
{
    public const GATEWAY_TYPE = 'local';

    public function read(): array
    {
        switch ($this->migrationContext->getEntity()) {
            case ProductDefinition::getEntityName():
                return require __DIR__ . '/../../../../_fixtures/product_data.php';
            case 'translation':
                return require __DIR__ . '/../../../../_fixtures/translation_data.php';
            case CategoryDefinition::getEntityName():
                return require __DIR__ . '/../../../../_fixtures/category_data.php';
            case MediaDefinition::getEntityName():
                return require __DIR__ . '/../../../../_fixtures/media_data.php';
            case CustomerDefinition::getEntityName():
                return require __DIR__ . '/../../../../_fixtures/customer_data.php';
            case OrderDefinition::getEntityName():
                return require __DIR__ . '/../../../../_fixtures/order_data.php';
            //Invalid data
            case CustomerDefinition::getEntityName() . 'Invalid':
                return require __DIR__ . '/../../../../_fixtures/invalid/customer_data.php';
            default:
                return [];
        }
    }

    public function readEnvironmentInformation(): array
    {
        return require __DIR__ . '/../../../../_fixtures/environment_data.php';
    }
}
