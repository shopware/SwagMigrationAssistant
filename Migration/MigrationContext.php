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
    private $entityType;

    /**
     * @var string
     */
    private $gatewayName;

    /**
     * @var array
     */
    private $credentials;

    public function __construct(string $profileName, string $entityType, string $gatewayName, array $credentials)
    {
        $this->profileName = $profileName;
        $this->entityType = $entityType;
        $this->gatewayName = $gatewayName;
        $this->credentials = $credentials;
    }

    public function getProfileName(): string
    {
        return $this->profileName;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
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
