<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Services;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriter;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use SwagMigrationAssistant\Migration\Converter\ConverterInterface;
use SwagMigrationAssistant\Migration\Converter\ConverterRegistryInterface;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Service\MigrationDataConverter;
use SwagMigrationAssistant\Migration\Validation\SwagMigrationValidationService;
use SwagMigrationAssistant\Test\Mock\DataSet\DataSetMock;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;

class MigrationDataConverterTest extends TestCase
{
    public function testConvertLogsNullConverted(): void
    {
        $context = Context::createDefaultContext();

        $dataSet = new DataSetMock();

        $migrationContext = $this->createMock(MigrationContextInterface::class);
        $migrationContext->method('getDataSet')->willReturn($dataSet);

        $converter = $this->createMock(ConverterInterface::class);
        // // To pass the test case, return null
        $converter->expects(static::once())->method('convert')->willReturn(null);

        $converterRegistry = $this->createMock(ConverterRegistryInterface::class);
        $converterRegistry->expects(static::once())->method('getConverter')->willReturn($converter);

        $dummyLogger = new DummyLoggingService();

        $migrationDataConverter = $this->createMigrationDataConverter(converterRegistry: $converterRegistry, loggingService: $dummyLogger);

        $migrationDataConverter->convert([['id' => '1']], $migrationContext, $context);

        $result = $dummyLogger->getLoggingArray();

        static::assertCount(1, $result);
        static::assertSame('SWAG_MIGRATION__ENTITY_NOT_CONVERTED', $result[0]['code']);
    }

    private function createMigrationDataConverter(
        ?EntityWriterInterface $entityWriter = null,
        ?ConverterRegistryInterface $converterRegistry = null,
        ?MediaFileServiceInterface $mediaFileService = null,
        ?LoggingServiceInterface $loggingService = null,
        ?EntityDefinition $dataDefinition = null,
        ?MappingServiceInterface $mappingService = null,
        ?SwagMigrationValidationService $validationService = null,
    ): MigrationDataConverter {
        if ($entityWriter === null) {
            $entityWriter = $this->createMock(EntityWriter::class);
        }

        if ($converterRegistry === null) {
            $converterRegistry = $this->createMock(ConverterRegistryInterface::class);
        }

        if ($mediaFileService === null) {
            $mediaFileService = $this->createMock(MediaFileServiceInterface::class);
        }

        if ($loggingService === null) {
            $loggingService = $this->createMock(LoggingServiceInterface::class);
        }

        if ($dataDefinition === null) {
            $dataDefinition = $this->createMock(EntityDefinition::class);
        }

        if ($mappingService === null) {
            $mappingService = $this->createMock(MappingServiceInterface::class);
        }

        if ($validationService === null) {
            $validationService = $this->createMock(SwagMigrationValidationService::class);
        }

        return new MigrationDataConverter(
            $entityWriter,
            $converterRegistry,
            $mediaFileService,
            $loggingService,
            $dataDefinition,
            $mappingService,
            $validationService
        );
    }
}
