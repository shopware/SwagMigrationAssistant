<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\Gateway\GatewayInterface;
use SwagMigrationAssistant\Migration\Profile\ProfileInterface;

#[Package('fundamentals@after-sales')]
class MigrationContext extends Struct implements MigrationContextInterface
{
    final public const SOURCE_CONTEXT = 'MIGRATION_CONNECTION_CHECK_FOR_RUNNING_MIGRATION';

    public function __construct(
        private SwagMigrationConnectionEntity $connection,
        private ?ProfileInterface $profile = null,
        private ?GatewayInterface $gateway = null,
        private ?DataSet $dataSet = null,
        private readonly string $runUuid = '',
        private int $offset = 0,
        private int $limit = 0,
    ) {
    }

    public function getProfile(): ProfileInterface
    {
        if ($this->profile === null) {
            throw MigrationException::migrationContextPropertyMissing('profile');
        }

        return $this->profile;
    }

    public function setProfile(ProfileInterface $profile): void
    {
        $this->profile = $profile;
    }

    public function getGateway(): GatewayInterface
    {
        if ($this->gateway === null) {
            throw MigrationException::migrationContextPropertyMissing('gateway');
        }

        return $this->gateway;
    }

    public function setGateway(GatewayInterface $gateway): void
    {
        $this->gateway = $gateway;
    }

    public function getConnection(): SwagMigrationConnectionEntity
    {
        return $this->connection;
    }

    public function setConnection(SwagMigrationConnectionEntity $connection): void
    {
        $this->connection = $connection;
    }

    public function getRunUuid(): string
    {
        return $this->runUuid;
    }

    public function getDataSet(): ?DataSet
    {
        return $this->dataSet;
    }

    public function setDataSet(DataSet $dataSet): void
    {
        $this->dataSet = $dataSet;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function setOffset(int $offset): void
    {
        $this->offset = $offset;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
    }
}
