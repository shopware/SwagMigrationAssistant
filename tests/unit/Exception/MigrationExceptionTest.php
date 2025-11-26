<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\unit\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Exception\MigrationException;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
#[CoversClass(MigrationException::class)]
class MigrationExceptionTest extends TestCase
{
    public function testDuplicateSourceConnection(): void
    {
        $exception = MigrationException::duplicateSourceConnection();

        static::assertSame(409, $exception->getStatusCode());
        static::assertSame('SWAG_MIGRATION__DUPLICATE_SOURCE_CONNECTION', $exception->getErrorCode());
        static::assertSame('A connection to this source system already exists.', $exception->getMessage());
    }
}
