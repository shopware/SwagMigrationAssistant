<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware6\Converter;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Converter\ConverterInterface;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\Lookup\LanguageLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\LocaleLookup;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Profile\Shopware6\Converter\LanguageConverter;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\LanguageDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Mapping\Shopware6MappingServiceInterface;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\Dummy6MappingService;

#[Package('services-settings')]
class LanguageConverterTest extends ShopwareConverterTest
{
    protected function createConverter(
        Shopware6MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        MediaFileServiceInterface $mediaFileService,
        ?array $mappingArray = [],
    ): ConverterInterface {
        $localeLookup = $this->createMock(LocaleLookup::class);
        $localeLookup->method('get')->willReturn(Dummy6MappingService::FALLBACK_LOCALE_UUID_FOR_EVERY_CODE);

        return new LanguageConverter(
            $mappingService,
            $loggingService,
            $this->getContainer()->get(LanguageLookup::class),
            $localeLookup
        );
    }

    protected function createDataSet(): DataSet
    {
        return new LanguageDataSet();
    }

    protected static function getFixtureBasePath(): string
    {
        return __DIR__ . '/../../../_fixtures/Shopware6/Language/';
    }
}
