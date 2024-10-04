<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware6\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\SeoUrlDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Converter\SeoUrlConverter;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\Dummy6MappingService;

class SeoUrlConverterConvertDataTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testConvertSkipConvertionIfSeoUrlIsUnmodified(): void
    {
        $data = [
            'isModified' => false,
        ];

        $seoUrlConverter = $this->createSeoUrlConverter();
        $result = $seoUrlConverter->convert($data, Context::createDefaultContext(), $this->createMigrationContext());
        static::assertNull($result->getConverted());
        static::assertSame($data, $result->getUnmapped());
    }

    private function createSeoUrlConverter(): SeoUrlConverter
    {
        return new SeoUrlConverter(
            new Dummy6MappingService(),
            new DummyLoggingService()
        );
    }

    private function createMigrationContext(): MigrationContext
    {
        $migrationConnectionEntity = new SwagMigrationConnectionEntity();
        $migrationConnectionEntity->setId(Uuid::randomHex());

        return new MigrationContext(
            new Shopware6MajorProfile('6.6.0.0'),
            $migrationConnectionEntity,
            Uuid::randomHex(),
            new SeoUrlDataSet(),
            0,
            10
        );
    }
}
