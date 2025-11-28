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
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Validation\Log\MigrationValidationExceptionLog;
use SwagMigrationAssistant\Migration\Validation\MigrationValidationResult;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
#[CoversClass(MigrationValidationResult::class)]
class MigrationValidationResultTest extends TestCase
{
    public function testValidationResult(): void
    {
        $result = new MigrationValidationResult(CustomerDefinition::ENTITY_NAME);

        static::assertSame(CustomerDefinition::ENTITY_NAME, $result->getEntityName());
        static::assertFalse($result->hasLogs());
        static::assertEmpty($result->getLogs());

        $log = new MigrationValidationExceptionLog(
            'test1',
            'test2',
            'test3',
        );
        $result->addLog($log);

        static::assertTrue($result->hasLogs());
        static::assertCount(1, $result->getLogs());
        static::assertSame($log, $result->getLogs()[0]);
    }
}
