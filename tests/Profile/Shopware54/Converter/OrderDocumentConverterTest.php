<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware54\Converter;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Document\Aggregate\DocumentType\DocumentTypeEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
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
use SwagMigrationAssistant\Test\Mock\Profile\Shopware\OrderDocumentConverterMock;

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

    #[DataProvider('documentTypes')]
    public function testMapDocumentType(string $type, string $expected): void
    {
        $orderDocumentConverter = new OrderDocumentConverterMock();

        static::assertSame($expected, $orderDocumentConverter->mapDocumentType($type));
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function documentTypes(): array
    {
        return [
            'credit from sw5' => ['credit', 'credit_note'],
            'cancellation from sw5' => ['cancellation', 'storno'],
            'invoice from sw5' => ['invoice', 'invoice'],
            'delivery_note from sw5' => ['delivery_note', 'delivery_note'],
            'storno' => ['storno', 'storno'],
            'credit_note' => ['credit_note', 'credit_note'],
            'other key' => ['fooBar', 'fooBar'],
        ];
    }

    /**
     * @param array<string, string> $data
     * @param array<string, string> $mappingResult
     * @param array<string, string> $expected
     */
    #[DataProvider('documentData')]
    public function testGetDocumentType(
        array $data,
        InvokedCount $loggerInvokedCount,
        ?string $getDocumentTypeUuidResult,
        array $mappingResult,
        array $expected
    ): void {
        $loggerMock = $this->createMock(LoggingServiceInterface::class);
        $loggerMock->expects($loggerInvokedCount)->method('addLogEntry');

        $mappingServiceMock = $this->createMock(MappingServiceInterface::class);
        $mappingServiceMock->expects(static::once())->method('getDocumentTypeUuid')->willReturn($getDocumentTypeUuidResult);
        $mappingServiceMock->expects(empty($mappingResult) ? static::never() : static::once())->method('getOrCreateMapping')->willReturn($mappingResult);

        $orderDocumentConverter = new OrderDocumentConverterMock();
        $orderDocumentConverter->setMappingService($mappingServiceMock);
        $orderDocumentConverter->setLoggingService($loggerMock);
        $orderDocumentConverter->setContext(Context::createDefaultContext());
        $orderDocumentConverter->setMigrationContext(new MigrationContext(new Shopware54Profile()));
        $orderDocumentConverter->setRunId(Uuid::randomHex());
        $orderDocumentConverter->setConnectionId(Uuid::randomHex());

        $result = $orderDocumentConverter->getDocumentType($data);

        static::assertSame($expected, $result);
    }

    /**
     * @return array<string, array<int|string, array<string, mixed>|InvokedCount|string|null>>
     */
    public static function documentData(): array
    {
        $repository = self::getContainer()->get('document_type.repository');
        $result = $repository->search(new Criteria(), Context::createDefaultContext());

        $returnValue = [
            'undefined documentType any' => [
                'data' => ['id' => '1', 'key' => 'any', 'name' => 'any name'],
                'loggerInvokedCount' => static::once(),
                'getDocumentTypeUuidResult' => null,
                'mappingResult' => ['entityUuid' => '9999', 'id' => '9999'],
                'expected' => ['id' => '9999', 'name' => 'any name', 'technicalName' => 'any'],
            ],

            'undefined documentType fooBar' => [
                'data' => ['id' => '2', 'key' => 'fooBar', 'name' => 'fooBar name'],
                'loggerInvokedCount' => static::once(),
                'getDocumentTypeUuidResult' => null,
                'mappingResult' => ['entityUuid' => '8888', 'id' => '8888'],
                ['id' => '8888', 'name' => 'fooBar name', 'technicalName' => 'fooBar'],
            ],
        ];

        foreach ($result as $index => $documentType) {
            static::assertInstanceOf(DocumentTypeEntity::class, $documentType);
            $mappedType = self::mapTypeToShopware5Default($documentType->getTechnicalName());

            $returnValue['documentType ' . $mappedType] = [
                'data' => ['id' => $index, 'key' => $mappedType],
                'loggerInvokedCount' => static::never(),
                'getDocumentTypeUuidResult' => $documentType->getId(),
                'mappingResult' => [],
                'expected' => ['id' => $documentType->getId()],
            ];
        }

        return $returnValue;
    }

    private static function mapTypeToShopware5Default(string $documentType): string
    {
        return match ($documentType) {
            'storno' => 'cancellation',
            'credit_note' => 'credit',
            default => $documentType
        };
    }
}
