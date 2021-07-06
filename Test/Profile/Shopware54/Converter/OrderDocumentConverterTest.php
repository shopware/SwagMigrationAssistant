<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware54\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Converter\OrderDocumentConverter;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\OrderDocumentDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54OrderDocumentConverter;
use SwagMigrationAssistant\Profile\Shopware54\Shopware54Profile;
use SwagMigrationAssistant\Test\MigrationServicesTrait;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\DummyMappingService;
use SwagMigrationAssistant\Test\Mock\Migration\Media\DummyMediaFileService;

class OrderDocumentConverterTest extends TestCase
{
    use MigrationServicesTrait;

    /**
     * @var OrderDocumentConverter
     */
    private $orderDocumentConverter;

    /**
     * @var MigrationContextInterface
     */
    private $migrationContext;

    /**
     * @var SwagMigrationConnectionEntity
     */
    private $connection;

    /**
     * @var string
     */
    private $runId;

    /**
     * @var DummyLoggingService
     */
    private $loggingService;

    protected function setUp(): void
    {
        $mappingService = new DummyMappingService();
        $this->loggingService = new DummyLoggingService();
        $mediaFileService = new DummyMediaFileService();

        $this->orderDocumentConverter = new Shopware54OrderDocumentConverter($mappingService, $this->loggingService, $mediaFileService);
        $connectionId = Uuid::randomHex();
        $this->runId = Uuid::randomHex();
        $this->connection = new SwagMigrationConnectionEntity();
        $this->connection->setId($connectionId);
        $this->connection->setProfileName(Shopware54Profile::PROFILE_NAME);
        $this->connection->setGatewayName(ShopwareLocalGateway::GATEWAY_NAME);
        $this->connection->setName('shopware');
        $this->migrationContext = new MigrationContext(
            new Shopware54Profile(),
            $this->connection,
            $this->runId,
            new OrderDocumentDataSet(),
            0,
            250
        );
        $context = Context::createDefaultContext();
        $mappingService->getOrCreateMapping($connectionId, DefaultEntities::ORDER, '15', $context, null, [], Uuid::randomHex());
    }

    public function testSupports(): void
    {
        $supportsDefinition = $this->orderDocumentConverter->supports($this->migrationContext);

        static::assertTrue($supportsDefinition);
    }

    public function testConvertWithUnknownOrderId(): void
    {
        $orderDocumentData = require __DIR__ . '/../../../_fixtures/order_document_data.php';
        $context = Context::createDefaultContext();

        $convertResult = $this->orderDocumentConverter->convert(
            $orderDocumentData[1],
            $context,
            $this->migrationContext
        );
        static::assertEmpty($convertResult->getConverted());
        $logs = $this->loggingService->getLoggingArray();
        static::assertSame('SWAG_MIGRATION__SHOPWARE_ASSOCIATION_REQUIRED_MISSING_ORDER', $logs[0]['code']);
    }

    public function testConvertWithoutDocumentType(): void
    {
        $orderDocumentData = require __DIR__ . '/../../../_fixtures/order_document_data.php';
        $context = Context::createDefaultContext();
        unset($orderDocumentData[0]['documenttype']);

        $convertResult = $this->orderDocumentConverter->convert(
            $orderDocumentData[0],
            $context,
            $this->migrationContext
        );
        static::assertEmpty($convertResult->getConverted());
        $logs = $this->loggingService->getLoggingArray();
        static::assertSame('SWAG_MIGRATION_EMPTY_NECESSARY_FIELD_ORDER_DOCUMENT', $logs[0]['code']);
        static::assertSame('1', $logs[0]['parameters']['sourceId']);
        static::assertSame('documenttype', $logs[0]['parameters']['emptyField']);
    }

    public function testConvert(): void
    {
        $orderDocumentData = require __DIR__ . '/../../../_fixtures/order_document_data.php';
        $context = Context::createDefaultContext();

        $convertResult = $this->orderDocumentConverter->convert(
            $orderDocumentData[0],
            $context,
            $this->migrationContext
        );
        $converted = $convertResult->getConverted();

        static::assertEmpty($convertResult->getUnmapped());
        static::assertNotNull($converted);
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('orderId', $converted);
        static::assertArrayHasKey('deepLinkCode', $converted);
        static::assertArrayHasKey('config', $converted);
        static::assertArrayHasKey('documentType', $converted);
        static::assertSame('pdf', $converted['fileType']);
        static::assertTrue($converted['static']);
        static::assertSame('Rechnung', $converted['documentType']['name']);
        static::assertSame('invoice', $converted['documentType']['technicalName']);
        static::assertSame($orderDocumentData[0]['docID'], $converted['config']['documentNumber']);
        static::assertSame($orderDocumentData[0]['docID'], $converted['config']['custom']['invoiceNumber']);
    }

    public function testConvertWithUnknownType(): void
    {
        $orderDocumentData = require __DIR__ . '/../../../_fixtures/order_document_data.php';
        $context = Context::createDefaultContext();

        $convertResult = $this->orderDocumentConverter->convert(
            $orderDocumentData[2],
            $context,
            $this->migrationContext
        );
        static::assertEmpty($convertResult->getConverted());
        $logs = $this->loggingService->getLoggingArray();
        static::assertSame('SWAG_MIGRATION__DOCUMENT_TYPE_NOT_SUPPORTED', $logs[0]['code']);
    }
}
