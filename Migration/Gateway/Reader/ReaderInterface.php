<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Gateway\Reader;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\TotalStruct;

#[Package('services-settings')]
interface ReaderInterface
{
    public function supports(MigrationContextInterface $migrationContext): bool;

    public function supportsTotal(MigrationContextInterface $migrationContext): bool;

    /**
     * Reads data from source via the given gateway based on implementation
     *
     * @return array<array<string, mixed>>
     */
    public function read(MigrationContextInterface $migrationContext): array;

    public function readTotal(MigrationContextInterface $migrationContext): ?TotalStruct;
}
