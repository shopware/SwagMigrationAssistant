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
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Salutation\SalutationDefinition;
use Shopware\Core\System\Salutation\SalutationEntity;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistry;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Premapping\PremappingStruct;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\Premapping\OrderStateReader;
use SwagMigrationAssistant\Profile\Shopware\Premapping\SalutationReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

class SalutationReaderTest extends TestCase
{
    use KernelTestBehaviour;

    /**
     * @var MigrationContextInterface
     */
    private $migrationContext;

    /**
     * @var OrderStateReader
     */
    private $reader;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var SalutationEntity
     */
    private $salutationTwo;

    /**
     * @var SalutationEntity
     */
    private $salutationOne;

    public function setUp(): void
    {
        $this->context = Context::createDefaultContext();

        $connection = new SwagMigrationConnectionEntity();
        $connection->setId(Uuid::randomHex());
        $connection->setProfileName(Shopware55Profile::PROFILE_NAME);
        $connection->setGatewayName(ShopwareLocalGateway::GATEWAY_NAME);
        $connection->setCredentialFields([]);

        $this->salutationOne = new SalutationEntity();
        $this->salutationOne->setId(Uuid::randomHex());
        $this->salutationOne->setDisplayName('Mr');
        $this->salutationOne->setSalutationKey('mr');

        $this->salutationTwo = new SalutationEntity();
        $this->salutationTwo->setId(Uuid::randomHex());
        $this->salutationTwo->setDisplayName('Ms');
        $this->salutationTwo->setSalutationKey('ms');

        $premapping = [[
            'entity' => 'salutation',
            'mapping' => [
                0 => [
                    'sourceId' => 'mr',
                    'description' => 'mr',
                    'destinationUuid' => $this->salutationOne->getId(),
                ],
                1 => [
                    'sourceId' => 'ms',
                    'description' => 'ms',
                    'destinationUuid' => $this->salutationTwo->getId(),
                ],

                2 => [
                    'sourceId' => 'salutation-invalid',
                    'description' => 'salutation-invalid',
                    'destinationUuid' => Uuid::randomHex(),
                ],
            ],
        ]];
        $connection->setPremapping($premapping);

        $mock = $this->createMock(EntityRepository::class);
        $mock->method('search')->willReturn(new EntitySearchResult(SalutationDefinition::ENTITY_NAME, 2, new EntityCollection([$this->salutationOne, $this->salutationTwo]), null, new Criteria(), $this->context));

        $gatewayMock = $this->createMock(ShopwareLocalGateway::class);
        $gatewayMock->method('readTable')->willReturn([
            ['id' => '1', 'name' => 'shopsalutations', 'value' => 's:24:"mr,ms,salutation-invalid";'],
        ]);

        $gatewayRegistryMock = $this->createMock(GatewayRegistry::class);
        $gatewayRegistryMock->method('getGateway')->willReturn($gatewayMock);

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $connection
        );

        $this->reader = new SalutationReader($mock, $gatewayRegistryMock);
    }

    public function testGetPremapping(): void
    {
        $result = $this->reader->getPremapping($this->context, $this->migrationContext);

        static::assertInstanceOf(PremappingStruct::class, $result);
        static::assertCount(3, $result->getMapping());
        static::assertCount(2, $result->getChoices());

        $choices = $result->getChoices();
        static::assertSame('mr', $choices[0]->getDescription());
        static::assertSame('ms', $choices[1]->getDescription());
        static::assertSame($this->salutationOne->getId(), $result->getMapping()[0]->getDestinationUuid());
        static::assertSame($this->salutationTwo->getId(), $result->getMapping()[1]->getDestinationUuid());
        static::assertEmpty($result->getMapping()[2]->getDestinationUuid());
    }
}
