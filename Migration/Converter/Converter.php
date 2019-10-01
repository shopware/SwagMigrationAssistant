<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Converter;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

abstract class Converter implements ConverterInterface
{
    /**
     * @var MappingServiceInterface
     */
    protected $mappingService;

    /**
     * @var LoggingServiceInterface
     */
    protected $loggingService;

    /**
     * @var array|null
     */
    protected $mainMapping;

    /**
     * @var array
     */
    protected $mappingIds = [];

    public function __construct(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService
    ) {
        $this->mappingService = $mappingService;
        $this->loggingService = $loggingService;
    }

    public function writeMapping(Context $context): void
    {
        $this->mappingService->writeMapping($context);
    }

    /**
     * Generates a unique checksum for the data array to recognize changes
     * on repeated migrations.
     */
    protected function generateChecksum(array $data): string
    {
        return md5(serialize($data));
    }

    /**
     * Updates the main mapping with all related mapping ids and writes it to mapping service.
     */
    protected function updateMainMapping(MigrationContextInterface $migrationContext, Context $context): void
    {
        $this->mainMapping['additionalData']['relatedMappings'] = $this->mappingIds;
        $this->mappingIds = [];

        $this->mappingService->updateMapping(
            $migrationContext->getConnection()->getId(),
            $migrationContext->getDataSet()::getEntity(),
            $this->mainMapping['oldIdentifier'],
            $this->mainMapping,
            $context
        );
    }
}
