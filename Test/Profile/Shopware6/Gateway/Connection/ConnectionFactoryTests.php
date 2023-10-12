<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware6\Gateway\Connection;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Connection\AuthClient;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;

class ConnectionFactoryTests extends TestCase
{
    /**
     * @dataProvider getCreateApiClientTestData
     */
    public function testCreateApiClient(?array $credentials, bool $expectedToBeNull): void
    {
        $connection = new SwagMigrationConnectionEntity();

        if ($credentials !== null) {
            $connection->setCredentialFields($credentials);
        }

        $migrationContext = new MigrationContext(
            new Shopware6MajorProfile('6.5.6.1'),
            $connection,
        );

        $connection = new ConnectionFactory(new StaticEntityRepository([]));
        $result = $connection->createApiClient($migrationContext);

        if ($expectedToBeNull) {
            static::assertNull($result);
        } else {
            static::assertNotNull($result);
            static::assertInstanceOf(AuthClient::class, $result);
        }
    }

    public function getCreateApiClientTestData(): \Generator
    {
        yield 'Empty credentials should return null' => [
            'credentials' => [],
            'expectedToBeNull' => true,
        ];

        yield 'Null credentials should return null' => [
            'credentials' => null,
            'expectedToBeNull' => true,
        ];

        yield 'Credentials without endpoint key should return null' => [
            'credentials' => [
                'other' => 'test',
            ],
            'expectedToBeNull' => true,
        ];

        yield 'Filled credentials with endpoint key should return a instance' => [
            'credentials' => [
                'endpoint' => 'foobar',
            ],
            'expectedToBeNull' => false,
        ];
    }
}
