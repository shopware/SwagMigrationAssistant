<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration;

use InvalidArgumentException;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use SwagMigrationNext\Gateway\GatewayFactoryRegistryInterface;

class MigrationEnvironmentService implements MigrationEnvironmentServiceInterface
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
            case OrderDefinition::getEntityName():
                $key = 'orders';
                break;
            default:
                throw new InvalidArgumentException('No valid entity provided');
        }

        if (!isset($data[$key])) {
            return 0;
        }

        return $data[$key];
    }

    public function getEnvironmentInformation(MigrationContext $migrationContext): array
    {
        $gateway = $this->gatewayFactoryRegistry->createGateway($migrationContext);

        return $gateway->read('environment', 0, 0);
    }
}
