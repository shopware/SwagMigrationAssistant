<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration\Media;

use PHPUnit\Framework\TestCase;
use SwagMigrationNext\Exception\ProcessorNotFoundException;
use SwagMigrationNext\Migration\Logging\LogType;
use SwagMigrationNext\Migration\Media\MediaFileProcessorRegistry;
use SwagMigrationNext\Migration\Media\MediaFileProcessorRegistryInterface;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Test\Mock\DummyCollection;
use SwagMigrationNext\Test\Mock\Migration\Media\DummyHttpMediaDownloadService;
use Symfony\Component\HttpFoundation\Response;

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
                    new DummyHttpMediaDownloadService(),
                ]
            )
        );
    }

    public function testGetProcessorNotFound(): void
    {
        $context = new MigrationContext(
            '',
            'foo',
            '',
            'bar',
            '',
            0,
            0
        );

        try {
            $this->processorRegistry->getProcessor($context);
        } catch (\Exception $e) {
            /* @var ProcessorNotFoundException $e */
            self::assertInstanceOf(ProcessorNotFoundException::class, $e);
            self::assertSame(Response::HTTP_NOT_FOUND, $e->getStatusCode());
            self::assertSame(LogType::PROCESSOR_NOT_FOUND, $e->getCode());
        }
    }
}
