<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware\Premapping;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\CashPayment;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\DebitPayment;
use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistry;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Premapping\PremappingStruct;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\Premapping\PaymentMethodReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

class PaymentMethodReaderTest extends TestCase
{
    use KernelTestBehaviour;

    /**
     * @var MigrationContextInterface
     */
    private $migrationContext;

    /**
     * @var PaymentMethodReader
     */
    private $reader;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var PaymentMethodEntity
     */
    private $debitMock;

    /**
     * @var PaymentMethodEntity
     */
    private $cashMock;

    public function setUp(): void
    {
        $this->context = Context::createDefaultContext();

        $connection = new SwagMigrationConnectionEntity();
        $connection->setId(Uuid::randomHex());
        $connection->setProfileName(Shopware55Profile::PROFILE_NAME);
        $connection->setGatewayName(ShopwareLocalGateway::GATEWAY_NAME);
        $connection->setCredentialFields([]);

        $this->debitMock = new PaymentMethodEntity();
        $this->debitMock->setId(Uuid::randomHex());
        $this->debitMock->setName('Debit');
        $this->debitMock->setHandlerIdentifier(DebitPayment::class);

        $this->cashMock = new PaymentMethodEntity();
        $this->cashMock->setId(Uuid::randomHex());
        $this->cashMock->setName('Cash');
        $this->cashMock->setHandlerIdentifier(CashPayment::class);

        $premapping = [[
            'entity' => 'payment_method',
            'mapping' => [
                0 => [
                    'sourceId' => 'debit',
                    'description' => 'debit',
                    'destinationUuid' => $this->debitMock->getId(),
                ],
                1 => [
                    'sourceId' => 'cash',
                    'description' => 'cash',
                    'destinationUuid' => $this->cashMock->getId(),
                ],

                2 => [
                    'sourceId' => 'payment-invalid',
                    'description' => 'payment-invalid',
                    'destinationUuid' => Uuid::randomHex(),
                ],
            ],
        ]];
        $connection->setPremapping($premapping);

        $mock = $this->createMock(EntityRepository::class);
        $mock->method('search')->willReturn(new EntitySearchResult(PaymentMethodDefinition::ENTITY_NAME, 2, new EntityCollection([$this->debitMock, $this->cashMock]), null, new Criteria(), $this->context));

        $gatewayMock = $this->createMock(ShopwareLocalGateway::class);
        $gatewayMock->method('readTable')->willReturn([
            ['id' => '1', 'name' => 'debit', 'description' => 'Direct debit'],
            ['id' => '2', 'name' => 'cash', 'description' => 'Cash'],
            ['id' => '3', 'name' => 'no_description', 'description' => ''],
            ['id' => '4', 'name' => 'payment-invalid', 'description' => 'payment-invalid'],
        ]);

        $gatewayRegistryMock = $this->createMock(GatewayRegistry::class);
        $gatewayRegistryMock->method('getGateway')->willReturn($gatewayMock);

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $connection
        );

        $this->reader = new PaymentMethodReader($mock, $gatewayRegistryMock);
    }

    public function testGetPremapping(): void
    {
        $result = $this->reader->getPremapping($this->context, $this->migrationContext);

        static::assertInstanceOf(PremappingStruct::class, $result);
        static::assertCount(5, $result->getMapping());
        static::assertCount(2, $result->getChoices());

        $choices = $result->getChoices();
        static::assertSame('Debit', $choices[0]->getDescription());
        static::assertSame('Cash', $choices[1]->getDescription());
        static::assertSame($this->cashMock->getId(), $result->getMapping()[0]->getDestinationUuid());
        static::assertSame($this->debitMock->getId(), $result->getMapping()[1]->getDestinationUuid());
        static::assertEmpty($result->getMapping()[2]->getDestinationUuid());
        static::assertEmpty($result->getMapping()[3]->getDestinationUuid());
    }

    public function testPremappingChoicesWithEmptyDescription(): void
    {
        $result = $this->reader->getPremapping($this->context, $this->migrationContext);

        static::assertInstanceOf(PremappingStruct::class, $result);
        $mapping = $result->getMapping();
        static::assertSame('no_description', $mapping[3]->getDescription());
    }
}
