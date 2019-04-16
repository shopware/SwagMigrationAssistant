<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Setting;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use SwagMigrationNext\Migration\Connection\SwagMigrationConnectionEntity;

class GeneralSettingEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string|null
     */
    protected $selectedConnectionId;

    /**
     * @var SwagMigrationConnectionEntity|null
     */
    protected $selectedConnection;

    /**
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * @var \DateTime|null
     */
    protected $updatedAt;

    public function getSelectedConnectionId(): ?string
    {
        return $this->selectedConnectionId;
    }

    public function setSelectedConnectionId(string $selectedConnectionId): void
    {
        $this->selectedConnectionId = $selectedConnectionId;
    }

    public function getSelectedConnection(): ?SwagMigrationConnectionEntity
    {
        return $this->selectedConnection;
    }

    public function setSelectedConnection(SwagMigrationConnectionEntity $selectedConnection): void
    {
        $this->selectedConnection = $selectedConnection;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
