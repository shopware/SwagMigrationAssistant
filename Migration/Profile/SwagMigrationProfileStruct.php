<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Profile;

use DateTime;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use SwagMigrationNext\Migration\Mapping\SwagMigrationMappingStruct;
use SwagMigrationNext\Migration\Run\SwagMigrationRunStruct;

class SwagMigrationProfileStruct extends Entity
{
    /**
     * @var string
     */
    protected $profile;

    /**
     * @var string
     */
    protected $gateway;

    /**
     * @var array
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
     * @var SwagMigrationRunStruct[]
     */
    protected $runs;

    /**
     * @var SwagMigrationMappingStruct[]
     */
    protected $mappings;

    public function getProfile(): string
    {
        return $this->profile;
    }

    public function setProfile(string $profile): void
    {
        $this->profile = $profile;
    }

    public function getGateway(): string
    {
        return $this->gateway;
    }

    public function setGateway(string $gateway): void
    {
        $this->gateway = $gateway;
    }

    public function getCredentialFields(): array
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
     * @return SwagMigrationRunStruct[]
     */
    public function getRuns(): array
    {
        return $this->runs;
    }

    /**
     * @param SwagMigrationRunStruct[] $data
     */
    public function setRuns(array $runs): void
    {
        $this->runs = $runs;
    }

    /**
     * @return SwagMigrationMappingStruct[]
     */
    public function getMappings(): array
    {
        return $this->mappings;
    }

    /**
     * @param SwagMigrationMappingStruct[] $mappings
     */
    public function setMappings(array $mappings): void
    {
        $this->mappings = $mappings;
    }
}
