<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway\Shopware55\Local;

use SwagMigrationNext\Gateway\GatewayInterface;

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
    private $dbUser;

    /**
     * @var string
     */
    private $dbPassword;

    public function __construct(string $dbHost, string $dbName, string $dbUser, string $dbPassword)
    {
        $this->dbHost = $dbHost;
        $this->dbName = $dbName;
        $this->dbUser = $dbUser;
        $this->dbPassword = $dbPassword;
    }

    public function read(string $entityType): array
    {
        // TODO use properties to read directly from database
        return require __DIR__ . '/../../../Test/_fixtures/product_data.php';
    }
}
