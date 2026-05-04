<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware6\Converter;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Converter\ConverterInterface;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\ConvertAssociationMissingLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Profile\Shopware6\Converter\ProductReviewConverter;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\ProductReviewDataSet;

#[Package('fundamentals@after-sales')]
class ProductReviewConverterTest extends ShopwareConverterTest
{
    public function testConvertUsesMappedCustomSalesChannelId(): void
    {
        $input = require __DIR__ . '/../../../_fixtures/Shopware6/ProductReview/01-HappyCase/input.php';
        $mappingArray = require __DIR__ . '/../../../_fixtures/Shopware6/ProductReview/01-HappyCase/mapping.php';

        foreach ($mappingArray as &$mapping) {
            if (
                $mapping['entityName'] === DefaultEntities::SALES_CHANNEL
                && $mapping['oldIdentifier'] === $input['salesChannelId']
            ) {
                $mapping['newIdentifier'] = '11111111111111111111111111111111';
            }
        }
        unset($mapping);

        $this->loadMapping($mappingArray);

        $context = Context::createDefaultContext();
        $convertResult = $this->converter->convert($input, $context, $this->migrationContext);

        static::assertInstanceOf(ConvertStruct::class, $convertResult);

        $output = $convertResult->getConverted();
        static::assertNotNull($output);
        static::assertSame('11111111111111111111111111111111', $output['salesChannelId']);
        static::assertSame([], $this->loggingService->getLoggingArray());
    }

    public function testConvertSkipsWhenSalesChannelMappingIsMissing(): void
    {
        $input = require __DIR__ . '/../../../_fixtures/Shopware6/ProductReview/01-HappyCase/input.php';
        $mappingArray = require __DIR__ . '/../../../_fixtures/Shopware6/ProductReview/01-HappyCase/mapping.php';

        $mappingArray = \array_values(\array_filter(
            $mappingArray,
            static fn (array $mapping): bool => $mapping['entityName'] !== DefaultEntities::SALES_CHANNEL
        ));

        $this->loadMapping($mappingArray);

        $context = Context::createDefaultContext();
        $convertResult = $this->converter->convert($input, $context, $this->migrationContext);

        static::assertInstanceOf(ConvertStruct::class, $convertResult);
        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        static::assertCount(1, $logs);
        static::assertSame(ConvertAssociationMissingLog::getCode(), $logs[0]['code']);
        static::assertSame('salesChannelId', $logs[0]['fieldName']);
    }

    protected function createConverter(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        MediaFileServiceInterface $mediaFileService,
        ?array $mappingArray = [],
    ): ConverterInterface {
        return new ProductReviewConverter($mappingService, $loggingService);
    }

    protected function createDataSet(): DataSet
    {
        return new ProductReviewDataSet();
    }

    protected static function getFixtureBasePath(): string
    {
        return __DIR__ . '/../../../_fixtures/Shopware6/ProductReview/';
    }
}
