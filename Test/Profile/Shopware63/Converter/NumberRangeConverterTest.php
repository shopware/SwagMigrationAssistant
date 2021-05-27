<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware63\Converter;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use SwagMigrationAssistant\Migration\Converter\ConverterInterface;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\NumberRangeDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Mapping\Shopware6MappingServiceInterface;
use SwagMigrationAssistant\Profile\Shopware63\Converter\Shopware63NumberRangeConverter;

class NumberRangeConverterTest extends ShopwareConverterTest
{
    use KernelTestBehaviour;

    protected function createConverter(Shopware6MappingServiceInterface $mappingService, LoggingServiceInterface $loggingService, MediaFileServiceInterface $mediaFileService): ConverterInterface
    {
        /** @var EntityRepositoryInterface $numberRangeRepo */
        $numberRangeRepo = $this->getContainer()->get('number_range_state.repository');

        return new Shopware63NumberRangeConverter($mappingService, $loggingService, $numberRangeRepo);
    }

    protected function createDataSet(): DataSet
    {
        return new NumberRangeDataSet();
    }

    protected function getFixtureBasePath(): string
    {
        return __DIR__ . '/../../../_fixtures/Shopware6/NumberRange/';
    }
}
