<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration;

use Shopware\Core\Framework\Struct\Struct;

class MigrationContext extends Struct
{
    /**
     * @var string
     */
    private $profile;

    /**
     * @var string
     */
    private $entity;

    /**
     * @var string
     */
    private $gateway;

    /**
     * @var array
     */
    private $credentials;

    public function __construct(string $profile, string $gateway, string $entity, array $credentials)
    {
        $this->profile = $profile;
        $this->gateway = $gateway;
        $this->entity = $entity;
        $this->credentials = $credentials;
    }

    public function getProfile(): string
    {
        return $this->profile;
    }

    public function getEntity(): string
    {
        return $this->entity;
    }

    public function getGateway(): string
    {
        return $this->gateway;
    }

    public function getCredentials(): array
    {
        return $this->credentials;
    }

    public function getGatewayIdentifier(): string
    {
        return $this->getProfile() . $this->getGateway();
    }
}
