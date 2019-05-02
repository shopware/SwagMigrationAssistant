<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\DataSelection;

use SwagMigrationNext\Migration\DataSelection\DataSelectionInterface;
use SwagMigrationNext\Migration\DataSelection\DataSelectionStruct;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\NumberRangeDataSet;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class NumberRangeDataSelection implements DataSelectionInterface
{
    public const IDENTIFIER = 'numberRanges';

    public function supports(string $profileName, string $gatewayIdentifier): bool
    {
        return $profileName === Shopware55Profile::PROFILE_NAME;
    }

    public function getData(): DataSelectionStruct
    {
        return new DataSelectionStruct(
            self::IDENTIFIER,
            $this->getEntityNames(),
            'swag-migration.index.selectDataCard.dataSelection.numberRanges',
            99,
            true
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityNames(): array
    {
        return [
            NumberRangeDataSet::getEntity(),
        ];
    }
}
