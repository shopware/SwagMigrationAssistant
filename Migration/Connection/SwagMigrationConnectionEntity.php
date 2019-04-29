<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Connection;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use SwagMigrationNext\Migration\Mapping\SwagMigrationMappingCollection;
use SwagMigrationNext\Migration\Premapping\PremappingStruct;
use SwagMigrationNext\Migration\Profile\SwagMigrationProfileEntity;
use SwagMigrationNext\Migration\Run\SwagMigrationRunCollection;
use SwagMigrationNext\Migration\Setting\GeneralSettingCollection;

class SwagMigrationConnectionEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string|null
     */
    protected $name;

    /**
     * @var array|null
     */
    protected $credentialFields;

    /**
     * @var PremappingStruct[]
     */
    protected $premapping;

    /**
     * @var string
     */
    protected $profileId;

    /**
     * @var SwagMigrationProfileEntity
     */
    protected $profile;

    /**
     * @var SwagMigrationRunCollection|null
     */
    protected $runs;

    /**
     * @var SwagMigrationMappingCollection|null
     */
    protected $mappings;

    /**
     * @var GeneralSettingCollection|null
     */
    protected $settings;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCredentialFields(): ?array
    {
        return $this->credentialFields;
    }

    public function setCredentialFields(array $credentialFields): void
    {
        $this->credentialFields = $credentialFields;
    }

    public function getPremapping(): ?array
    {
        return $this->premapping;
    }

    /**
     * @param PremappingStruct[] $premapping
     */
    public function setPremapping(array $premapping): void
    {
        $this->premapping = $premapping;
    }

    public function getProfileId(): ?string
    {
        return $this->profileId;
    }

    public function setProfileId(string $profileId): void
    {
        $this->profileId = $profileId;
    }

    public function getProfile(): SwagMigrationProfileEntity
    {
        return $this->profile;
    }

    public function setProfile(SwagMigrationProfileEntity $profile): void
    {
        $this->profile = $profile;
    }

    public function getRuns(): ?SwagMigrationRunCollection
    {
        return $this->runs;
    }

    public function setRuns(SwagMigrationRunCollection $runs): void
    {
        $this->runs = $runs;
    }

    public function getMappings(): ?SwagMigrationMappingCollection
    {
        return $this->mappings;
    }

    public function setMappings(SwagMigrationMappingCollection $mappings): void
    {
        $this->mappings = $mappings;
    }

    public function getSettings(): ?GeneralSettingCollection
    {
        return $this->settings;
    }

    public function setSettings(GeneralSettingCollection $settings): void
    {
        $this->settings = $settings;
    }
}
