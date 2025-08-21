<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware55\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Mapping\Lookup\LanguageLookup;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\NewsletterRecipientDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\Premapping\SalutationReader;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55NewsletterRecipientConverter;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\DummyMappingService;

#[Package('fundamentals@after-sales')]
class NewsletterRecipientConverterTest extends TestCase
{
    use KernelTestBehaviour;

    private DummyLoggingService $loggingService;

    private Shopware55NewsletterRecipientConverter $newsletterReceiverConverter;

    private MigrationContext $context;

    protected function setUp(): void
    {
        $mappingService = new DummyMappingService();
        $this->loggingService = new DummyLoggingService();
        $this->newsletterReceiverConverter = new Shopware55NewsletterRecipientConverter(
            $mappingService,
            $this->loggingService,
            $this->getContainer()->get(LanguageLookup::class)
        );

        $runId = Uuid::randomHex();
        $connection = new SwagMigrationConnectionEntity();
        $connectionId = Uuid::randomHex();
        $connection->setId($connectionId);
        $connection->setProfileName(Shopware55Profile::PROFILE_NAME);
        $connection->setGatewayName(ShopwareLocalGateway::GATEWAY_NAME);

        $this->context = new MigrationContext(
            $connection,
            new Shopware55Profile(),
            null,
            new NewsletterRecipientDataSet(),
            $runId,
            0,
            250
        );

        $context = Context::createDefaultContext();
        $mappingService->getOrCreateMapping(
            $connectionId,
            SalutationReader::getMappingName(),
            'mr',
            $context,
            null,
            [],
            Uuid::randomHex()
        );
        $mappingService->getOrCreateMapping(
            $connectionId,
            SalutationReader::getMappingName(),
            'ms',
            $context,
            null,
            [],
            Uuid::randomHex()
        );
        $mappingService->getOrCreateMapping(
            $connectionId,
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

        static::assertSame($logs[0]['code'], 'SWAG_MIGRATION_EMPTY_NECESSARY_FIELD');
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
        $converted = $convertResult->getConverted();
        static::assertNotNull($converted);
        static::assertArrayNotHasKey('salutationId', $converted);

        $logs = $this->loggingService->getLoggingArray();
        static::assertCount(1, $logs);

        static::assertSame($logs[0]['code'], 'SWAG_MIGRATION_ENTITY_UNKNOWN');
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
        static::assertNotNull($converted);
        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('email', $converted);
        static::assertArrayHasKey('salutationId', $converted);
        static::assertArrayHasKey('languageId', $converted);
    }
}
