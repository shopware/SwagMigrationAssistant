<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware6\Converter;

use PHPUnit\Framework\Attributes\DataProvider;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Converter\ConverterInterface;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\Lookup\SalesChannelLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\SalesChannelTypeLookup;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Profile\Shopware6\Converter\SalesChannelConverter;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\SalesChannelDataSet;

#[Package('fundamentals@after-sales')]
class SalesChannelConverterTest extends ShopwareConverterTest
{
    #[DataProvider('dataProviderConvert')]
    public function testConvert(string $fixtureFolderPath): void
    {
        if (!\str_contains($fixtureFolderPath, '02-DefaultSalesChannel')) {
            parent::testConvert($fixtureFolderPath);

            return;
        }

        $input = require $fixtureFolderPath . '/input.php';
        $expectedOutput = require $fixtureFolderPath . '/output.php';

        $mappingArray = [];
        if (\is_file($fixtureFolderPath . '/mapping.php')) {
            $mappingArray = require $fixtureFolderPath . '/mapping.php';
        }

        $this->converter = $this->createConverter($this->mappingService, $this->loggingService, $this->mediaService, $mappingArray);

        $this->loadMapping($mappingArray);

        $context = Context::createDefaultContext();
        $convertResult = $this->converter->convert($input, $context, $this->migrationContext);
        static::assertInstanceOf(ConvertStruct::class, $convertResult);
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

    public function testConvertAppendsMigrationSuffixForExistingStorefront(): void
    {
        $input = require __DIR__ . '/../../../_fixtures/Shopware6/SalesChannel/01-HappyCase/input.php';
        $mappingArray = require __DIR__ . '/../../../_fixtures/Shopware6/SalesChannel/03-DefaultSalesChannelWithMapping/mapping.php';

        $input['name'] = 'Storefront';
        foreach ($input['translations'] as &$translation) {
            $translation['name'] = 'Storefront';
        }
        unset($translation);

        $salesChannelTypeLookup = $this->createMock(SalesChannelTypeLookup::class);
        $salesChannelTypeLookup->method('get')->willReturn($input['typeId']);

        $salesChannelLookup = $this->createMock(SalesChannelLookup::class);
        $salesChannelLookup->method('hasSalesChannelWithTypeAndName')
            ->with($input['typeId'], 'Storefront', static::anything())
            ->willReturn(true);

        $this->converter = new SalesChannelConverter(
            $this->mappingService,
            $this->loggingService,
            $salesChannelTypeLookup,
            $salesChannelLookup
        );

        $this->loadMapping($mappingArray);

        $context = Context::createDefaultContext();
        $convertResult = $this->converter->convert($input, $context, $this->migrationContext);

        static::assertInstanceOf(ConvertStruct::class, $convertResult);

        $output = $convertResult->getConverted();
        static::assertNotNull($output);
        static::assertSame('Storefront (Migration)', $output['name']);
        static::assertSame('Storefront (Migration)', $output['translations'][0]['name']);
    }

    protected function createConverter(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        MediaFileServiceInterface $mediaFileService,
        ?array $mappingArray = [],
    ): ConverterInterface {
        $salesChannelTypeLookup = $this->createMock(SalesChannelTypeLookup::class);
        $salesChannelTypeLookup->method('get')->willReturnCallback(
            static function (string $salesChannelTypeId, mixed $context): ?string {
                if ($salesChannelTypeId === 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa') {
                    return null;
                }

                return $salesChannelTypeId;
            }
        );

        $salesChannelLookup = $this->createMock(SalesChannelLookup::class);
        $salesChannelLookup->method('hasSalesChannelWithTypeAndName')->willReturn(false);

        return new SalesChannelConverter(
            $mappingService,
            $loggingService,
            $salesChannelTypeLookup,
            $salesChannelLookup
        );
    }

    protected function createDataSet(): DataSet
    {
        return new SalesChannelDataSet();
    }

    protected static function getFixtureBasePath(): string
    {
        return __DIR__ . '/../../../_fixtures/Shopware6/SalesChannel/';
    }
}
