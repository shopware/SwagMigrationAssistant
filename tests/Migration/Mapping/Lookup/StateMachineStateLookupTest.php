<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Mapping\Lookup;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use SwagMigrationAssistant\Migration\Mapping\Lookup\StateMachineStateLookup;

class StateMachineStateLookupTest extends TestCase
{
    use KernelTestBehaviour;

    #[DataProvider('getData')]
    public function testGet(string $technicalName, string $stateMachineTechnicalName, ?string $expectedResult): void
    {
        $stateMachineStateLookup = $this->getStateMachineStateLookup();

        static::assertSame($expectedResult, $stateMachineStateLookup->get($technicalName, $stateMachineTechnicalName, Context::createDefaultContext()));
    }

    #[DataProvider('getDatabaseData')]
    public function testGetShouldGetDataFromCache(string $technicalName, string $stateMachineTechnicalName, ?string $expectedResult): void
    {
        $stateMachineStateLookup = $this->getMockedStateMachineStateLookup();

        static::assertSame($expectedResult, $stateMachineStateLookup->get($technicalName, $stateMachineTechnicalName, Context::createDefaultContext()));
    }

    public function testReset(): void
    {
        $stateMachineStateLookup = $this->getMockedStateMachineStateLookup();

        $cacheProperty = new \ReflectionProperty(StateMachineStateLookup::class, 'cache');
        $cacheProperty->setAccessible(true);

        static::assertNotEmpty($cacheProperty->getValue($stateMachineStateLookup));

        $stateMachineStateLookup->reset();

        static::assertEmpty($cacheProperty->getValue($stateMachineStateLookup));
    }

    /**
     * @return array<array{technicalName: string, stateMachineTechnicalName: string, expectedResult: string|null}>
     */
    public static function getData(): array
    {
        $returnData = self::getDatabaseData();
        $returnData[] = ['technicalName' => 'Foo', 'stateMachineTechnicalName' => 'Foo', 'expectedResult' => null];
        $returnData[] = ['technicalName' => 'Bar', 'stateMachineTechnicalName' => 'Bar', 'expectedResult' => null];

        return $returnData;
    }

    /**
     * @return array<array{technicalName: string, stateMachineTechnicalName: string, expectedResult: string}>
     */
    public static function getDatabaseData(): array
    {
        $criteria = new Criteria();
        $criteria->addAssociation('stateMachine');
        $list = self::getContainer()->get('state_machine_state.repository')->search($criteria, Context::createDefaultContext());

        $returnData = [];
        foreach ($list as $stateMachineState) {
            static::assertInstanceOf(StateMachineStateEntity::class, $stateMachineState);

            $stateMachineTechnicalName = $stateMachineState->getStateMachine()?->getTechnicalName();
            static::assertNotNull($stateMachineTechnicalName);

            $returnData[] = [
                'technicalName' => $stateMachineState->getTechnicalName(),
                'stateMachineTechnicalName' => $stateMachineTechnicalName,
                'expectedResult' => $stateMachineState->getId(),
            ];
        }

        return $returnData;
    }

    private function getStateMachineStateLookup(): StateMachineStateLookup
    {
        return static::getContainer()->get(StateMachineStateLookup::class);
    }

    private function getMockedStateMachineStateLookup(): StateMachineStateLookup
    {
        $entityRepository = $this->createMock(EntityRepository::class);
        $entityRepository->method('searchIds')->willThrowException(new \Exception('StateMachineStateLookup repository should not be called'));

        $stateMachineStateLookup = new StateMachineStateLookup($entityRepository);

        $reflectionProperty = new \ReflectionProperty(StateMachineStateLookup::class, 'cache');
        $reflectionProperty->setAccessible(true);

        $databaseData = self::getDatabaseData();

        $cache = [];
        foreach ($databaseData as $data) {
            $cache[$data['technicalName'] . '-' . $data['stateMachineTechnicalName']] = $data['expectedResult'];
        }

        $reflectionProperty->setValue($stateMachineStateLookup, $cache);

        return $stateMachineStateLookup;
    }
}
