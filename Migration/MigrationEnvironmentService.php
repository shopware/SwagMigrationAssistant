<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use SwagMigrationNext\Gateway\GatewayFactoryRegistryInterface;

class MigrationEnvironmentService
{
    /**
     * @var GatewayFactoryRegistryInterface
     */
    private $gatewayFactoryRegistry;

    public function __construct(GatewayFactoryRegistryInterface $gatewayFactoryRegistry)
    {
        $this->gatewayFactoryRegistry = $gatewayFactoryRegistry;
    }

    public function getEntityTotal(MigrationContext $migrationContext): int
    {
        $entity = $migrationContext->getEntity();
        $gateway = $this->gatewayFactoryRegistry->createGateway($migrationContext);
        $data = $gateway->read('environment', 0, 0);

        $key = '';
        switch ($entity) {
            case ProductDefinition::getEntityName():
                $key = 'products';
                break;
            case CustomerDefinition::getEntityName():
                $key = 'customers';
                break;
            case CategoryDefinition::getEntityName():
                $key = 'categories';
                break;
            case MediaDefinition::getEntityName():
                $key = 'assets';
                break;
            case 'translation':
                $key = 'translations';
                break;
        }

        if (!isset($data[$key])) {
            return 0;
        }

        return $data[$key];
    }
}
