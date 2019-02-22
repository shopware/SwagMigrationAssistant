<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration;

use Shopware\Core\Framework\Struct\Struct;
use SwagMigrationNext\Migration\Connection\SwagMigrationConnectionEntity;

class MigrationContext extends Struct implements MigrationContextInterface
{
    public const SOURCE_CONTEXT = 'MIGRATION_CONNECTION_CHECK_FOR_RUNNING_MIGRATION';

    /**
     * @var string
     */
    private $runUuid;

    /**
     * @var SwagMigrationConnectionEntity|null
     */
    private $connection;

    /**
     * @var string
     */
    private $entity;

    /**
     * @var int
     */
    private $offset;

    /**
     * @var int
     */
    private $limit;

    public function __construct(
        string $runUuid,
        ?SwagMigrationConnectionEntity $connection,
        string $entity,
        int $offset,
        int $limit
    ) {
        $this->runUuid = $runUuid;
        $this->connection = $connection;
        $this->entity = $entity;
        $this->offset = $offset;
        $this->limit = $limit;
    }

    public function getRunUuid(): string
    {
        return $this->runUuid;
    }

    public function getConnection(): ?SwagMigrationConnectionEntity
    {
        return $this->connection;
    }

    public function getEntity(): string
    {
        return $this->entity;
    }

    public function getProfileName(): ?string
    {
        if ($this->connection === null) {
            return null;
        }

        return $this->connection->getProfile()->getName();
    }

    public function getGatewayName(): ?string
    {
        if ($this->connection === null) {
            return null;
        }

        return $this->connection->getProfile()->getGatewayName();
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }
}
