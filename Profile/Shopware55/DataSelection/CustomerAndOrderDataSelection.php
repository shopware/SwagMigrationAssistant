<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\DataSelection;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use SwagMigrationNext\Migration\DataSelection\DataSelectionInterface;
use SwagMigrationNext\Migration\DataSelection\DataSelectionStruct;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class CustomerAndOrderDataSelection implements DataSelectionInterface
{
    public function supports(string $profileName, string $gatewayIdentifier): bool
    {
        return $profileName === Shopware55Profile::PROFILE_NAME;
    }

    public function getData(): DataSelectionStruct
    {
        return new DataSelectionStruct(
            'customersOrders',
            [
                CustomerDefinition::getEntityName(),
                OrderDefinition::getEntityName(),
            ],
            'swag-migration.index.selectDataCard.dataSelection.customersOrders',
            200
        );
    }
}
