<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\DataSelection;

use SwagMigrationAssistant\Migration\EnvironmentInformation;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

interface DataSelectionRegistryInterface
{
    public function getDataSelections(MigrationContextInterface $migrationContext, EnvironmentInformation $environmentInformation): DataSelectionCollection;

    public function getDataSelectionsByIds(MigrationContextInterface $migrationContext, EnvironmentInformation $environmentInformation, array $ids): DataSelectionCollection;
}
