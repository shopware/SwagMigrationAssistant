<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\DataSelection;

use SwagMigrationNext\Migration\DataSelection\DataSelectionInterface;
use SwagMigrationNext\Migration\DataSelection\DataSelectionStruct;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\NewsletterReceiverDataSet;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class NewsletterReceiverDataSelection implements DataSelectionInterface
{
    public function supports(string $profileName, string $gatewayIdentifier): bool
    {
        return $profileName === Shopware55Profile::PROFILE_NAME;
    }

    public function getData(): DataSelectionStruct
    {
        return new DataSelectionStruct(
            'newsletter',
            $this->getEntityNames(),
            'swag-migration.index.selectDataCard.dataSelection.newsletter',
            400,
            false
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityNames(): array
    {
        return [
            NewsletterReceiverDataSet::getEntity(),
        ];
    }
}
