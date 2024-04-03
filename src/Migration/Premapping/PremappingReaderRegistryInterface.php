<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Premapping;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('services-settings')]
interface PremappingReaderRegistryInterface
{
    /**
     * @param list<string> $dataSelectionIds
     *
     * @return PremappingReaderInterface[]
     */
    public function getPremappingReaders(MigrationContextInterface $migrationContext, array $dataSelectionIds): array;
}
