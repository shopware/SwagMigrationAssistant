<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\unit\Migration\ErrorResolution;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\ErrorResolution\Event\MigrationPostErrorResolutionEvent;
use SwagMigrationAssistant\Migration\ErrorResolution\Event\MigrationPreErrorResolutionEvent;
use SwagMigrationAssistant\Migration\ErrorResolution\MigrationErrorResolutionService;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
#[CoversClass(MigrationErrorResolutionService::class)]
class MigrationErrorResolutionServiceTest extends TestCase
{
    public function testApply(): void
    {
        $expected = 'newValue';

        $dataIdOne = Uuid::randomHex();
        $dataIdTwo = Uuid::randomHex();
        $dataIdThree = Uuid::randomHex();
        $dataIdFour = Uuid::randomHex();

        $fixes = [
            [
                'entityId' => Uuid::fromHexToBytes($dataIdOne),
                'id' => Uuid::randomBytes(),
                'value' => \json_encode($expected, \JSON_THROW_ON_ERROR),
                'path' => 'other.path.to.value',
            ],
            [
                'entityId' => Uuid::fromHexToBytes($dataIdOne),
                'id' => Uuid::randomBytes(),
                'value' => \json_encode($expected, \JSON_THROW_ON_ERROR),
                'path' => 'the.path.to.value',
            ],
            [
                'entityId' => Uuid::fromHexToBytes($dataIdTwo),
                'id' => Uuid::randomBytes(),
                'value' => \json_encode($expected, \JSON_THROW_ON_ERROR),
                'path' => 'the.path.to.value',
            ],
            [
                'entityId' => Uuid::fromHexToBytes($dataIdThree),
                'id' => Uuid::randomBytes(),
                'value' => \json_encode($expected, \JSON_THROW_ON_ERROR),
                'path' => 'other.path.to.value',
            ],
            [
                'entityId' => Uuid::fromHexToBytes($dataIdFour),
                'id' => Uuid::randomBytes(),
                'value' => \json_encode($expected, \JSON_THROW_ON_ERROR),
                'path' => 'path.to.nested',
            ],
            [
                'entityId' => Uuid::fromHexToBytes($dataIdFour),
                'id' => Uuid::randomBytes(),
                'value' => \json_encode($expected, \JSON_THROW_ON_ERROR),
                'path' => 'path.to.other.nested',
            ],
            [
                'entityId' => Uuid::fromHexToBytes($dataIdFour),
                'id' => Uuid::randomBytes(),
                'value' => \json_encode($expected, \JSON_THROW_ON_ERROR),
                'path' => 'path.value',
            ],
            [
                'entityId' => Uuid::fromHexToBytes($dataIdFour),
                'id' => Uuid::randomBytes(),
                'value' => \json_encode($expected, \JSON_THROW_ON_ERROR),
                'path' => 'path.without.predefined.value',
            ],
        ];

        $data = [
            0 => [
                'id' => $dataIdOne,
                'name' => 'test',
                'the' => ['path' => ['to' => ['value' => 'oldValue']]],
                'other' => ['path' => ['to' => ['value' => 'oldValue']]],
            ],
            1 => [
                'id' => $dataIdTwo,
                'name' => 'test',
                'the' => ['path' => ['to' => ['value' => 'oldValue']]],
                'other' => ['path' => ['to' => ['value' => 'oldValue']]],
            ],
            2 => [
                'id' => $dataIdThree,
                'name' => 'test',
                'the' => ['path' => ['to' => ['value' => 'oldValue']]],
                'other' => ['path' => ['to' => ['value' => 'oldValue']]],
            ],
            3 => [
                'id' => $dataIdFour,
                'name' => 'test',
                'untouchedKey' => 'untouchedValue',
                'path' => [
                    'untouchedKey' => 'untouchedValue',
                    'value' => 'oldValue',
                    'to' => [
                        'untouchedKey' => 'untouchedValue',
                        'nested' => 'oldValue',
                        'other' => [
                            'untouchedKey' => 'untouchedValue',
                            'nested' => 'oldValue',
                        ],
                    ],
                ],
            ],
        ];

        $eventClasses = [];
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects(static::exactly(2))->method('dispatch')
            ->willReturnCallback(function ($event) use (&$eventClasses) {
                $eventClasses[] = $event::class;

                return $event;
            });

        $migrationFixApplier = new MigrationErrorResolutionService($this->createConnection($fixes), $eventDispatcher);

        $migrationFixApplier->applyFixes($data, Uuid::randomHex(), Uuid::randomHex(), Context::createDefaultContext());

        static::assertSame([
            MigrationPreErrorResolutionEvent::class,
            MigrationPostErrorResolutionEvent::class,
        ], $eventClasses);

        static::assertSame($expected, $data[0]['the']['path']['to']['value']);
        static::assertSame($expected, $data[0]['other']['path']['to']['value']);

        static::assertSame($expected, $data[1]['the']['path']['to']['value']);

        static::assertSame('oldValue', $data[2]['the']['path']['to']['value']);
        static::assertSame($expected, $data[2]['other']['path']['to']['value']);

        static::assertSame($expected, $data[3]['path']['value']);
        static::assertSame($expected, $data[3]['path']['value']);
        static::assertSame($expected, $data[3]['path']['to']['nested']);
        static::assertSame($expected, $data[3]['path']['to']['other']['nested']);

        // It is important to check other values in the path to ensure that they have not been changed.
        static::assertSame('untouchedValue', $data[3]['untouchedKey']);
        static::assertSame('untouchedValue', $data[3]['path']['untouchedKey']);
        static::assertSame('untouchedValue', $data[3]['path']['to']['untouchedKey']);
        static::assertSame('untouchedValue', $data[3]['path']['to']['other']['untouchedKey']);

        // Check value without predefined item array path and value are set
        static::assertSame($expected, $data[3]['path']['without']['predefined']['value']);
    }

    /**
     * @param array<int, array<string, string>> $fixes
     */
    private function createConnection(array $fixes): Connection
    {
        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->method('fetchAllAssociative')->willReturn($fixes);

        return $connectionMock;
    }
}
