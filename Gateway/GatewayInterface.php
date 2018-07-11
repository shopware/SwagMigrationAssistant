<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway;

interface GatewayInterface
{
    public function read(string $entityType): array;
}
