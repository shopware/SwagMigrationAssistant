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
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6ProfileInterface;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\Dummy6MappingService;

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

    protected function setUp(): void
    {
        $this->loggingService = new DummyLoggingService();
        $this->mappingService = new Dummy6MappingService();
        $this->converter = $this->createConverter($this->mappingService, $this->loggingService);

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

    public function testConvert(): void
    {
        $basePath = \rtrim($this->getFixtureBasePath(), '/') . '/';
        $glob = \glob($basePath . '*');

        if ($glob === false) {
            return;
        }

        $testcaseDirectories = \array_filter($glob, 'is_dir');
        foreach ($testcaseDirectories as $testcase) {
            $fixtureName = \basename($testcase);
            $this->doSingleConvert($testcase . '/', $fixtureName);

            $this->loggingService->resetLogging();
            $this->mappingService->resetMappingService();
        }
    }

    abstract protected function createConverter(MappingServiceInterface $mappingService, LoggingServiceInterface $loggingService): ConverterInterface;

    abstract protected function createProfile(): Shopware6ProfileInterface;

    abstract protected function getProfileName(): string;

    abstract protected function createDataSet(): DataSet;

    abstract protected function getConverterTestClassName(): string;

    abstract protected function getFixtureBasePath(): string;

    private function getAssertMessage(string $notes = ''): string
    {
        $childClassName = static::class;

        $message = "Child class: ${childClassName}";
        if ($notes !== '') {
            $message .= PHP_EOL . $notes;
        }

        return $message;
    }

    private function loadMapping(array $mappingArray): void
    {
        $connection = $this->migrationContext->getConnection();

        if ($connection === null) {
            return;
        }

        $connectionId = $connection->getId();
        foreach ($mappingArray as $mapping) {
            $this->mappingService->createMapping(
                $connectionId,
                $mapping['entityName'],
                $mapping['oldIdentifier'],
                null,
                null,
                $mapping['newIdentifier']
            );
        }
    }

    private function doSingleConvert(string $fixtureFolderPath, string $fixtureName): void
    {
        $mappingArray = require $fixtureFolderPath . 'mapping.php';
        $input = require $fixtureFolderPath . 'input.php';
        $expectedOutput = require $fixtureFolderPath . 'output.php';
        $expectedLogArray = require $fixtureFolderPath . 'log.php';

        $this->loadMapping($mappingArray);

        $context = Context::createDefaultContext();

        $convertResult = $this->converter->convert($input, $context, $this->migrationContext);
        $output = $convertResult->getConverted();

        static::assertNotNull($convertResult->getMappingUuid(), $this->getAssertMessage($fixtureName . ': No mappingUuid in converted result struct.'));
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
    }
}
