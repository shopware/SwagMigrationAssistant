<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\DataSelection;

use SwagMigrationAssistant\Migration\DataSelection\DataSelectionInterface;
use SwagMigrationAssistant\Migration\DataSelection\DataSelectionStruct;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\ProductDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\ProductFeatureSetDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\ProductManufacturerDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\PropertyGroupDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\TaxDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6ProfileInterface;

class ProductDataSelection implements DataSelectionInterface
{
    public const IDENTIFIER = 'products';

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof Shopware6ProfileInterface;
    }

    public function getData(): DataSelectionStruct
    {
        return new DataSelectionStruct(
            self::IDENTIFIER,
            $this->getDataSets(),
            $this->getDataSetsRequiredForCount(),
            'swag-migration.index.selectDataCard.dataSelection.products',
            100,
            false // ToDo enable when media processing is implemented
        );
    }

    public function getDataSets(): array
    {
        return [
            new TaxDataSet(),
            new PropertyGroupDataSet(),
            new ProductFeatureSetDataSet(),
            new ProductManufacturerDataSet(),
            new ProductDataSet(),
        ];
    }

    public function getDataSetsRequiredForCount(): array
    {
        return [
            new ProductDataSet(),
        ];
    }
}
