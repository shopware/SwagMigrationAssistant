<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Connection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\ConnectionException;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Gateway\HttpClientInterface;
use SwagMigrationAssistant\Migration\Gateway\HttpSimpleClient;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use Symfony\Contracts\Service\ResetInterface;

#[Package('fundamentals@after-sales')]
class ConnectionFactory implements ConnectionFactoryInterface, ResetInterface
{
    private ?Connection $externalConnection = null;

    public function createApiClient(MigrationContextInterface $migrationContext): HttpClientInterface
    {
        $connection = $migrationContext->getConnection();

        if ($connection === null) {
            throw MigrationException::noConnectionFound();
        }

        $credentials = $connection->getCredentialFields();

        if (empty($credentials)) {
            throw MigrationException::invalidConnectionCredentials();
        }

        $options = [
            'base_uri' => $credentials['endpoint'] . '/api/',
            'auth' => [$credentials['apiUser'], $credentials['apiKey'], 'digest'],
        ];

        return new HttpSimpleClient($options);
    }

    public function createDatabaseConnection(MigrationContextInterface $migrationContext): Connection
    {
        if ($this->externalConnection instanceof Connection) {
            $this->ensureConnectionAttributes($this->externalConnection);

            \assert(
                $this->externalConnection instanceof Connection,
                '$this->externalConnection should not be null at this point'
            );

            return $this->externalConnection;
        }

        $connection = $migrationContext->getConnection();

        if ($connection === null) {
            throw MigrationException::noConnectionFound();
        }

        $credentials = $connection->getCredentialFields();

        if ($credentials === null) {
            throw MigrationException::invalidConnectionCredentials();
        }

        $connectionParams = [
            'dbname' => (string) ($credentials['dbName'] ?? ''),
            'user' => (string) ($credentials['dbUser'] ?? ''),
            'password' => (string) ($credentials['dbPassword'] ?? ''),
            'host' => (string) ($credentials['dbHost'] ?? ''),
            'charset' => 'utf8mb4',
            'driver' => 'pdo_mysql',
            'driverOptions' => [
                \PDO::ATTR_STRINGIFY_FETCHES => true,
            ],
        ];

        if (isset($credentials['dbPort'])) {
            $connectionParams['port'] = (int) $credentials['dbPort'];
        }

        $this->externalConnection = DriverManager::getConnection($connectionParams);
        $this->ensureConnectionAttributes($this->externalConnection);

        \assert(
            $this->externalConnection instanceof Connection,
            '$this->externalConnection should not be null at this point'
        );

        return $this->externalConnection;
    }

    public function reset(): void
    {
        if ($this->externalConnection instanceof Connection) {
            $this->externalConnection->close();
        }

        $this->externalConnection = null;
    }

    private function ensureConnectionAttributes(Connection $connection): void
    {
        try {
            $nativeConnection = $connection->getNativeConnection();
            // we can assume that the underlying connection always uses the 'pdo_mysql' driver,
            // as specified in $connectionParams passed to DriverManager::getConnection above
            if (!$nativeConnection instanceof \PDO) {
                throw MigrationException::databaseConnectionAttributesWrong();
            }

            $successfullySet = $nativeConnection->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, true);
            if (!$successfullySet) {
                throw MigrationException::databaseConnectionAttributesWrong();
            }
        } catch (ConnectionException $exception) {
            // $connection->getNativeConnection() tries to connect to the DB
            // we want to ignore connection errors at this point
        }
    }
}
