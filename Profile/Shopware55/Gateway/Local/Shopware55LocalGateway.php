<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Gateway\Local;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Shopware\Core\Content\Product\ProductDefinition;
use SwagMigrationNext\Migration\Gateway\AbstractGateway;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalProductReader;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalReaderNotFoundException;

class Shopware55LocalGateway extends AbstractGateway
{
    public const GATEWAY_TYPE = 'local';

    public function read(): array
    {
        $connection = $this->getConnection();

        switch ($this->migrationContext->getEntity()) {
            case ProductDefinition::getEntityName():
                $reader = new Shopware55LocalProductReader($connection, $this->migrationContext);

                return $reader->read();

            default:
                throw new Shopware55LocalReaderNotFoundException($this->migrationContext->getEntity());
        }
    }

    public function readEnvironmentInformation(): array
    {
        return [];
    }

    private function getConnection(): Connection
    {
        $credentials = $this->migrationContext->getCredentials();

        $connectionParams = [
            'dbname' => $credentials['dbName'],
            'user' => $credentials['dbUser'],
            'password' => $credentials['dbPassword'],
            'host' => $credentials['dbHost'],
            'port' => $credentials['dbPort'],
            'driver' => 'pdo_mysql',
            'charset' => 'utf8mb4',
        ];

        return DriverManager::getConnection($connectionParams);
    }
}
