<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Mapping;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;

#[Package('fundamentals@after-sales')]
class SwagMigrationMappingEntity extends Entity
{
    use EntityIdTrait;

    protected string $connectionId;

    protected ?SwagMigrationConnectionEntity $connection = null;

    protected ?string $entity;

    protected ?string $oldIdentifier;

    protected ?string $entityUuid;

    protected ?string $entityValue;

    protected ?string $checksum;

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $additionalData;

    public function getConnectionId(): string
    {
        return $this->connectionId;
    }

    public function setConnectionId(string $connectionId): void
    {
        $this->connectionId = $connectionId;
    }

    public function getConnection(): ?SwagMigrationConnectionEntity
    {
        return $this->connection;
    }

    public function setConnection(SwagMigrationConnectionEntity $connection): void
    {
        $this->connection = $connection;
    }

    public function getEntity(): ?string
    {
        return $this->entity;
    }

    public function setEntity(string $entity): void
    {
        $this->entity = $entity;
    }

    public function getOldIdentifier(): ?string
    {
        return $this->oldIdentifier;
    }

    public function setOldIdentifier(string $oldIdentifier): void
    {
        $this->oldIdentifier = $oldIdentifier;
    }

    public function getEntityUuid(): ?string
    {
        return $this->entityUuid;
    }

    public function setEntityUuid(string $entityUuid): void
    {
        $this->entityUuid = $entityUuid;
    }

    public function getEntityValue(): ?string
    {
        return $this->entityValue;
    }

    public function setEntityValue(?string $entityValue): void
    {
        $this->entityValue = $entityValue;
    }

    public function getChecksum(): ?string
    {
        return $this->checksum;
    }

    public function setChecksum(?string $checksum): void
    {
        $this->checksum = $checksum;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getAdditionalData(): ?array
    {
        return $this->additionalData;
    }

    /**
     * @param array<string, mixed> $additionalData
     */
    public function setAdditionalData(array $additionalData): void
    {
        $this->additionalData = $additionalData;
    }
}
