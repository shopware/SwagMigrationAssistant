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
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\Lookup\MailTemplateTypeLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\SystemDefaultMailTemplateLookup;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Profile\Shopware6\Converter\MailTemplateConverter;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\MailTemplateDataSet;

#[Package('services-settings')]
class MailTemplateConverterTest extends ShopwareConverterTest
{
    protected function createConverter(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        MediaFileServiceInterface $mediaFileService,
        ?array $mappingArray = [],
    ): ConverterInterface {
        $mailTemplateTypeLookup = $this->createMock(MailTemplateTypeLookup::class);
        $systemDefaultMailTemplateLookup = $this->createMock(SystemDefaultMailTemplateLookup::class);
        $systemDefaultMailTemplateLookup->method('get')->willReturn('fee20daa2f2a45178c808f2f69b686d4');

        static::assertIsArray($mappingArray);

        foreach ($mappingArray as $mapping) {
            if ($mapping['entityName'] === DefaultEntities::MAIL_TEMPLATE_TYPE) {
                $mailTemplateTypeLookup->method('get')->willReturn($mapping['newIdentifier']);
            }
        }

        return new MailTemplateConverter(
            $mappingService,
            $loggingService,
            $mediaFileService,
            $mailTemplateTypeLookup,
            $systemDefaultMailTemplateLookup,
        );
    }

    protected function createDataSet(): DataSet
    {
        return new MailTemplateDataSet();
    }

    protected static function getFixtureBasePath(): string
    {
        return __DIR__ . '/../../../_fixtures/Shopware6/MailTemplate/';
    }
}
