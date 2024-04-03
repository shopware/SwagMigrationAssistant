<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware\Premapping;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Rule\RuleDefinition;
use Shopware\Core\Content\Rule\RuleEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Premapping\PremappingStruct;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\Premapping\DefaultShippingAvailabilityRuleReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

#[Package('services-settings')]
class DefaultShippingAvailabilityRuleReaderTest extends TestCase
{
    use KernelTestBehaviour;

    private MigrationContextInterface $migrationContext;

    private DefaultShippingAvailabilityRuleReader $reader;

    private Context $context;

    private RuleEntity $ruleEntity;

    private RuleEntity $anotherRuleEntity;

    private SwagMigrationConnectionEntity $connection;

    protected function setUp(): void
    {
        $this->context = Context::createDefaultContext();

        $this->connection = new SwagMigrationConnectionEntity();
        $this->connection->setId(Uuid::randomHex());
        $this->connection->setProfileName(Shopware55Profile::PROFILE_NAME);
        $this->connection->setGatewayName(ShopwareLocalGateway::GATEWAY_NAME);
        $this->connection->setCredentialFields([]);

        $this->ruleEntity = new RuleEntity();
        $this->ruleEntity->setId(Uuid::randomHex());
        $this->ruleEntity->setName('My default rule');

        $this->anotherRuleEntity = new RuleEntity();
        $this->anotherRuleEntity->setId(Uuid::randomHex());
        $this->anotherRuleEntity->setName('Another rule');
    }

    public function testGetValidPremapping(): void
    {
        $premapping = [[
            'entity' => DefaultShippingAvailabilityRuleReader::getMappingName(),
            'mapping' => [
                0 => [
                    'sourceId' => DefaultShippingAvailabilityRuleReader::SOURCE_ID,
                    'description' => 'Description of the default rule',
                    'destinationUuid' => $this->ruleEntity->getId(),
                ],
            ],
        ]];
        $this->connection->setPremapping($premapping);

        $mock = $this->createMock(EntityRepository::class);
        $mock->method('search')->willReturn(new EntitySearchResult(RuleDefinition::ENTITY_NAME, 2, new EntityCollection([$this->ruleEntity, $this->anotherRuleEntity]), null, new Criteria(), $this->context));

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection
        );

        $this->reader = new DefaultShippingAvailabilityRuleReader($mock);

        $result = $this->reader->getPremapping($this->context, $this->migrationContext);

        static::assertInstanceOf(PremappingStruct::class, $result);
        static::assertCount(1, $result->getMapping());
        static::assertCount(2, $result->getChoices());

        $choices = $result->getChoices();
        static::assertSame($this->ruleEntity->getName(), $choices[0]->getDescription());
        static::assertSame($this->anotherRuleEntity->getName(), $choices[1]->getDescription());
        static::assertSame($this->ruleEntity->getId(), $result->getMapping()[0]->getDestinationUuid());
    }

    public function testGetInvalidPremapping(): void
    {
        $premapping = [[
            'entity' => DefaultShippingAvailabilityRuleReader::getMappingName(),
            'mapping' => [
                0 => [
                    'sourceId' => DefaultShippingAvailabilityRuleReader::SOURCE_ID,
                    'description' => 'Description of the default rule',
                    'destinationUuid' => Uuid::randomHex(),
                ],
            ],
        ]];
        $this->connection->setPremapping($premapping);

        $mock = $this->createMock(EntityRepository::class);
        $mock->method('search')->willReturn(new EntitySearchResult(RuleDefinition::ENTITY_NAME, 2, new EntityCollection([$this->ruleEntity, $this->anotherRuleEntity]), null, new Criteria(), $this->context));

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection
        );

        $this->reader = new DefaultShippingAvailabilityRuleReader($mock);

        $result = $this->reader->getPremapping($this->context, $this->migrationContext);

        static::assertInstanceOf(PremappingStruct::class, $result);
        static::assertCount(1, $result->getMapping());
        static::assertCount(2, $result->getChoices());

        $choices = $result->getChoices();
        static::assertSame($this->ruleEntity->getName(), $choices[0]->getDescription());
        static::assertSame($this->anotherRuleEntity->getName(), $choices[1]->getDescription());
        static::assertEmpty($result->getMapping()[0]->getDestinationUuid());
    }
}
