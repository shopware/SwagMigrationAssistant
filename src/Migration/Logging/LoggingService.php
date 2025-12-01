<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Logging\Log\Builder\MigrationLogEntry;

#[Package('fundamentals@after-sales')]
class LoggingService implements LoggingServiceInterface
{
    protected array $logging = [];

    /**
     * @param EntityRepository<SwagMigrationLoggingCollection> $loggingRepo
     */
    public function __construct(
        private readonly EntityRepository $loggingRepo,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function reset(): void
    {
        if (!empty($this->logging)) {
            $this->logger->error('SwagMigrationAssistant: Migration logging was not empty on calling reset.');
        }

        $this->logging = [];
    }

    public function saveLogging(Context $context): void
    {
        if (empty($this->logging)) {
            return;
        }

        try {
            $this->loggingRepo->create($this->logging, $context);
        } catch (\Exception) {
            $this->writePerEntry($context);
        } finally {
            $this->logging = [];
        }
    }

    public function addLogEntry(MigrationLogEntry $logEntry): void
    {
        $this->logging[] = [
            'runId' => $logEntry->getRunId(),
            'profileName' => $logEntry->getProfileName(),
            'gatewayName' => $logEntry->getGatewayName(),
            'level' => $logEntry->getLevel(),
            'code' => $logEntry->getCode(),
            'userFixable' => $logEntry->isUserFixable(),
            'entityId' => $logEntry->getEntityId(),
            'entityName' => $logEntry->getEntityName(),
            'fieldName' => $logEntry->getFieldName(),
            'fieldSourcePath' => $logEntry->getFieldSourcePath(),
            'sourceData' => $logEntry->getSourceData(),
            'convertedData' => $logEntry->getConvertedData(),
            'exceptionMessage' => $logEntry->getExceptionMessage(),
            'exceptionTrace' => $logEntry->getExceptionTrace(),
        ];
    }

    /**
     * @param array<array-key, mixed> $keys
     * @param callable(array-key $key, mixed|null $value): MigrationLogEntry $callback
     */
    public function addLogForEach(array $keys, callable $callback): void
    {
        foreach ($keys as $key => $value) {
            if (\array_is_list($keys)) {
                $this->addLogEntry($callback($value, null));
            } else {
                $this->addLogEntry($callback($key, $value));
            }
        }
    }

    private function writePerEntry(Context $context): void
    {
        foreach ($this->logging as $log) {
            try {
                $this->loggingRepo->create([$log], $context);
            } catch (\Exception) {
                $this->logger->error('SwagMigrationAssistant: Could not write log entry: ', $log);
            }
        }
    }
}
