<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Service;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;
use SwagMigrationAssistant\Migration\Run\RunProgress;

#[Package('services-settings')]
class ProgressState extends Struct
{
    final public const STATUS_WAITING = -1;
    final public const STATUS_PREMAPPING = 0;
    final public const STATUS_FETCH_DATA = 1;
    final public const STATUS_WRITE_DATA = 2;
    final public const STATUS_DOWNLOAD_DATA = 3;

    public function __construct(
        protected bool $migrationRunning,
        protected bool $validMigrationRunToken,
        protected ?string $runId = null,
        protected ?string $accessToken = null,
        protected int $status = ProgressState::STATUS_WAITING,
        protected ?string $entity = null,
        protected int $finishedCount = 0,
        protected int $entityCount = 0,
        protected array $runProgress = []
    ) {
    }

    public function isMigrationRunning(): bool
    {
        return $this->migrationRunning;
    }

    public function isMigrationRunTokenValid(): bool
    {
        return $this->validMigrationRunToken;
    }

    public function getRunId(): ?string
    {
        return $this->runId;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getEntity(): ?string
    {
        return $this->entity;
    }

    public function getFinishedCount(): int
    {
        return $this->finishedCount;
    }

    public function getEntityCount(): int
    {
        return $this->entityCount;
    }

    public function isValidMigrationRunToken(): bool
    {
        return $this->validMigrationRunToken;
    }

    /**
     * @return RunProgress[]
     */
    public function getRunProgress(): array
    {
        return $this->runProgress;
    }

    public function setRunProgress(array $runProgress): void
    {
        $this->runProgress = $runProgress;
    }
}
