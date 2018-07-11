<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway\Shopware5\Api;

use SwagMigrationNext\Gateway\GatewayInterface;

class Gateway implements GatewayInterface
{
    /**
     * @var string
     */
    private $endpoint;

    /**
     * @var string
     */
    private $apiUser;

    /**
     * @var string
     */
    private $apiKey;

    public function __construct(string $endpoint, string $apiUser, string $apiKey)
    {
        $this->endpoint = $endpoint;
        $this->apiUser = $apiUser;
        $this->apiKey = $apiKey;
    }

    public function read(string $entityType): array
    {
        // TODO use properties to create connection
        return require __DIR__ . '/../../../Test/_fixtures/product_data.php';
    }
}
