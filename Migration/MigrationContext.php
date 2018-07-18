<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration;

use Shopware\Core\Framework\Struct\Struct;

class MigrationContext extends Struct
{
    /**
     * @var string
     */
    private $profileName;

    /**
     * @var string
     */
    private $entityName;

    /**
     * @var string
     */
    private $gatewayName;

    /**
     * @var array
     */
    private $credentials;

    public function __construct(string $profileName, string $gatewayName, string $entityName, array $credentials)
    {
        $this->profileName = $profileName;
        $this->gatewayName = $gatewayName;
        $this->entityName = $entityName;
        $this->credentials = $credentials;
    }

    public function getProfileName(): string
    {
        return $this->profileName;
    }

    public function getEntityName(): string
    {
        return $this->entityName;
    }

    public function getGatewayName(): string
    {
        return $this->gatewayName;
    }

    public function getCredentials(): array
    {
        return $this->credentials;
    }

    public function getGatewayIdentifier(): string
    {
        return $this->getProfileName() . $this->getGatewayName();
    }
}
