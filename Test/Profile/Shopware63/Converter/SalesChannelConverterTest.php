<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware63\Converter;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\Converter\ConverterInterface;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\SalesChannelDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Mapping\Shopware6MappingServiceInterface;
use SwagMigrationAssistant\Profile\Shopware63\Converter\Shopware63SalesChannelConverter;

class SalesChannelConverterTest extends ShopwareConverterTest
{
    /**
     * @dataProvider dataProviderConvert
     */
    public function testConvert(string $fixtureFolderPath): void
    {
        if (!\str_contains($fixtureFolderPath, '02-DefaultSalesChannel')) {
            parent::testConvert($fixtureFolderPath);

            return;
        }

        $input = require $fixtureFolderPath . '/input.php';
        $expectedOutput = require $fixtureFolderPath . '/output.php';

        $mappingArray = [];
        if (\file_exists($fixtureFolderPath . '/mapping.php')) {
            $mappingArray = require $fixtureFolderPath . '/mapping.php';
        }

        $this->loadMapping($mappingArray);

        $context = Context::createDefaultContext();
        $convertResult = $this->converter->convert($input, $context, $this->migrationContext);
        $output = $convertResult->getConverted();

        static::assertNotNull($output);
        static::assertNotSame($output['id'], $input['id']);

        foreach ($output['translations'] as &$translation) {
            static::assertNotSame($translation['salesChannelId'], $input['id']);
            unset($translation['salesChannelId']);
        }

        unset($output['id']);
        static::assertSame($expectedOutput, $output);
    }

    protected function createConverter(Shopware6MappingServiceInterface $mappingService, LoggingServiceInterface $loggingService, MediaFileServiceInterface $mediaFileService): ConverterInterface
    {
        return new Shopware63SalesChannelConverter($mappingService, $loggingService);
    }

    protected function createDataSet(): DataSet
    {
        return new SalesChannelDataSet();
    }

    protected function getFixtureBasePath(): string
    {
        return __DIR__ . '/../../../_fixtures/Shopware6/SalesChannel/';
    }
}
