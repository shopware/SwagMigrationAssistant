<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway\Shopware55\Local;

use Doctrine\DBAL\DriverManager;
use Shopware\Core\Content\Product\ProductDefinition;
use SwagMigrationNext\Gateway\GatewayInterface;
use SwagMigrationNext\Gateway\Shopware55\Local\Reader\Shopware55LocalProductReader;
use SwagMigrationNext\Gateway\Shopware55\Local\Reader\Shopware55LocalReaderNotFoundException;

class Shopware55LocalGateway implements GatewayInterface
{
    public const GATEWAY_TYPE = 'local';

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
        string $dbHost,
        string $dbPort,
        string $dbName,
        string $dbUser,
        string $dbPassword
    ) {
        $this->dbHost = $dbHost;
        $this->dbPort = $dbPort;
        $this->dbName = $dbName;
        $this->dbUser = $dbUser;
        $this->dbPassword = $dbPassword;
    }

    public function read(string $entityName, int $offset, int $limit): array
    {
        $connectionParams = [
            'dbname' => $this->dbName,
            'user' => $this->dbUser,
            'password' => $this->dbPassword,
            'host' => $this->dbHost,
            'port' => $this->dbPort,
            'driver' => 'pdo_mysql',
            'charset' => 'utf8mb4',
        ];

        $connection = DriverManager::getConnection($connectionParams);

        switch ($entityName) {
            case ProductDefinition::getEntityName():
                $reader = new Shopware55LocalProductReader();

                return $reader->read($connection);

            default:
                throw new Shopware55LocalReaderNotFoundException($entityName);
        }
    }
}
