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
use SwagMigrationAssistant\Migration\Validation\Event\MigrationPostValidationEvent;
use SwagMigrationAssistant\Migration\Validation\MigrationValidationContext;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
#[CoversClass(MigrationPostValidationEvent::class)]
class MigrationPostValidationEventTest extends TestCase
{
    public function testPostValidationEvent(): void
    {
        $context = new MigrationValidationContext(
            Context::createDefaultContext(),
            new MigrationContext(new SwagMigrationConnectionEntity()),
            new CustomerDefinition(),
            [],
            []
        );

        $event = new MigrationPostValidationEvent($context);

        static::assertSame($context, $event->getValidationContext());
        static::assertSame($context->getContext(), $event->getContext());
    }
}
