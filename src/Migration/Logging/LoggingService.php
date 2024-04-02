<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Logging\Log\LogEntryInterface;

#[Package('services-settings')]
class LoggingService implements LoggingServiceInterface
{
    protected array $logging = [];

    public function __construct(private readonly EntityRepository $loggingRepo)
    {
    }

    public function saveLogging(Context $context): void
    {
        if (empty($this->logging)) {
            return;
        }

        $this->loggingRepo->create($this->logging, $context);

        $this->logging = [];
    }

    public function addLogEntry(LogEntryInterface $logEntry): void
    {
        $this->logging[] = [
            'level' => $logEntry->getLevel(),
            'code' => $logEntry->getCode(),
            'title' => $logEntry->getTitle(),
            'description' => $logEntry->getDescription(),
            'parameters' => $logEntry->getParameters(),
            'titleSnippet' => $logEntry->getTitleSnippet(),
            'descriptionSnippet' => $logEntry->getDescriptionSnippet(),
            'entity' => $logEntry->getEntity(),
            'sourceId' => $logEntry->getSourceId(),
            'runId' => $logEntry->getRunId(),
        ];
    }
}
