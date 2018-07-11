<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway\Shopware5\Local;

use SwagMigrationNext\Gateway\GatewayInterface;

class Gateway implements GatewayInterface
{
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

    public function __construct(string $dbName, string $dbUser, string $dbPassword)
    {
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
