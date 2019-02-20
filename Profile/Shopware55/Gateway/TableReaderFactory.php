<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Gateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use GuzzleHttp\Client;
use SwagMigrationNext\Migration\MigrationContextInterface;
use SwagMigrationNext\Migration\Profile\TableReaderFactoryInterface;
use SwagMigrationNext\Migration\Profile\TableReaderInterface;
use SwagMigrationNext\Profile\Shopware55\Gateway\Api\Reader\Shopware55ApiTableReader;
use SwagMigrationNext\Profile\Shopware55\Gateway\Api\Shopware55ApiGateway;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalTableReader;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Shopware55LocalGateway;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class TableReaderFactory implements TableReaderFactoryInterface
{
    public function create(MigrationContextInterface $migrationContext): ?TableReaderInterface
    {
        if (
            $migrationContext->getProfileName() === Shopware55Profile::PROFILE_NAME
            && $migrationContext->getGatewayName() === Shopware55ApiGateway::GATEWAY_NAME
        ) {
            return new Shopware55ApiTableReader($this->getApiClient($migrationContext));
        }

        if (
            $migrationContext->getProfileName() === Shopware55Profile::PROFILE_NAME
            && $migrationContext->getGatewayName() === Shopware55LocalGateway::GATEWAY_NAME
        ) {
            return new Shopware55LocalTableReader($this->getConnection($migrationContext));
        }

        return null;
    }

    private function getApiClient(MigrationContextInterface $migrationContext): Client
    {
        $credentials = $migrationContext->getConnection()->getCredentialFields();

        $options = [
            'base_uri' => $credentials['endpoint'] . '/api/',
            'auth' => [$credentials['apiUser'], $credentials['apiKey'], 'digest'],
            'verify' => false,
        ];

        return new Client($options);
    }

    private function getConnection(MigrationContextInterface $migrationContext): Connection
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
