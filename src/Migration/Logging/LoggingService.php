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
use Shopware\Core\Framework\Util\Hasher;
use SwagMigrationAssistant\Migration\Logging\Log\Builder\MigrationLogEntry;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use Symfony\Contracts\Service\ResetInterface;

#[Package('fundamentals@after-sales')]
class LoggingService implements LoggingServiceInterface, ResetInterface
{
    final public const BATCH_SIZE = 100;

    /**
     * @var array <array-key, array<string, mixed>>
     */
    protected array $buffer = [];

    protected ?MigrationContextInterface $migrationContext = null;

    protected ?Context $context = null;

    /**
     * @param EntityRepository<SwagMigrationLoggingCollection> $loggingRepo
     */
    public function __construct(
        private readonly EntityRepository $loggingRepo,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __destruct()
    {
        if (empty($this->buffer) || $this->context === null) {
            return;
        }

        $this->saveLogging($this->context);
    }

    public function reset(): void
    {
        $this->buffer = [];
    }

    public function saveLogging(Context $context): void
    {
        if (empty($this->buffer)) {
            return;
        }

        $data = array_values($this->buffer);

        try {
            $this->loggingRepo->create($data, $context);
        } catch (\Exception) {
            $this->writePerEntry($context);
        } finally {
            $this->reset();
        }
    }

    public function addLogEntry(MigrationLogEntry $logEntry): void
    {
        $key = $this->generateKey($logEntry);

        $this->buffer[$key] = [
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

    public function setContext(?MigrationContextInterface $migrationContext = null, ?Context $context = null): void
    {
        $this->migrationContext = $migrationContext;
        $this->context = $context;
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
        foreach ($this->buffer as $key => $log) {
            try {
                $this->loggingRepo->create([$log], $context);
            } catch (\Exception) {
                $this->logger->error('SwagMigrationAssistant: Could not write log entry: ', [$key => $log]);
            }
        }
    }

    private function generateKey(MigrationLogEntry $entry): string
    {
        return Hasher::hash(implode('.', [
            $entry->getCode(),
            $entry->getEntityName(),
            $entry->getFieldName(),
            $entry->getEntityId(),
        ]));
    }
}
