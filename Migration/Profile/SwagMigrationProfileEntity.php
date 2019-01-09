<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Profile;

use DateTime;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use SwagMigrationNext\Migration\Mapping\SwagMigrationMappingEntity;
use SwagMigrationNext\Migration\Run\SwagMigrationRunEntity;

class SwagMigrationProfileEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string|null
     */
    protected $profile;

    /**
     * @var string|null
     */
    protected $gateway;

    /**
     * @var array|null
     */
    protected $credentialFields;

    /**
     * @var DateTime
     */
    protected $createdAt;

    /**
     * @var DateTime|null
     */
    protected $updatedAt;

    /**
     * @var SwagMigrationRunEntity[]|null
     */
    protected $runs;

    /**
     * @var SwagMigrationMappingEntity[]|null
     */
    protected $mappings;

    public function getProfile(): ?string
    {
        return $this->profile;
    }

    public function setProfile(string $profile): void
    {
        $this->profile = $profile;
    }

    public function getGateway(): ?string
    {
        return $this->gateway;
    }

    public function setGateway(string $gateway): void
    {
        $this->gateway = $gateway;
    }

    public function getCredentialFields(): ?array
    {
        return $this->credentialFields;
    }

    public function setCredentialFields(array $credentialFields): void
    {
        $this->credentialFields = $credentialFields;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return SwagMigrationRunEntity[]|null
     */
    public function getRuns(): ?array
    {
        return $this->runs;
    }

    /**
     * @param SwagMigrationRunEntity[] $runs
     */
    public function setRuns(array $runs): void
    {
        $this->runs = $runs;
    }

    /**
     * @return SwagMigrationMappingEntity[]|null
     */
    public function getMappings(): ?array
    {
        return $this->mappings;
    }

    /**
     * @param SwagMigrationMappingEntity[] $mappings
     */
    public function setMappings(array $mappings): void
    {
        $this->mappings = $mappings;
    }
}
