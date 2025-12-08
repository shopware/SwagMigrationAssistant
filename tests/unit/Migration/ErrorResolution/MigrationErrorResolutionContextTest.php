<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace unit\Migration\ErrorResolution;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\ErrorResolution\MigrationErrorResolutionContext;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
#[CoversClass(MigrationErrorResolutionContext::class)]
class MigrationErrorResolutionContextTest extends TestCase
{
    public function testErrorResolutionContext(): void
    {
        $data = [
            ['id' => '1', 'name' => 'Test 1'],
            ['id' => '2', 'name' => 'Test 2'],
        ];

        $fixes = [
            'entity1' => [],
            'entity2' => [],
        ];

        $connectionId = 'connection-id';
        $runId = 'run-id';

        $context = new MigrationErrorResolutionContext(
            $data,
            $fixes,
            $connectionId,
            $runId,
            Context::createDefaultContext(),
        );

        static::assertSame($data, $context->getData());
        static::assertSame($fixes, $context->getFixes());
        static::assertSame($connectionId, $context->getConnectionId());
        static::assertSame($runId, $context->getRunId());

        $newData = [
            ['id' => '3', 'name' => 'Test 3'],
        ];
        $context->setData($newData);
        static::assertSame($newData, $context->getData());

        $newFixes = [
            'entity3' => [],
        ];
        $context->setFixes($newFixes);
        static::assertSame($newFixes, $context->getFixes());
    }
}
