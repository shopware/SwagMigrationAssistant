<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware6\Premapping;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\User\UserDefinition;
use Shopware\Core\System\User\UserEntity;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistry;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Premapping\PremappingEntityStruct;
use SwagMigrationAssistant\Migration\Premapping\PremappingStruct;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Shopware6ApiGateway;
use SwagMigrationAssistant\Profile\Shopware6\Premapping\UserReader;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;

#[Package('services-settings')]
class UserReaderTest extends TestCase
{
    use KernelTestBehaviour;

    private MigrationContextInterface $migrationContext;

    private UserReader $reader;

    private Context $context;

    private UserEntity $adminUserMock;

    private UserEntity $basicUserMock;

    protected function setUp(): void
    {
        $this->context = Context::createDefaultContext();

        $connection = new SwagMigrationConnectionEntity();
        $connection->setId(Uuid::randomHex());
        $connection->setProfileName(Shopware6MajorProfile::PROFILE_NAME);
        $connection->setGatewayName(Shopware6ApiGateway::GATEWAY_NAME);
        $connection->setCredentialFields([]);

        $this->adminUserMock = new UserEntity();
        $this->adminUserMock->setId(Uuid::randomHex());
        $this->adminUserMock->setAdmin(true);
        $this->adminUserMock->setUsername('admin');

        $this->basicUserMock = new UserEntity();
        $this->basicUserMock->setId(Uuid::randomHex());
        $this->basicUserMock->setUsername('basicUser');

        $premapping = [
            new PremappingStruct(DefaultEntities::USER, [
                new PremappingEntityStruct('', 'debit', $this->adminUserMock->getId()),
                new PremappingEntityStruct('cash', 'cash', $this->basicUserMock->getId()),
                new PremappingEntityStruct('payment-invalid', 'payment-invalid', Uuid::randomHex()),
            ], []),
        ];
        $connection->setPremapping($premapping);

        $mock = $this->createMock(EntityRepository::class);
        $mock->method('search')->willReturn(new EntitySearchResult(
            UserDefinition::ENTITY_NAME,
            2,
            new EntityCollection([$this->adminUserMock, $this->basicUserMock]),
            null,
            new Criteria(),
            $this->context
        ));

        $gatewayMock = $this->createMock(Shopware6ApiGateway::class);
        $gatewayMock->method('readTable')->willReturn([
            ['id' => '1', 'username' => 'admin'],
            ['id' => '2', 'username' => 'basicUser'],
            ['id' => '3', 'username' => 'foobar'],
        ]);

        $gatewayRegistryMock = $this->createMock(GatewayRegistry::class);
        $gatewayRegistryMock->method('getGateway')->willReturn($gatewayMock);

        $this->migrationContext = new MigrationContext(
            new Shopware6MajorProfile('6.6'),
            $connection
        );

        $this->reader = new UserReader($mock, $gatewayRegistryMock);
    }

    public function testGetPremapping(): void
    {
        $result = $this->reader->getPremapping($this->context, $this->migrationContext);

        static::assertCount(3, $result->getMapping());
        static::assertCount(2, $result->getChoices());

        $choices = $result->getChoices();
        static::assertSame('admin', $choices[0]->getDescription());
        static::assertSame('basicUser', $choices[1]->getDescription());

        // assert preselection
        static::assertSame($this->adminUserMock->getId(), $result->getMapping()[0]->getDestinationUuid());
        static::assertSame($this->basicUserMock->getId(), $result->getMapping()[1]->getDestinationUuid());
        static::assertEmpty($result->getMapping()[2]->getDestinationUuid());
    }
}
