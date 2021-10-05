<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Connection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use GuzzleHttp\Client;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

class ConnectionFactory implements ConnectionFactoryInterface
{
    public function createApiClient(MigrationContextInterface $migrationContext, bool $verify = false): ?Client
    {
        $connection = $migrationContext->getConnection();

        if ($connection === null) {
            return null;
        }

        $credentials = $connection->getCredentialFields();

        if ($credentials === null) {
            return null;
        }

        $options = [
            'base_uri' => $credentials['endpoint'] . '/api/',
            'auth' => [$credentials['apiUser'], $credentials['apiKey'], 'digest'],
            'connect_timeout' => 5.0,
            'verify' => $verify,
        ];

        return new Client($options);
    }

    public function createDatabaseConnection(MigrationContextInterface $migrationContext): ?Connection
    {
        $connection = $migrationContext->getConnection();

        if ($connection === null) {
            return null;
        }

        $credentials = $connection->getCredentialFields();

        if ($credentials === null) {
            return null;
        }

        $connectionParams = [
            'dbname' => (string) ($credentials['dbName'] ?? ''),
            'user' => (string) ($credentials['dbUser'] ?? ''),
            'password' => (string) ($credentials['dbPassword'] ?? ''),
            'host' => (string) ($credentials['dbHost'] ?? ''),
            'driver' => 'pdo_mysql',
            'charset' => 'utf8mb4',
        ];

        if (isset($credentials['dbPort'])) {
            $connectionParams['port'] = (int) $credentials['dbPort'];
        }

        return DriverManager::getConnection($connectionParams);
    }
}
