<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware55\Gateway\Connection;

use Doctrine\DBAL\Connection;
use GuzzleHttp\Client;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

interface ConnectionFactoryInterface
{
    public function createApiClient(MigrationContextInterface $migrationContext): Client;

    public function createDatabaseConnection(MigrationContextInterface $migrationContext): Connection;
}
