<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\DataSelection;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('services-settings')]
interface DataSelectionInterface
{
    public function supports(MigrationContextInterface $migrationContext): bool;

    public function getData(): DataSelectionStruct;

    /**
     * @return DataSet[]
     */
    public function getDataSets(): array;

    /**
     * @return DataSet[]
     */
    public function getDataSetsRequiredForCount(): array;
}
