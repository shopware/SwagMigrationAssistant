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

    /**
     * MigrationContext constructor.
     *
     * @param string $profileName
     * @param string $entityType
     * @param string $gatewayName
     * @param array  $credentials
     */
    public function __construct(string $profileName, string $entityType, string $gatewayName, array $credentials)
    {
        $this->profileName = $profileName;
        $this->entityType = $entityType;
        $this->gatewayName = $gatewayName;
        $this->credentials = $credentials;
    }

    /**
     * @return string
     */
    public function getProfileName(): string
    {
        return $this->profileName;
    }

    /**
     * @return string
     */
    public function getEntityType(): string
    {
        return $this->entityType;
    }

    /**
     * @return string
     */
    public function getGatewayName(): string
    {
        return $this->gatewayName;
    }

    /**
     * @return array
     */
    public function getCredentials(): array
    {
        return $this->credentials;
    }

    public function getGatewayIdentifier(): string
    {
        return $this->getProfileName() . $this->getGatewayName();
    }
}
