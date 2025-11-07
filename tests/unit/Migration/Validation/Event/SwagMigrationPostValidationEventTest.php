<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\unit\Migration\Validation\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\Validation\Event\SwagMigrationPostValidationEvent;
use SwagMigrationAssistant\Migration\Validation\SwagMigrationValidationContext;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
#[CoversClass(SwagMigrationPostValidationEvent::class)]
class SwagMigrationPostValidationEventTest extends TestCase
{
    public function testPostValidationEvent(): void
    {
        $context = new SwagMigrationValidationContext(
            Context::createDefaultContext(),
            new MigrationContext(new SwagMigrationConnectionEntity()),
            new CustomerDefinition(),
            [],
            []
        );

        $event = new SwagMigrationPostValidationEvent($context);

        static::assertSame($context, $event->getValidationContext());
        static::assertSame($context->getContext(), $event->getContext());
    }
}
