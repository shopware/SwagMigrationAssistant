<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Mock\Profile\Shopware;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Converter\OrderDocumentConverter;

class OrderDocumentConverterMock extends OrderDocumentConverter
{
    public function __construct()
    {
    }

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return true;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>|null
     */
    public function getDocumentType(array $data): ?array
    {
        return parent::getDocumentType($data);
    }

    public function mapDocumentType(string $sourceDocumentType): string
    {
        return parent::mapDocumentType($sourceDocumentType);
    }

    public function setMappingService(MappingServiceInterface $mappingService): void
    {
        $this->mappingService = $mappingService;
    }

    public function setLoggingService(LoggingServiceInterface $loggingService): void
    {
        $this->loggingService = $loggingService;
    }

    public function setContext(Context $context): void
    {
        $this->context = $context;
    }

    public function setMigrationContext(MigrationContextInterface $migrationContext): void
    {
        $this->migrationContext = $migrationContext;
    }

    public function setRunId(string $runId): void
    {
        $this->runId = $runId;
    }

    public function setConnectionId(string $connectionId): void
    {
        $this->connectionId = $connectionId;
    }
}
