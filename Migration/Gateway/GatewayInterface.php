<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Gateway;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\EnvironmentInformation;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\TotalStruct;

#[Package('services-settings')]
interface GatewayInterface
{
    public function getName(): string;

    public function getSnippetName(): string;

    /**
     * Identifier for a gateway registry
     */
    public function supports(MigrationContextInterface $context): bool;

    /**
     * Reads the given entity type from via context from its connection and returns the data
     *
     * @return array<array<string, mixed>>
     */
    public function read(MigrationContextInterface $migrationContext): array;

    public function readEnvironmentInformation(MigrationContextInterface $migrationContext, Context $context): EnvironmentInformation;

    /**
     * @return array<string, TotalStruct>
     */
    public function readTotals(MigrationContextInterface $migrationContext, Context $context): array;
}
