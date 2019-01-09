<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Setting;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use SwagMigrationNext\Migration\Profile\SwagMigrationProfileEntity;

class GeneralSettingEntity extends Entity
{
    /**
     * @var string|null
     */
    protected $selectedProfileId;

    /**
     * @var SwagMigrationProfileEntity|null
     */
    protected $selectedProfile;

    public function getSelectedProfileId(): ?string
    {
        return $this->selectedProfileId;
    }

    public function setSelectedProfileId(string $selectedProfileId): void
    {
        $this->selectedProfileId = $selectedProfileId;
    }

    public function getSelectedProfile(): ?SwagMigrationProfileEntity
    {
        return $this->selectedProfile;
    }

    public function setSelectedProfile(SwagMigrationProfileEntity $selectedProfile): void
    {
        $this->selectedProfile = $selectedProfile;
    }
}
