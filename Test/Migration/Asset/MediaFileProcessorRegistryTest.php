<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration\Asset;

use PHPUnit\Framework\TestCase;
use SwagMigrationNext\Exception\ProcessorNotFoundException;
use SwagMigrationNext\Migration\Asset\MediaFileProcessorRegistry;
use SwagMigrationNext\Migration\Asset\MediaFileProcessorRegistryInterface;
use SwagMigrationNext\Migration\Logging\LogType;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Test\Mock\DummyCollection;
use SwagMigrationNext\Test\Mock\Migration\Asset\DummyHttpAssetDownloadService;

class MediaFileProcessorRegistryTest extends TestCase
{
    /**
     * @var MediaFileProcessorRegistryInterface
     */
    private $processorRegistry;

    protected function setUp()
    {
        $this->processorRegistry = new MediaFileProcessorRegistry(
            new DummyCollection(
                [
                    new DummyHttpAssetDownloadService(),
                ]
            )
        );
    }

    public function testGetProcessorNotFount(): void
    {
        $context = new MigrationContext(
            '',
            'foo',
            '',
            'bar',
            '',
            [],
            0,
            0
        );
        $this->expectException(ProcessorNotFoundException::class);
        $this->expectExceptionCode(LogType::PROCESSOR_NOT_FOUND);

        $this->processorRegistry->getProcessor($context);
    }
}
