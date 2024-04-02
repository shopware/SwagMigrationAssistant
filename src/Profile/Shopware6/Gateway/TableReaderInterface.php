<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Gateway;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('services-settings')]
interface TableReaderInterface
{
    /**
     * Reads data from source table via the given gateway based on implementation
     *
     * @param array<string, mixed> $filter
     *
     * @return array<string, mixed>
     */
    public function read(MigrationContextInterface $migrationContext, string $tableName, array $filter = []): array;
}
