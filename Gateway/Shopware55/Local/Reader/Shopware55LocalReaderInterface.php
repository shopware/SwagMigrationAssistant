<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway\Shopware55\Local\Reader;

use Doctrine\DBAL\Driver\PDOConnection;

interface Shopware55LocalReaderInterface
{
    /**
     * Identifier which external entity this reader supports
     */
    public function supports(): string;

    /**
     * Reads data from its entity with the given database connection
     */
    public function read(PDOConnection $connection): array;
}
