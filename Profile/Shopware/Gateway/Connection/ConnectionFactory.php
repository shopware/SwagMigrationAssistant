<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Connection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use GuzzleHttp\Client;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

class ConnectionFactory implements ConnectionFactoryInterface
{
    public function createApiClient(MigrationContextInterface $migrationContext): Client
    {
        $credentials = $migrationContext->getConnection()->getCredentialFields();

        $options = [
            'base_uri' => $credentials['endpoint'] . '/api/',
            'auth' => [$credentials['apiUser'], $credentials['apiKey'], 'digest'],
            'connect_timeout' => 5.0,
            'verify' => false,
        ];

        return new Client($options);
    }

    public function createDatabaseConnection(MigrationContextInterface $migrationContext): Connection
    {
        $credentials = $migrationContext->getConnection()->getCredentialFields();

        $connectionParams = [
            'dbname' => $credentials['dbName'] ?? '',
            'user' => $credentials['dbUser'] ?? '',
            'password' => $credentials['dbPassword'] ?? '',
            'host' => $credentials['dbHost'] ?? '',
            'port' => $credentials['dbPort'] ?? '',
            'driver' => 'pdo_mysql',
            'charset' => 'utf8mb4',
        ];

        return DriverManager::getConnection($connectionParams);
    }
}
