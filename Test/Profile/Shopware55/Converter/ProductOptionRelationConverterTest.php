<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Test\Profile\Shopware55\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ProductOptionRelationDataSet;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55ProductOptionRelationConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55ProductPropertyRelationConverter;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\DummyMappingService;

class ProductOptionRelationConverterTest extends TestCase
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var DummyMappingService
     */
    private $mappingService;

    /**
     * @var DummyLoggingService
     */
    private $loggingService;

    /**
     * @var Shopware55ProductPropertyRelationConverter
     */
    private $converter;

    /**
     * @var MigrationContext
     */
    private $migrationContext;

    /**
     * @var string
     */
    private $productUuid;

    /**
     * @var string[]
     */
    private $propertyUuids;

    /**
     * @var string
     */
    private $oldMappingId;

    protected function setUp(): void
    {
        $this->context = Context::createDefaultContext();
        $this->mappingService = new DummyMappingService();
        $this->loggingService = new DummyLoggingService();
        $this->converter = new Shopware55ProductOptionRelationConverter($this->mappingService, $this->loggingService);

        $connectionId = Uuid::randomHex();
        $runId = Uuid::randomHex();
        $connection = new SwagMigrationConnectionEntity();
        $connection->setId($connectionId);
        $connection->setName('ConnectionName');
        $connection->setProfileName(Shopware55Profile::PROFILE_NAME);

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $connection,
            $runId,
            new ProductOptionRelationDataSet(),
            0,
            250
        );

        $productMapping = $this->mappingService->getOrCreateMapping(
            $connectionId,
            DefaultEntities::PRODUCT_CONTAINER,
            '2',
            $this->context
        );
        $this->productUuid = $productMapping['entityUuid'];

        $relationData = require __DIR__ . '/../../../_fixtures/product_option_relation.php';

        foreach ($relationData as $key => $data) {
            $mapping = $this->mappingService->getOrCreateMapping(
                $connectionId,
                DefaultEntities::PROPERTY_GROUP_OPTION,
                \hash('md5', \mb_strtolower($data['name'] . '_' . $data['group']['name'])),
                $this->context
            );
            $this->propertyUuids[$key] = $mapping['entityUuid'];
        }

        $this->oldMappingId = Uuid::randomHex();
        $this->mappingService->getOrCreateMapping(
            $connectionId,
            DefaultEntities::PRODUCT_OPTION_RELATION,
            $relationData[0]['identifier'],
            $this->context,
            null,
            null,
            $this->oldMappingId
        );

        $mapping = $this->mappingService->getOrCreateMapping(
            $connectionId,
            DefaultEntities::PRODUCT_PROPERTY,
            $relationData[0]['id'] . '_' . $this->productUuid,
            $this->context
        );
        $this->oldMappingId = $mapping['entityUuid'];
    }

    public function testConvert(): void
    {
        $data = require __DIR__ . '/../../../_fixtures/product_option_relation.php';

        $context = Context::createDefaultContext();
        $convertResult = $this->converter->convert($data[0], $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($convertResult->getMappingUuid());
        static::assertSame($this->productUuid, $converted['id']);
        static::assertSame($this->propertyUuids[0], $converted['configuratorSettings'][0]['optionId']);

        $convertResult = $this->converter->convert($data[1], $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($convertResult->getMappingUuid());
        static::assertSame($this->productUuid, $converted['id']);
        static::assertSame($this->propertyUuids[1], $converted['configuratorSettings'][0]['optionId']);

        $convertResult = $this->converter->convert($data[2], $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($convertResult->getMappingUuid());
        static::assertSame($this->productUuid, $converted['id']);
        static::assertSame($this->propertyUuids[2], $converted['configuratorSettings'][0]['optionId']);
    }

    public function testConvertWithOldRelationMapping(): void
    {
        $data = require __DIR__ . '/../../../_fixtures/product_option_relation.php';

        $context = Context::createDefaultContext();
        $convertResult = $this->converter->convert($data[0], $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($convertResult->getMappingUuid());
        static::assertSame($this->productUuid, $converted['id']);
        static::assertSame($this->propertyUuids[0], $converted['configuratorSettings'][0]['optionId']);
        static::assertSame($this->oldMappingId, $converted['configuratorSettings'][0]['id']);
    }

    public function testConvertWithoutProductMapping(): void
    {
        $data = require __DIR__ . '/../../../_fixtures/product_option_relation.php';
        $data = $data[0];
        $data['productId'] = '18';

        $context = Context::createDefaultContext();
        $convertResult = $this->converter->convert($data, $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $logs = $this->loggingService->getLoggingArray();

        static::assertNull($convertResult->getConverted());
        static::assertNotNull($convertResult->getUnmapped());
        static::assertEmpty($logs);
    }

    public function testConvertWithoutPropertyMapping(): void
    {
        $relations = require __DIR__ . '/../../../_fixtures/product_option_relation.php';
        $data = $relations[0];
        $data['name'] = 'Invalid property value';

        $context = Context::createDefaultContext();
        $convertResult = $this->converter->convert($data, $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $logs = $this->loggingService->getLoggingArray();

        static::assertNull($convertResult->getConverted());
        static::assertNotNull($convertResult->getUnmapped());
        static::assertEmpty($logs);

        $data = $relations[0];
        $data['group']['name'] = 'Invalid group name';

        $context = Context::createDefaultContext();
        $convertResult = $this->converter->convert($data, $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $logs = $this->loggingService->getLoggingArray();

        static::assertNull($convertResult->getConverted());
        static::assertNotNull($convertResult->getUnmapped());
        static::assertEmpty($logs);
    }
}
