<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware\Premapping;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Premapping\PremappingStruct;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\Premapping\NewsletterRecipientStatusReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

class NewsletterRecipientStatusReaderTest extends TestCase
{
    use KernelTestBehaviour;

    /**
     * @var MigrationContextInterface
     */
    private $migrationContext;

    /**
     * @var NewsletterRecipientStatusReader
     */
    private $reader;

    /**
     * @var Context
     */
    private $context;

    public function setUp(): void
    {
        $connection = new SwagMigrationConnectionEntity();
        $connection->setId(Uuid::randomHex());
        $connection->setProfileName(Shopware55Profile::PROFILE_NAME);
        $connection->setGatewayName(ShopwareLocalGateway::GATEWAY_NAME);
        $connection->setCredentialFields([]);
        $connection->setPremapping([]);

        $this->context = Context::createDefaultContext();

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $connection
        );

        $this->reader = new NewsletterRecipientStatusReader();
    }

    public function testGetPremapping(): void
    {
        $result = $this->reader->getPremapping($this->context, $this->migrationContext);

        static::assertInstanceOf(PremappingStruct::class, $result);
        $mapping = $result->getMapping();
        static::assertCount(1, $mapping);
        static::assertSame('default_newsletter_recipient_status', $mapping[0]->getSourceId());

        $choices = $result->getChoices();
        static::assertCount(4, $choices);
        static::assertSame('optIn', $choices[2]->getUuid());
        static::assertSame('OptIn', $choices[2]->getDescription());
    }
}
