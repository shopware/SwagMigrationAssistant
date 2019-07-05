<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Gateway;

use SwagMigrationAssistant\Migration\EnvironmentInformation;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

interface GatewayInterface
{
    public function getName(): string;

    /**
     * Identifier for a gateway registry
     */
    public function supports(MigrationContextInterface $context): bool;

    /**
     * Reads the given entity type from via context from its connection and returns the data
     */
    public function read(MigrationContextInterface $migrationContext): array;

    public function readEnvironmentInformation(MigrationContextInterface $migrationContext): EnvironmentInformation;

    public function readTotals(MigrationContextInterface $migrationContext): array;
}
