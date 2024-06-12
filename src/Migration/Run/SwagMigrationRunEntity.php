<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Run;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataCollection;
use SwagMigrationAssistant\Migration\Logging\SwagMigrationLoggingCollection;
use SwagMigrationAssistant\Migration\Media\SwagMigrationMediaFileCollection;

#[Package('services-settings')]
class SwagMigrationRunEntity extends Entity
{
    use EntityIdTrait;

    protected string $step;

    protected ?string $connectionId = null;

    protected ?SwagMigrationConnectionEntity $connection = null;

    protected ?array $totals = null;

    protected ?array $environmentInformation = null;

    protected ?MigrationProgress $progress = null;

    protected ?SwagMigrationDataCollection $data = null;

    protected ?SwagMigrationMediaFileCollection $mediaFiles = null;

    protected ?SwagMigrationLoggingCollection $logs = null;

    public function getConnectionId(): ?string
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

    public function getTotals(): ?array
    {
        return $this->totals;
    }

    public function setTotals(array $totals): void
    {
        $this->totals = $totals;
    }

    public function getEnvironmentInformation(): ?array
    {
        return $this->environmentInformation;
    }

    public function setEnvironmentInformation(array $environmentInformation): void
    {
        $this->environmentInformation = $environmentInformation;
    }

    public function getStep(): MigrationStep
    {
        $step = MigrationStep::tryFrom($this->step);
        if ($step === null) {
            throw MigrationException::undefinedRunStatus($this->step);
        }

        return $step;
    }

    public function getStepValue(): string
    {
        return $this->step;
    }

    /**
     * # Safety:
     * You are only allowed to use this method to set an initial step when creating a new run.
     * Every other step transition should go through RunTransitionService::transitionToRunStep
     * to avoid data races.
     */
    public function setStep(MigrationStep $step): void
    {
        $this->step = $step->value;
    }

    public function getProgress(): ?MigrationProgress
    {
        return $this->progress;
    }

    /**
     * # Safety:
     * Only the MigrationProcessHandler is allowed to call this method
     * (except for initial run creation, where this can also be called elsewhere).
     * This is important to prevent data races between http requests controllers and the message queue.
     */
    public function setProgress(MigrationProgress $progress): void
    {
        $this->progress = $progress;
    }

    public function getData(): ?SwagMigrationDataCollection
    {
        return $this->data;
    }

    public function setData(SwagMigrationDataCollection $data): void
    {
        $this->data = $data;
    }

    public function getMediaFiles(): ?SwagMigrationMediaFileCollection
    {
        return $this->mediaFiles;
    }

    public function setMediaFiles(SwagMigrationMediaFileCollection $mediaFiles): void
    {
        $this->mediaFiles = $mediaFiles;
    }

    public function getLogs(): ?SwagMigrationLoggingCollection
    {
        return $this->logs;
    }

    public function setLogs(SwagMigrationLoggingCollection $logs): void
    {
        $this->logs = $logs;
    }
}
