<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\unit\Migration\Validation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\Validation\SwagMigrationValidationContext;
use SwagMigrationAssistant\Profile\Shopware54\Shopware54Profile;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
#[CoversClass(SwagMigrationValidationContext::class)]
class SwagMigrationValidationContextTest extends TestCase
{
    public function testValidationContext(): void
    {
        $connection = new SwagMigrationConnectionEntity();
        $connection->setProfileName(Shopware54Profile::PROFILE_NAME);
        $connection->setGatewayName(DummyLocalGateway::GATEWAY_NAME);

        $migrationContext = new MigrationContext(
            $connection,
            new Shopware54Profile(),
            new DummyLocalGateway(),
            null,
            Uuid::randomHex(),
        );

        $context = Context::createDefaultContext();
        $entityDefinition = new CustomerDefinition();

        $validationContext = new SwagMigrationValidationContext(
            $context,
            $migrationContext,
            $entityDefinition,
            ['id' => Uuid::randomHex()],
            []
        );

        static::assertSame($context, $validationContext->getContext());
        static::assertSame($migrationContext, $validationContext->getMigrationContext());
        static::assertSame($entityDefinition, $validationContext->getEntityDefinition());
        static::assertSame(['id' => $validationContext->getConvertedData()['id']], $validationContext->getConvertedData());
        static::assertSame('customer', $validationContext->getValidationResult()->getEntityName());
        static::assertEmpty($validationContext->getValidationResult()->getLogs());
    }
}
