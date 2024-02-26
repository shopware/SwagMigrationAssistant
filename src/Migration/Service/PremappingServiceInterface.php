<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Premapping\PremappingStruct;

#[Package('services-settings')]
interface PremappingServiceInterface
{
    /**
     * @param array<int, string> $dataSelectionIds
     *
     * @return array<int, PremappingStruct>
     */
    public function generatePremapping(Context $context, MigrationContextInterface $migrationContext, array $dataSelectionIds): array;

    /**
     * @param array<array{entity: string, mapping: list<array{sourceId: string, description: string, destinationUuid: string}>}> $premapping
     */
    public function writePremapping(Context $context, MigrationContextInterface $migrationContext, array $premapping): void;
}
