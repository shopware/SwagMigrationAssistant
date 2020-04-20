<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware\Premapping;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\DeliveryTime\DeliveryTimeEntity;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Premapping\PremappingStruct;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\Premapping\DeliveryTimeReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

class DeliveryTimeReaderTest extends TestCase
{
    use KernelTestBehaviour;

    /**
     * @var DeliveryTimeReader
     */
    private $deliveryTimeReader;

    /**
     * @var MigrationContextInterface
     */
    private $migrationContext;

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

        $mock = $this->createMock(EntityRepository::class);
        $timeOne = new DeliveryTimeEntity();
        $timeOne->setId(Uuid::randomHex());
        $timeOne->setName('1-2 days');

        $timeTwo = new DeliveryTimeEntity();
        $timeTwo->setId(Uuid::randomHex());
        $timeTwo->setName('2-5 days');

        $mock->method('search')->willReturn(new EntitySearchResult(2, new EntityCollection([$timeOne, $timeTwo]), null, new Criteria(), $this->context));

        /* @var EntityRepositoryInterface $mock */
        $this->deliveryTimeReader = new DeliveryTimeReader($mock);

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $connection
        );
    }

    public function testGetPremapping(): void
    {
        $result = $this->deliveryTimeReader->getPremapping($this->context, $this->migrationContext);

        static::assertInstanceOf(PremappingStruct::class, $result);
        static::assertCount(1, $result->getMapping());
        static::assertCount(2, $result->getChoices());

        $choices = $result->getChoices();
        static::assertSame('2-5 days', $choices[1]->getDescription());
    }
}
