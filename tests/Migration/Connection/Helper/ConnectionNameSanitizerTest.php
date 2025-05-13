<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Connection\Helper;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Connection\Helper\ConnectionNameSanitizer;

#[Package('fundamentals@after-sales')]
class ConnectionNameSanitizerTest extends TestCase
{
    #[DataProvider('nameProvider')]
    public function testSanitize(string $name, string $expected): void
    {
        static::assertSame($expected, ConnectionNameSanitizer::sanitize($name));
    }

    public static function nameProvider(): \Generator
    {
        yield ['Test Name', 'TestName'];
        yield ['Test-Name', 'TestName'];
        yield ['Test_Name', 'TestName'];
        yield ['Test Name 123', 'TestName123'];
        yield ['Test@Name!', 'TestName'];
        yield ['Test&Name*', 'TestName'];
        yield ['Test Name 123@!', 'TestName123'];
    }
}
