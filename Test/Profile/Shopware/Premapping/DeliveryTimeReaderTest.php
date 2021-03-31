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
use Shopware\Core\System\DeliveryTime\DeliveryTimeDefinition;
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

    /**
     * @var DeliveryTimeEntity
     */
    private $timeOne;

    public function setUp(): void
    {
        $this->context = Context::createDefaultContext();
        $connection = new SwagMigrationConnectionEntity();
        $connection->setId(Uuid::randomHex());
        $connection->setProfileName(Shopware55Profile::PROFILE_NAME);
        $connection->setGatewayName(ShopwareLocalGateway::GATEWAY_NAME);
        $connection->setCredentialFields([]);

        $mock = $this->createMock(EntityRepository::class);
        $this->timeOne = new DeliveryTimeEntity();
        $this->timeOne->setId(Uuid::randomHex());
        $this->timeOne->setName('1-2 days');

        $timeTwo = new DeliveryTimeEntity();
        $timeTwo->setId(Uuid::randomHex());
        $timeTwo->setName('2-5 days');

        $premapping = [[
            'entity' => 'delivery_time',
            'mapping' => [
                0 => [
                    'sourceId' => 'default_delivery_time',
                    'description' => 'Default delivery time',
                    'destinationUuid' => $this->timeOne->getId(),
                ],
            ],
        ]];
        $connection->setPremapping($premapping);

        $mock->method('search')->willReturn(new EntitySearchResult(DeliveryTimeDefinition::ENTITY_NAME, 2, new EntityCollection([$this->timeOne, $timeTwo]), null, new Criteria(), $this->context));

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
        static::assertSame($this->timeOne->getId(), $result->getMapping()[0]->getDestinationUuid());
    }

    public function testGetPremappingInvalidUuid(): void
    {
        $connection = new SwagMigrationConnectionEntity();
        $connection->setId(Uuid::randomHex());
        $connection->setProfileName(Shopware55Profile::PROFILE_NAME);
        $connection->setGatewayName(ShopwareLocalGateway::GATEWAY_NAME);
        $connection->setCredentialFields([]);

        $premapping = [[
            'entity' => 'delivery_time',
            'mapping' => [
                0 => [
                    'sourceId' => 'default_delivery_time',
                    'description' => 'Default delivery time',
                    'destinationUuid' => Uuid::randomHex(),
                ],
            ],
        ]];
        $connection->setPremapping($premapping);

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $connection
        );

        $result = $this->deliveryTimeReader->getPremapping($this->context, $this->migrationContext);

        static::assertInstanceOf(PremappingStruct::class, $result);
        static::assertCount(1, $result->getMapping());
        static::assertCount(2, $result->getChoices());

        $choices = $result->getChoices();
        static::assertSame('2-5 days', $choices[1]->getDescription());
        static::assertEmpty($result->getMapping()[0]->getDestinationUuid());
    }
}
