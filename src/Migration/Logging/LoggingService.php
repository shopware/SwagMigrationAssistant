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
use Symfony\Contracts\Service\ResetInterface;

#[Package('fundamentals@after-sales')]
class LoggingService implements LoggingServiceInterface, ResetInterface
{
    final public const BUFFER_SIZE = 50;

    final public const TRACE_ITEM_LIMIT = 10;

    /**
     * @var array<array-key, array<string, mixed>>
     */
    protected array $buffer = [];

    /**
     * @internal
     *
     * @param EntityRepository<SwagMigrationLoggingCollection> $loggingRepo
     */
    public function __construct(
        private readonly EntityRepository $loggingRepo,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __destruct()
    {
        if (empty($this->buffer)) {
            return;
        }

        try {
            $this->flush();
        } catch (\Throwable $e) {
            $this->logger->error(
                'SwagMigrationAssistant: Could not flush log buffer in destructor.',
                ['exception' => $e]
            );
        }
    }

    public function reset(): void
    {
        $this->flush();
    }

    public function flush(): void
    {
        if (empty($this->buffer)) {
            return;
        }

        try {
            $this->loggingRepo->create(
                $this->buffer,
                Context::createDefaultContext(),
            );
        } catch (\Exception) {
            $this->writePerEntry();
        } finally {
            $this->buffer = [];
        }
    }

    public function log(MigrationLogEntry $logEntry): self
    {
        $trace = $logEntry->getExceptionTrace();

        if ($trace !== null && \count($trace) > self::TRACE_ITEM_LIMIT) {
            $trace = \array_slice($trace, 0, self::TRACE_ITEM_LIMIT);
        }

        $this->buffer[] = [
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
            'exceptionTrace' => $trace,
        ];

        if (\count($this->buffer) >= self::BUFFER_SIZE) {
            $this->flush();
        }

        return $this;
    }

    /**
     * @param array<array-key, mixed> $keys
     * @param callable(array-key $key, mixed|null $value): MigrationLogEntry $callback
     */
    public function addLogForEach(array $keys, callable $callback): void
    {
        foreach ($keys as $key => $value) {
            if (\array_is_list($keys)) {
                $this->log($callback($value, null));
            } else {
                $this->log($callback($key, $value));
            }
        }
    }

    private function writePerEntry(): void
    {
        foreach ($this->buffer as $log) {
            try {
                $this->loggingRepo->create(
                    [$log],
                    Context::createDefaultContext(),
                );
            } catch (\Exception) {
                $this->logger->error('SwagMigrationAssistant: Could not write log entry: ', $log);
            }
        }
    }
}
