<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration;

use Shopware\Core\Framework\Struct\Struct;

class MigrationContext extends Struct
{
    /**
     * @var string
     */
    private $runUuid;

    /**
     * @var string
     */
    private $profileId;

    /**
     * @var string
     */
    private $profileName;

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

    /**
     * @var int
     */
    private $offset;

    /**
     * @var int
     */
    private $limit;

    /**
     * @var string
     */
    private $catalogId;

    /**
     * @var string
     */
    private $salesChannelId;


    public function __construct(
        string $runUuid,
        string $profileId,
        string $profileName,
        string $gateway,
        string $entity,
        array $credentials,
        int $offset,
        int $limit,
        ?string $catalogId = null,
        ?string $salesChannelId = null
    ) {
        $this->runUuid = $runUuid;
        $this->profileId = $profileId;
        $this->profileName = $profileName;
        $this->gateway = $gateway;
        $this->entity = $entity;
        $this->credentials = $credentials;
        $this->offset = $offset;
        $this->limit = $limit;
        $this->catalogId = $catalogId;
        $this->salesChannelId = $salesChannelId;
    }

    public function getRunUuid(): string
    {
        return $this->runUuid;
    }

    public function getProfileId(): string
    {
        return $this->profileId;
    }

    public function getProfileName(): string
    {
        return $this->profileName;
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
        return $this->getProfileName() . $this->getGateway();
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getCatalogId(): ?string
    {
        return $this->catalogId;
    }

    public function getSalesChannelId(): ?string
    {
        return $this->salesChannelId;
    }
}
