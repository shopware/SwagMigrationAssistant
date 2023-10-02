<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\DataSelection\DataSet;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Exception\DataSetNotFoundException;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('services-settings')]
interface DataSetRegistryInterface
{
    /**
     * @return DataSet[]
     */
    public function getDataSets(MigrationContextInterface $migrationContext): array;

    /**
     * @throws DataSetNotFoundException
     */
    public function getDataSet(MigrationContextInterface $migrationContext, string $dataSetName): DataSet;
}
