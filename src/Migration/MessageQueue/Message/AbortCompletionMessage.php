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
use SwagMigrationAssistant\Migration\Run\MigrationProgress;

#[Package('fundamentals@after-sales')]
readonly class AbortCompletionMessage implements AsyncMessageInterface
{
    public function __construct(
        private string $runId,
        private MigrationProgress $progress,
        private Context $context,
    ) {
    }

    public function getRunId(): string
    {
        return $this->runId;
    }

    public function getProgress(): MigrationProgress
    {
        return $this->progress;
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}
