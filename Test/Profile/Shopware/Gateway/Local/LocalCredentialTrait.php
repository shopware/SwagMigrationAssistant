<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware\Gateway\Local;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Shopware5DatabaseConnection;

#[Package('services-settings')]
trait LocalCredentialTrait
{
    protected SwagMigrationConnectionEntity $connection;

    protected string $runId;

    public function connectionSetup(): void
    {
        if (\getenv('SWAG_MIGRATION_ASSISTANT_SKIP_SW5_TESTS') === 'true') {
            static::markTestSkipped('Shopware 5 test database not available. Skipping test');
        }

        $dbUrlParts = \parse_url($_SERVER['DATABASE_URL'] ?? '') ?: [];
        $dbUrlParts['path'] ??= 'root';

        $this->runId = Uuid::randomHex();
        $this->connection = new SwagMigrationConnectionEntity();
        $this->connection->setId(Uuid::randomHex());
        $this->connection->setCredentialFields(
            [
                'dbName' => Shopware5DatabaseConnection::DB_NAME,
                'dbUser' => $dbUrlParts['user'] ?? 'root',
                'dbPassword' => $dbUrlParts['pass'] ?? '',
                'dbHost' => $dbUrlParts['host'] ?? 'localhost',
                'dbPort' => $dbUrlParts['port'] ?? 3306,
            ]
        );
        $this->connection->setProfileName(Shopware55Profile::PROFILE_NAME);
        $this->connection->setGatewayName(ShopwareLocalGateway::GATEWAY_NAME);
    }
}
