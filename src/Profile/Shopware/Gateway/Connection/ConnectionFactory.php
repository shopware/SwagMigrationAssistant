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
use SwagMigrationAssistant\Migration\Gateway\HttpClientInterface;
use SwagMigrationAssistant\Migration\Gateway\HttpSimpleClient;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use Symfony\Contracts\Service\ResetInterface;

#[Package('services-settings')]
class ConnectionFactory implements ConnectionFactoryInterface, ResetInterface
{
    private ?Connection $externalConnection = null;

    public function createApiClient(MigrationContextInterface $migrationContext, bool $verify = false): ?HttpClientInterface
    {
        $connection = $migrationContext->getConnection();

        if ($connection === null) {
            return null;
        }

        $credentials = $connection->getCredentialFields();

        if (empty($credentials)) {
            return null;
        }

        $options = [
            'base_uri' => $credentials['endpoint'] . '/api/',
            'auth' => [$credentials['apiUser'], $credentials['apiKey'], 'digest'],
        ];

        return new HttpSimpleClient($options);
    }

    public function createDatabaseConnection(MigrationContextInterface $migrationContext): ?Connection
    {
        if ($this->externalConnection instanceof Connection) {
            return $this->externalConnection;
        }

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

        $this->externalConnection = DriverManager::getConnection($connectionParams);

        try {
            if (\is_object($this->externalConnection->getNativeConnection()) && \method_exists($this->externalConnection->getNativeConnection(), 'setAttribute')) {
                $this->externalConnection->getNativeConnection()->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, true);
            }
        } catch (ConnectionException $exception) {
            // nth
        }

        return $this->externalConnection;
    }

    public function reset(): void
    {
        $this->externalConnection = null;
    }
}
