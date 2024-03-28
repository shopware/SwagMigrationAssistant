<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;

abstract class LocalConnectionTestCase extends TestCase
{
    protected ?string $runId = null;

    protected ?SwagMigrationConnectionEntity $migrationConnectionEntity = null;

    protected ?ConnectionFactory $connectionFactory = null;

    private ?MigrationContextInterface $migrationContext = null;

    protected function setUp(): void
    {
        $this->getExternalConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->getExternalConnection()->rollBack();
    }

    #[Before]
    public function before(): void
    {
        $dbUrlParts = \parse_url($_SERVER['DATABASE_URL'] ?? '') ?: [];
        $dbUrlParts['path'] ??= 'root';

        $this->runId = Uuid::randomHex();
        $this->migrationConnectionEntity = new SwagMigrationConnectionEntity();
        $this->migrationConnectionEntity->setId(Uuid::randomHex());
        $this->migrationConnectionEntity->setCredentialFields(
            [
                'dbName' => Shopware5DatabaseConnection::DB_NAME,
                'dbUser' => $dbUrlParts['user'] ?? 'root',
                'dbPassword' => $dbUrlParts['pass'] ?? '',
                'dbHost' => $dbUrlParts['host'] ?? 'localhost',
                'dbPort' => $dbUrlParts['port'] ?? 3306,
            ]
        );
        $this->migrationConnectionEntity->setProfileName(Shopware55Profile::PROFILE_NAME);
        $this->migrationConnectionEntity->setGatewayName(ShopwareLocalGateway::GATEWAY_NAME);

        $this->getMigrationContext();

        static::assertTrue($this->getExternalConnection()->isConnected());
    }

    abstract protected function getDataSet(): DataSet;

    protected function setLimitAndOffset(int $limit, int $offset): void
    {
        $reflectionClass = new \ReflectionClass($this->getMigrationContext());
        (new \ReflectionProperty($reflectionClass->getName(), 'limit'))->setValue($this->getMigrationContext(), $limit);
        (new \ReflectionProperty($reflectionClass->getName(), 'offset'))->setValue($this->getMigrationContext(), $offset);
    }

    protected function getExternalConnection(): Connection
    {
        $connection = $this->getConnectionFactory()->createDatabaseConnection($this->getMigrationContext());

        if (!$connection instanceof Connection) {
            throw new \RuntimeException('Connection could not be created');
        }

        return $connection;
    }

    protected function getConnectionFactory(): ConnectionFactory
    {
        if ($this->connectionFactory instanceof ConnectionFactory) {
            return $this->connectionFactory;
        }

        $this->connectionFactory = new ConnectionFactory();

        return $this->connectionFactory;
    }

    protected function getMigrationContext(): MigrationContextInterface
    {
        if ($this->migrationContext instanceof MigrationContextInterface) {
            return $this->migrationContext;
        }

        if ($this->runId === null) {
            throw new \RuntimeException('RunId is not set. Please call before() method first.');
        }

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->migrationConnectionEntity,
            $this->runId,
            $this->getDataSet(),
            0,
            10
        );

        $this->migrationContext->setGateway(new DummyLocalGateway());

        return $this->migrationContext;
    }
}
