<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\DataSelection;

use SwagMigrationAssistant\Migration\MigrationContextInterface;

interface DataSelectionInterface
{
    public function supports(MigrationContextInterface $migrationContext): bool;

    public function getData(): DataSelectionStruct;

    /**
     * @return string[]
     */
    public function getEntityNames(): array;

    /**
     * @return string[]
     */
    public function getEntityNamesRequiredForCount(): array;
}
