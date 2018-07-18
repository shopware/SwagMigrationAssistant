<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway;

interface GatewayInterface
{
    /**
     * Reads the given entity type from its connection and returns the data
     */
    public function read(string $entityName): array;
}
