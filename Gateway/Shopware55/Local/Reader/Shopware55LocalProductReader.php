<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway\Shopware55\Local\Reader;

use Doctrine\DBAL\Driver\PDOConnection;

class Shopware55LocalProductReader
{
    public function read(PDOConnection $connection): array
    {
        return [];
    }
}
