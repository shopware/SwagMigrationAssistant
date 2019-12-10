<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware55\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\NewsletterRecipientDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\Premapping\SalutationReader;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55NewsletterRecipientConverter;
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
     * @var Shopware55NewsletterRecipientConverter
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
        $this->newsletterReceiverConverter = new Shopware55NewsletterRecipientConverter($this->mappingService, $this->loggingService);

        $this->runId = Uuid::randomHex();
        $this->connection = new SwagMigrationConnectionEntity();
        $this->connectionId = Uuid::randomHex();
        $this->connection->setId($this->connectionId);
        $this->connection->setProfileName(Shopware55Profile::PROFILE_NAME);
        $this->connection->setGatewayName(ShopwareLocalGateway::GATEWAY_NAME);

        $this->context = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runId,
            new NewsletterRecipientDataSet(),
            0,
            250
        );

        $context = Context::createDefaultContext();
        $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            SalutationReader::getMappingName(),
            'mr',
            $context,
            null,
            [],
            Uuid::randomHex()
        );
        $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            SalutationReader::getMappingName(),
            'ms',
            $context,
            null,
            [],
            Uuid::randomHex()
        );
        $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            SalesChannelDefinition::ENTITY_NAME,
            '1',
            $context,
            null,
            [],
            Uuid::randomHex()
        );
    }

    public function testConvertWithoutDoubleOptinConfirmed(): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/invalid/newsletter_recipient_data.php';

        $context = Context::createDefaultContext();
        $customerData = $customerData[1];
        $customerData['address']['double_optin_confirmed'] = null;
        $customerData['address']['salutation'] = 'mr';
        $customerData['double_optin_confirmed'] = null;

        $convertResult = $this->newsletterReceiverConverter->convert(
            $customerData,
            $context,
            $this->context
        );

        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        static::assertCount(1, $logs);

        static::assertSame($logs[0]['code'], 'SWAG_MIGRATION_EMPTY_NECESSARY_FIELD_NEWSLETTER_RECIPIENT');
        static::assertSame($logs[0]['parameters']['sourceId'], '1');
        static::assertSame($logs[0]['parameters']['emptyField'], 'status');
    }

    public function testConvertWithNotExistingSalutation(): void
    {
        $data = require __DIR__ . '/../../../_fixtures/invalid/newsletter_recipient_data.php';

        $context = Context::createDefaultContext();
        $convertResult = $this->newsletterReceiverConverter->convert(
            $data[1],
            $context,
            $this->context
        );

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($convertResult->getMappingUuid());
        static::assertArrayNotHasKey('salutationId', $convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        static::assertCount(1, $logs);

        static::assertSame($logs[0]['code'], 'SWAG_MIGRATION_SALUTATION_ENTITY_UNKNOWN');
        static::assertSame($logs[0]['parameters']['sourceId'], 'xx');
        static::assertSame($logs[0]['parameters']['requiredForSourceId'], '1');
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
