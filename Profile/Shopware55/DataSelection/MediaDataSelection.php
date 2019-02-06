<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\DataSelection;

use Shopware\Core\Content\Media\MediaDefinition;
use SwagMigrationNext\Migration\DataSelection\DataSelectionInterface;
use SwagMigrationNext\Migration\DataSelection\DataSelectionStruct;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class MediaDataSelection implements DataSelectionInterface
{
    public function supports(string $profileName, string $gatewayIdentifier): bool
    {
        return $profileName === Shopware55Profile::PROFILE_NAME;
    }

    public function getData(): DataSelectionStruct
    {
        return new DataSelectionStruct(
            'media',
            [
                MediaDefinition::getEntityName(),
            ],
            'swag-migration.index.selectDataCard.dataSelection.shopware55Profile.media',
            300
        );
    }
}
