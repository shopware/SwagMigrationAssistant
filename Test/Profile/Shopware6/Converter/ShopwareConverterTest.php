<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware6\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Converter\ConverterInterface;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware6\Mapping\Shopware6MappingServiceInterface;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6ProfileInterface;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\Dummy6MappingService;
use SwagMigrationAssistant\Test\Mock\Migration\Media\DummyMediaFileService;

abstract class ShopwareConverterTest extends TestCase
{
    /**
     * @var DummyLoggingService
     */
    protected $loggingService;

    /**
     * @var Dummy6MappingService
     */
    protected $mappingService;

    /**
     * @var ConverterInterface
     */
    protected $converter;

    /**
     * @var MigrationContext
     */
    protected $migrationContext;

    /**
     * @var DummyMediaFileService
     */
    private $mediaService;

    protected function setUp(): void
    {
        $this->loggingService = new DummyLoggingService();
        $this->mappingService = new Dummy6MappingService();
        $this->mediaService = new DummyMediaFileService();
        $this->converter = $this->createConverter($this->mappingService, $this->loggingService, $this->mediaService);

        $runId = Uuid::randomHex();
        $connection = new SwagMigrationConnectionEntity();
        $connection->setId(Uuid::randomHex());
        $connection->setProfileName($this->getProfileName());
        $this->migrationContext = new MigrationContext(
            $this->createProfile(),
            $connection,
            $runId,
            $this->createDataSet(),
            0,
            250
        );
    }

    public function testSupports(): void
    {
        $supportsDefinition = $this->converter->supports($this->migrationContext);

        static::assertTrue($supportsDefinition, $this->getAssertMessage('Converter does not support migration context.'));
    }

    public function dataProviderConvert(): iterable
    {
        $basePath = \rtrim($this->getFixtureBasePath(), '/') . '/';
        $glob = \glob($basePath . '*');

        if ($glob === false) {
            return [];
        }

        foreach (\array_filter($glob, 'is_dir') as $dir) {
            yield \basename($dir) => [$dir];
        }
    }

    /**
     * @dataProvider dataProviderConvert
     */
    public function testConvert(string $fixtureFolderPath): void
    {
        $input = require $fixtureFolderPath . '/input.php';
        $expectedOutput = require $fixtureFolderPath . '/output.php';

        $mappingArray = [];
        if (\file_exists($fixtureFolderPath . '/mapping.php')) {
            $mappingArray = require $fixtureFolderPath . '/mapping.php';
        }

        $expectedLogArray = [];
        if (\file_exists($fixtureFolderPath . '/log.php')) {
            $expectedLogArray = require $fixtureFolderPath . '/log.php';
        }

        $mediaFileArray = [];
        if (\file_exists($fixtureFolderPath . '/media.php')) {
            $mediaFileArray = require $fixtureFolderPath . '/media.php';
        }

        $this->loadMapping($mappingArray);

        $context = Context::createDefaultContext();

        $convertResult = $this->converter->convert($input, $context, $this->migrationContext);
        $output = $convertResult->getConverted();

        $fixtureName = \basename($fixtureFolderPath);
        if ($output !== null) {
            static::assertNotNull($convertResult->getMappingUuid(), $this->getAssertMessage($fixtureName . ': No mappingUuid in converted result struct.'));
        }
        static::assertSame($expectedOutput, $output, $this->getAssertMessage($fixtureName . ': Output of converter does not match.'));

        $logs = $this->loggingService->getLoggingArray();
        static::assertCount(\count($expectedLogArray), $logs, $this->getAssertMessage($fixtureName . ': Log count not as expected.'));

        foreach ($expectedLogArray as $index => $expectedLog) {
            static::assertArrayHasKey($index, $logs, $this->getAssertMessage($fixtureName . ': Log not found (make sure the log array order matches the logging order).'));
            $realLog = $logs[$index];

            foreach (\array_keys($expectedLog) as $key) {
                static::assertSame($expectedLog[$key], $realLog[$key], $this->getAssertMessage($fixtureName . ': Log key not as expected (make sure the log array order matches the logging order).'));
            }
        }

        $mediaFiles = $this->mediaService->getMediaFileArray();
        static::assertCount(\count($mediaFileArray), $mediaFiles, $this->getAssertMessage($fixtureName . ': Media file count not as expected.'));

        foreach ($mediaFileArray as $index => $expectedFile) {
            static::assertArrayHasKey($index, $mediaFiles, $this->getAssertMessage($fixtureName . ': Media file not found (make sure the media file array order matches the convert order).'));
            $realMediaFile = $mediaFiles[$index];
            $expectedFile = \array_merge(['runId' => $this->migrationContext->getRunUuid()], $expectedFile);

            static::assertSame($expectedFile, $realMediaFile, $this->getAssertMessage($fixtureName . ': Media file key not as expected (make sure the media file array order matches the convert order).'));
        }
    }

    abstract protected function createConverter(Shopware6MappingServiceInterface $mappingService, LoggingServiceInterface $loggingService, MediaFileServiceInterface $mediaFileService): ConverterInterface;

    abstract protected function createProfile(): Shopware6ProfileInterface;

    abstract protected function getProfileName(): string;

    abstract protected function createDataSet(): DataSet;

    abstract protected function getFixtureBasePath(): string;

    protected function loadMapping(array $mappingArray): void
    {
        $connection = $this->migrationContext->getConnection();

        if ($connection === null) {
            return;
        }

        $connectionId = $connection->getId();
        foreach ($mappingArray as $mapping) {
            $mappingConnection = null;
            if (isset($mapping['connectionId'])) {
                $mappingConnection = $mapping['connectionId'];
            }

            $this->mappingService->createMapping(
                $mappingConnection ?? $connectionId,
                $mapping['entityName'],
                $mapping['oldIdentifier'],
                null,
                null,
                $mapping['newIdentifier']
            );
        }
    }

    private function getAssertMessage(string $notes = ''): string
    {
        $childClassName = static::class;

        $message = "Child class: ${childClassName}";
        if ($notes !== '') {
            $message .= \PHP_EOL . $notes;
        }

        return $message;
    }
}
