<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace unit\Migration\ErrorResolution\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\ErrorResolution\Event\MigrationPostErrorResolutionEvent;
use SwagMigrationAssistant\Migration\ErrorResolution\MigrationErrorResolutionContext;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
#[CoversClass(MigrationPostErrorResolutionEvent::class)]
class MigrationPostErrorResolutionEventTest extends TestCase
{
    public function testPostErrorResolutionEvent(): void
    {
        $data = [];
        $fixes = [];

        $context = new MigrationErrorResolutionContext(
            $data,
            $fixes,
            'connectionId',
            'runId',
            Context::createDefaultContext()
        );

        $event = new MigrationPostErrorResolutionEvent($context);

        static::assertSame($context, $event->getErrorResolutionContext());
        static::assertSame($context->getContext(), $event->getContext());
    }
}
