<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway\Shopware55\Local;

use Doctrine\DBAL\Driver\PDOConnection;
use SwagMigrationNext\Gateway\GatewayInterface;
use SwagMigrationNext\Gateway\Shopware55\Local\Reader\Shopware55LocalReaderRegistryInterface;

class Shopware55LocalGateway implements GatewayInterface
{
    public const GATEWAY_TYPE = 'local';

    /**
     * @var Shopware55LocalReaderRegistryInterface
     */
    private $localReaderRegistry;

    /**
     * @var string
     */
    private $dbHost;

    /**
     * @var string
     */
    private $dbName;

    /**
     * @var string
     */
    private $dbPort;

    /**
     * @var string
     */
    private $dbUser;

    /**
     * @var string
     */
    private $dbPassword;

    public function __construct(
        Shopware55LocalReaderRegistryInterface $localReaderRegistry,
        string $dbHost,
        string $dbPort,
        string $dbName,
        string $dbUser,
        string $dbPassword
    ) {
        $this->localReaderRegistry = $localReaderRegistry;
        $this->dbHost = $dbHost;
        $this->dbPort = $dbPort;
        $this->dbName = $dbName;
        $this->dbUser = $dbUser;
        $this->dbPassword = $dbPassword;
    }

    public function read(string $entityName): array
    {
        $reader = $this->localReaderRegistry->getReader($entityName);

        $dsn = sprintf('mysql:dbname=%s;host=%s;port=%s', $this->dbName, $this->dbHost, $this->dbPort);
        $connection = new PDOConnection($dsn, $this->dbUser, $this->dbPassword);

        return $reader->read($connection);
    }
}
