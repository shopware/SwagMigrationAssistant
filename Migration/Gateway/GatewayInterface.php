<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Gateway;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\EnvironmentInformation;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

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
     */
    public function read(MigrationContextInterface $migrationContext): array;

    public function readEnvironmentInformation(MigrationContextInterface $migrationContext, Context $context): EnvironmentInformation;

    public function readTotals(MigrationContextInterface $migrationContext, Context $context): array;
}
