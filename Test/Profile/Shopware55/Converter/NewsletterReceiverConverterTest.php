<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Profile\Shopware55\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationNext\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Profile\SwagMigrationProfileEntity;
use SwagMigrationNext\Profile\Shopware55\Converter\ConverterHelperService;
use SwagMigrationNext\Profile\Shopware55\Converter\NewsletterReceiverConverter;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\NewsletterReceiverDataSet;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Shopware55LocalGateway;
use SwagMigrationNext\Profile\Shopware55\Premapping\SalesChannelReader;
use SwagMigrationNext\Profile\Shopware55\Premapping\SalutationReader;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use SwagMigrationNext\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationNext\Test\Mock\Migration\Mapping\DummyMappingService;

class NewsletterReceiverConverterTest extends TestCase
{
    /**
     * @var DummyMappingService
     */
    private $mappingService;

    /**
     * @var ConverterHelperService
     */
    private $converterHelperService;

    /**
     * @var DummyLoggingService
     */
    private $loggingService;

    /**
     * @var NewsletterReceiverConverter
     */
    private $newsletterReceiverConverter;

    /**
     * @var string
     */
    private $runId;

    /**
     * @var SwagMigrationConnectionEntity
     */
    private $connection;

    /**
     * @var string
     */
    private $connectionId;

    /**
     * @var MigrationContext
     */
    private $context;

    protected function setUp(): void
    {
        $this->mappingService = new DummyMappingService();
        $this->converterHelperService = new ConverterHelperService();
        $this->loggingService = new DummyLoggingService();
        $this->newsletterReceiverConverter = new NewsletterReceiverConverter($this->mappingService,
            $this->converterHelperService, $this->loggingService);

        $this->runId = Uuid::randomHex();
        $this->connection = new SwagMigrationConnectionEntity();
        $this->connectionId = Uuid::randomHex();
        $profile = new SwagMigrationProfileEntity();
        $profile->setName(Shopware55Profile::PROFILE_NAME);
        $profile->setGatewayName(Shopware55LocalGateway::GATEWAY_NAME);
        $this->connection->setId($this->connectionId);
        $this->connection->setProfile($profile);

        $this->context = new MigrationContext(
            $this->connection,
            $this->runId,
            new NewsletterReceiverDataSet(),
            0,
            250
        );

        $salesChannelId = 'default_salesChannel';
        $context = Context::createDefaultContext();
        $this->mappingService->createNewUuid($this->connectionId, SalutationReader::getMappingName(), 'mr',
            $context, [], Uuid::randomHex());
        $this->mappingService->createNewUuid($this->connectionId, SalutationReader::getMappingName(), 'ms',
            $context, [], Uuid::randomHex());
        $this->mappingService->createNewUuid($this->connectionId, SalesChannelReader::getMappingName(), $salesChannelId,
            $context, [], Uuid::randomHex());
    }

    public function testConvertWithoutDoubleOptinConfirmed(): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/invalid/newsletter_receiver_data.php';

        $context = Context::createDefaultContext();
        $convertResult = $this->newsletterReceiverConverter->convert(
            $customerData[0],
            $context,
            $this->context
        );

        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        static::assertCount(1, $logs);

        $description = sprintf('NewsletterReceiver-Entity could not converted cause of empty necessary field(s): %s.', 'double_optin_confirmed');
        static::assertSame($description, $logs[0]['logEntry']['description']);
    }

    public function testConvertWithNotExistingSalutation(): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/invalid/newsletter_receiver_data.php';

        $context = Context::createDefaultContext();
        $convertResult = $this->newsletterReceiverConverter->convert(
            $customerData[1],
            $context,
            $this->context
        );

        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        static::assertCount(1, $logs);

        $description = sprintf('NewsletterReceiver-Entity could not converted cause of unknown salutation');
        static::assertSame($description, $logs[0]['logEntry']['description']);
    }

    public function testConvert(): void
    {
        $data = require __DIR__ . '/../../../_fixtures/newsletter_receiver_data.php';

        $context = Context::createDefaultContext();
        $convertResult = $this->newsletterReceiverConverter->convert(
            $data[0],
            $context,
            $this->context
        );
        $converted = $convertResult->getConverted();
        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('email', $converted);
        static::assertArrayHasKey('salutationId', $converted);
        static::assertArrayHasKey('languageId', $converted);
    }
}
