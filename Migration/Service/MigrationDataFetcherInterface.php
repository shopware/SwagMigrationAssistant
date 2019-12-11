<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Service;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\EnvironmentInformation;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

interface MigrationDataFetcherInterface
{
    /**
     * Uses the given migration context and the shopware context to read data from an external source
     * and tries to convert it into the internal structure.
     * Returns the count of the imported data
     */
    public function fetchData(MigrationContextInterface $migrationContext, Context $context): array;

    /**
     * Reads the complete environment information from the source system
     */
    public function getEnvironmentInformation(MigrationContextInterface $migrationContext, Context $context): EnvironmentInformation;

    /**
     * Reads the totals of the data sets / db tables
     */
    public function fetchTotals(MigrationContextInterface $migrationContext, Context $context): array;
}
