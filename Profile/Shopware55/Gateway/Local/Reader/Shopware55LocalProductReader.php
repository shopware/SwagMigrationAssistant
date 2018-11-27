<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader;

use Doctrine\DBAL\Connection;
use SwagMigrationNext\Migration\MigrationContext;

class Shopware55LocalProductReader
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var MigrationContext
     */
    protected $migrationContext;

    public function __construct(Connection $connection, MigrationContext $migrationContext)
    {
        $this->connection = $connection;
        $this->migrationContext = $migrationContext;
    }

    public function read(): array
    {
        return [];
    }
}
