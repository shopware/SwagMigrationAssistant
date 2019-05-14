<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Gateway;

use SwagMigrationAssistant\Migration\EnvironmentInformation;

interface GatewayInterface
{
    /**
     * Reads the given entity type from via context from its connection and returns the data
     */
    public function read(): array;

    public function readEnvironmentInformation(): EnvironmentInformation;
}
