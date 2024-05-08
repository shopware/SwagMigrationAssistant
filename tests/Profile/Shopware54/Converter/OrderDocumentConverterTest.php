<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware54\Converter;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\DocumentTypeNotSupported;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Converter\OrderDocumentConverter;
use SwagMigrationAssistant\Profile\Shopware\Converter\ShopwareConverter;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\OrderDocumentDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54OrderDocumentConverter;
use SwagMigrationAssistant\Profile\Shopware54\Shopware54Profile;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55OrderDocumentConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56OrderDocumentConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57OrderDocumentConverter;
use SwagMigrationAssistant\Test\MigrationServicesTrait;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\DummyMappingService;
use SwagMigrationAssistant\Test\Mock\Migration\Media\DummyMediaFileService;

#[Package('services-settings')]
class OrderDocumentConverterTest extends TestCase
{
    use KernelTestBehaviour;
    use MigrationServicesTrait;

    private OrderDocumentConverter $orderDocumentConverter;

    private MigrationContextInterface $migrationContext;

    private SwagMigrationConnectionEntity $connection;

    private string $runId;

    private DummyLoggingService $loggingService;

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

    public function testConvertShouldLogUnknownType(): void
    {
        $orderDocumentData = require __DIR__ . '/../../../_fixtures/order_document_data.php';

        $document = $orderDocumentData[0];
        $document['id'] = '1';
        $document['documenttype']['id'] = '999';
        $document['documenttype']['key'] = 'unknown_type';
        $document['documenttype']['name'] = 'Unknown type test foo bar';

        $context = Context::createDefaultContext();

        $mappingServiceMock = $this->createMock(MappingServiceInterface::class);
        $mappingServiceMock->method('getMapping')->willReturn(['entityUuid' => Uuid::randomHex(), 'id' => Uuid::randomHex()]);
        $mappingServiceMock->method('getOrCreateMapping')->willReturn(['entityUuid' => Uuid::randomHex(), 'id' => Uuid::randomHex(), 'oldIdentifier' => $document['ID']]);

        $orderDocumentConverterClasses = [
            Shopware54OrderDocumentConverter::class => 'migration_unknown_type_test_foo_bar',
            Shopware55OrderDocumentConverter::class => 'unknown_type',
            Shopware56OrderDocumentConverter::class => 'unknown_type',
            Shopware57OrderDocumentConverter::class => 'unknown_type',
        ];

        foreach ($orderDocumentConverterClasses as $orderDocumentConverterClass => $expected) {
            $loggerMock = $this->createMock(LoggingServiceInterface::class);
            $loggerMock->expects(static::exactly(1))->method('addLogEntry')->with(new DocumentTypeNotSupported($this->runId, '999', $expected));

            $orderDocumentConverter = $this->createDocumentConverter($orderDocumentConverterClass, $mappingServiceMock, $loggerMock);
            $convertResult = $orderDocumentConverter->convert(
                $document,
                $context,
                $this->migrationContext
            );

            $converted = $convertResult->getConverted();

            static::assertIsArray($converted);
            static::assertArrayHasKey('documentType', $converted);
            static::assertArrayHasKey('technicalName', $converted['documentType']);
            static::assertSame($expected, $converted['documentType']['technicalName']);
        }
    }

    /**
     * @param array<string, array<string, mixed>> $document
     */
    #[DataProvider('documentTypes')]
    public function testConvertShouldMapDocumentTypes(array $document, string $expectedType): void
    {
        $context = Context::createDefaultContext();

        $mappingServiceMock = $this->createMock(MappingServiceInterface::class);
        $mappingServiceMock->method('getMapping')->willReturn(['entityUuid' => Uuid::randomHex(), 'id' => Uuid::randomHex()]);
        $mappingServiceMock->method('getOrCreateMapping')->willReturn(['entityUuid' => Uuid::randomHex(), 'id' => Uuid::randomHex(), 'oldIdentifier' => $document['ID']]);

        $orderDocumentConverterClasses = [
            Shopware54OrderDocumentConverter::class,
            Shopware55OrderDocumentConverter::class,
            Shopware56OrderDocumentConverter::class,
            Shopware57OrderDocumentConverter::class,
        ];

        foreach ($orderDocumentConverterClasses as $orderDocumentConverterClass) {
            $orderDocumentConverter = $this->createDocumentConverter($orderDocumentConverterClass, $mappingServiceMock);
            $convertResult = $orderDocumentConverter->convert(
                $document,
                $context,
                $this->migrationContext
            );

            $converted = $convertResult->getConverted();

            static::assertIsArray($converted);
            static::assertArrayHasKey('documentType', $converted);
            static::assertArrayHasKey('technicalName', $converted['documentType']);
            static::assertSame($expectedType, $converted['documentType']['technicalName']);
        }
    }

    /**
     * @return array<string, array<string, array<string, mixed>|string>>
     */
    public static function documentTypes(): array
    {
        $orderDocumentData = require __DIR__ . '/../../../_fixtures/order_document_data.php';

        $documentArrayObject = new \ArrayObject($orderDocumentData[0]);

        $data = [
            ['input' => 'credit', 'expected' => 'credit_note', 'id' => '3'],
            ['input' => 'cancellation', 'expected' => 'storno', 'id' => '4'],
            ['input' => 'invoice', 'expected' => 'invoice', 'id' => '1'],
            ['input' => 'delivery_note', 'expected' => 'delivery_note', 'id' => '2'],
            ['input' => 'storno', 'expected' => 'storno', 'id' => '4'],
            ['input' => 'credit_note', 'expected' => 'credit_note', 'id' => '3'],
        ];

        $result = [];
        foreach ($data as $case) {
            $document = $documentArrayObject->getArrayCopy();
            $document['id'] = $document['ID'];
            $document['documenttype']['key'] = $case['input'];
            $document['documenttype']['id'] = $case['id'];

            $result['given input: ' . $case['input']] = [
                'document' => $document,
                'expectedType' => $case['expected'],
            ];
        }

        return $result;
    }

    private function createDocumentConverter(string $converterClass, MappingServiceInterface $mappingService, ?LoggingServiceInterface $loggingService = null): ShopwareConverter
    {
        if ($loggingService === null) {
            $loggingService = new DummyLoggingService();
        }

        $instance = new $converterClass($mappingService, $loggingService, new DummyMediaFileService());
        static::assertInstanceOf(ShopwareConverter::class, $instance);

        return $instance;
    }
}
