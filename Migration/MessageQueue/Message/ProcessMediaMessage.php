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

#[Package('services-settings')]
class ProcessMediaMessage implements AsyncMessageInterface
{
    /**
     * @param array<int, string> $mediaFileIds
     */
    public function __construct(
        private array $mediaFileIds,
        private string $runId,
        private string $entityName,
        private Context $context
    ) {
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function setContext(Context $context): void
    {
        $this->context = $context;
    }

    /**
     * @param array<int, string> $mediaFileIds
     */
    public function setMediaFileIds(array $mediaFileIds): void
    {
        $this->mediaFileIds = $mediaFileIds;
    }

    public function setRunId(string $runId): void
    {
        $this->runId = $runId;
    }

    /**
     * @return string[]
     */
    public function getMediaFileIds(): array
    {
        return $this->mediaFileIds;
    }

    public function getRunId(): string
    {
        return $this->runId;
    }

    public function getEntityName(): string
    {
        return $this->entityName;
    }

    public function setEntityName(string $entityName): void
    {
        $this->entityName = $entityName;
    }
}
