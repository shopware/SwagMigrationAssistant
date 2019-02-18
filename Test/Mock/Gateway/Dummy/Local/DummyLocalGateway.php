<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Mock\Gateway\Dummy\Local;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use SwagMigrationNext\Migration\EnvironmentInformation;
use SwagMigrationNext\Migration\Gateway\AbstractGateway;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class DummyLocalGateway extends AbstractGateway
{
    public const GATEWAY_NAME = 'local';

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

    public function readEnvironmentInformation(): EnvironmentInformation
    {
        $environmentData = require __DIR__ . '/../../../../_fixtures/environment_data.php';

        $environmentDataArray = $environmentData['environmentInformation'];

        if (empty($environmentDataArray)) {
            return new EnvironmentInformation(
                Shopware55Profile::SOURCE_SYSTEM_NAME,
                Shopware55Profile::SOURCE_SYSTEM_VERSION,
                '',
                [],
                [],
                $environmentData['warning']['code'],
                $environmentData['warning']['detail'],
                $environmentData['error']['code'],
                $environmentData['error']['detail']
            );
        }

        if (!isset($environmentDataArray['translations'])) {
            $environmentDataArray['translations'] = 0;
        }

        $totals = [
            CategoryDefinition::getEntityName() => $environmentDataArray['categories'],
            ProductDefinition::getEntityName() => $environmentDataArray['products'],
            CustomerDefinition::getEntityName() => $environmentDataArray['customers'],
            OrderDefinition::getEntityName() => $environmentDataArray['orders'],
            MediaDefinition::getEntityName() => $environmentDataArray['assets'],
            'translation' => $environmentDataArray['translations'],
        ];

        return new EnvironmentInformation(
            Shopware55Profile::SOURCE_SYSTEM_NAME,
            $environmentDataArray['shopwareVersion'],
            $environmentDataArray['structure'][0]['host'],
            $environmentDataArray['structure'],
            $totals,
            $environmentData['warning']['code'],
            $environmentData['warning']['detail'],
            $environmentData['error']['code'],
            $environmentData['error']['detail']
        );
    }
}
