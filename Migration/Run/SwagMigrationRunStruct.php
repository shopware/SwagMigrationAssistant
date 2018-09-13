<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Run;

use DateTime;
use Shopware\Core\Framework\ORM\Entity;
use SwagMigrationNext\Migration\Data\SwagMigrationDataStruct;

class SwagMigrationRunStruct extends Entity
{
    /**
     * @var string
     */
    protected $profile;

    /**
     * @var array
     */
    protected $totals;

    /**
     * @var DateTime
     */
    protected $createdAt;

    /**
     * @var DateTime|null
     */
    protected $updatedAt;

    /**
     * @var SwagMigrationDataStruct[]
     */
    protected $data;

    public function getProfile(): string
    {
        return $this->profile;
    }

    public function setProfile(string $profile): void
    {
        $this->profile = $profile;
    }

    public function getTotals(): array
    {
        return $this->totals;
    }

    public function setTotals(array $totals): void
    {
        $this->totals = $totals;
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

    public function setUpdatedAt(?DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return SwagMigrationDataStruct[]
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param SwagMigrationDataStruct[] $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }
}
