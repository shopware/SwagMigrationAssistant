<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Profile\Shopware55\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\Profile\SwagMigrationProfileEntity;
use SwagMigrationAssistant\Profile\Shopware55\Converter\NewsletterRecipientConverter;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\NewsletterRecipientDataSet;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Local\Shopware55LocalGateway;
use SwagMigrationAssistant\Profile\Shopware55\Premapping\SalesChannelReader;
use SwagMigrationAssistant\Profile\Shopware55\Premapping\SalutationReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\DummyMappingService;

class NewsletterRecipientConverterTest extends TestCase
{
    /**
     * @var DummyMappingService
     */
    private $mappingService;

    /**
     * @var DummyLoggingService
     */
    private $loggingService;

    /**
     * @var NewsletterRecipientConverter
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
        $this->loggingService = new DummyLoggingService();
        $this->newsletterReceiverConverter = new NewsletterRecipientConverter($this->mappingService, $this->loggingService);

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
            new NewsletterRecipientDataSet(),
            0,
            250
        );

        $context = Context::createDefaultContext();
        $this->mappingService->createNewUuid($this->connectionId, SalutationReader::getMappingName(), 'mr',
            $context, [], Uuid::randomHex());
        $this->mappingService->createNewUuid($this->connectionId, SalutationReader::getMappingName(), 'ms',
            $context, [], Uuid::randomHex());
        $this->mappingService->createNewUuid($this->connectionId, SalesChannelReader::getMappingName(), 'default_salesChannel',
            $context, [], Uuid::randomHex());
    }

    public function testConvertWithoutDoubleOptinConfirmed(): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/invalid/newsletter_recipient_data.php';

        $context = Context::createDefaultContext();
        $convertResult = $this->newsletterReceiverConverter->convert(
            $customerData[0],
            $context,
            $this->context
        );

        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        static::assertCount(1, $logs);

        $description = sprintf('NewsletterRecipient-Entity could not be converted cause of empty necessary field(s): %s.', 'double_optin_confirmed');
        static::assertSame($description, $logs[0]['logEntry']['description']);
    }

    public function testConvertWithNotExistingSalutation(): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/invalid/newsletter_recipient_data.php';

        $context = Context::createDefaultContext();
        $convertResult = $this->newsletterReceiverConverter->convert(
            $customerData[1],
            $context,
            $this->context
        );

        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        static::assertCount(1, $logs);

        $description = sprintf('NewsletterRecipient-Entity could not be converted cause of unknown salutation');
        static::assertSame($description, $logs[0]['logEntry']['description']);
    }

    public function testConvert(): void
    {
        $data = require __DIR__ . '/../../../_fixtures/newsletter_recipient_data.php';

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
