<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\MessageQueue\Message;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\MessageQueue\AsyncMessageInterface;

#[Package('fundamentals@after-sales')]
readonly class ResetChecksumMessage implements AsyncMessageInterface
{
    public function __construct(
        private string $connectionId,
        private Context $context,
        private ?string $runId = null,
        private ?string $entity = null,
        private ?int $totalMappings = null,
        private int $processedMappings = 0,
        private bool $isPartOfAbort = false,
    ) {
    }

    public function getConnectionId(): string
    {
        return $this->connectionId;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getRunId(): ?string
    {
        return $this->runId;
    }

    public function getEntity(): ?string
    {
        return $this->entity;
    }

    public function getTotalMappings(): ?int
    {
        return $this->totalMappings;
    }

    public function getProcessedMappings(): int
    {
        return $this->processedMappings;
    }

    public function isPartOfAbort(): bool
    {
        return $this->isPartOfAbort;
    }
}
