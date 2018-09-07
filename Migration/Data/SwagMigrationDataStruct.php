<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Data;

use DateTime;
use Shopware\Core\Framework\ORM\Entity;

class SwagMigrationDataStruct extends Entity
{
    /**
     * @var string
     */
    protected $profile;

    /**
     * @var string
     */
    protected $entity;

    /**
     * @var array
     */
    protected $raw;

    /**
     * @var array
     */
    protected $converted;

    /**
     * @var array
     */
    protected $unmapped;

    /**
     * @var DateTime
     */
    protected $createdAt;

    /**
     * @var DateTime|null
     */
    protected $updatedAt;

    public function getProfile(): string
    {
        return $this->profile;
    }

    public function setProfile(string $profile): void
    {
        $this->profile = $profile;
    }

    public function getEntity(): string
    {
        return $this->entity;
    }

    public function setEntity(string $entity): void
    {
        $this->entity = $entity;
    }

    public function getRaw(): ?array
    {
        return $this->raw;
    }

    public function setRaw(?array $raw): void
    {
        $this->raw = $raw;
    }

    public function getConverted(): ?array
    {
        return $this->converted;
    }

    public function setConverted(?array $converted): void
    {
        $this->converted = $converted;
    }

    public function getUnmapped(): ?array
    {
        return $this->unmapped;
    }

    public function setUnmapped(?array $unmapped): void
    {
        $this->unmapped = $unmapped;
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
}
