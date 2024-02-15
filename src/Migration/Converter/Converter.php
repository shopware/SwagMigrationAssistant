<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Converter;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('services-settings')]
abstract class Converter implements ConverterInterface
{
    protected MappingServiceInterface $mappingService;

    protected LoggingServiceInterface $loggingService;

    /**
     * @var ?array{id: string, connectionId: string, oldIdentifier: ?string, entityUuid: ?string, entityValue: ?string, checksum: ?string, additionalData: ?array<mixed>}
     */
    protected ?array $mainMapping = null;

    /**
     * @var array<string>
     */
    protected array $mappingIds = [];

    protected string $checksum = '';

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

    public function getMediaUuids(array $converted): ?array
    {
        return null;
    }

    /**
     * Generates a unique checksum for the data array to recognize changes
     * on repeated migrations.
     *
     * @param array<mixed> $data
     */
    protected function generateChecksum(array $data): void
    {
        $this->checksum = \md5(\serialize($data));
    }

    /**
     * Updates the main mapping with all related mapping ids and writes it to mapping service.
     */
    protected function updateMainMapping(MigrationContextInterface $migrationContext, Context $context): void
    {
        if (empty($this->mainMapping)) {
            return;
        }

        $this->mainMapping['checksum'] = $this->checksum;
        $this->mainMapping['additionalData']['relatedMappings'] = $this->mappingIds;
        $this->mappingIds = [];

        $dataSet = $migrationContext->getDataSet();
        $connection = $migrationContext->getConnection();
        if ($dataSet === null || $connection === null) {
            return;
        }

        $this->mappingService->updateMapping(
            $connection->getId(),
            $dataSet::getEntity(),
            (string) $this->mainMapping['oldIdentifier'],
            $this->mainMapping,
            $context
        );
    }

    protected function getDataSetEntity(MigrationContextInterface $migrationContext): ?string
    {
        $dataSet = $migrationContext->getDataSet();
        if ($dataSet === null) {
            return null;
        }

        return $dataSet::getEntity();
    }
}
