<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware\Gateway\Local;

use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Shopware5DatabaseConnection;

trait LocalCredentialTrait
{
    public string $db_name = Shopware5DatabaseConnection::DB_NAME;

    public string $db_user = Shopware5DatabaseConnection::DB_USER;

    public string $db_password = Shopware5DatabaseConnection::DB_PASSWORD;

    public string $db_host = Shopware5DatabaseConnection::DB_HOST;

    public string $db_port = Shopware5DatabaseConnection::DB_PORT;

    private SwagMigrationConnectionEntity $connection;

    private string $runId;

    public function connectionSetup(): void
    {
        if (\getenv('SWAG_MIGRATION_ASSISTANT_SKIP_SW5_TESTS') === 'true') {
            static::markTestSkipped('Shopware 5 test database not available. Skipping test');
        }

        $this->runId = Uuid::randomHex();
        $this->connection = new SwagMigrationConnectionEntity();
        $this->connection->setId(Uuid::randomHex());
        $this->connection->setCredentialFields(
            [
                'dbName' => $this->db_name,
                'dbUser' => $this->db_user,
                'dbPassword' => $this->db_password,
                'dbHost' => $this->db_host,
                'dbPort' => $this->db_port,
            ]
        );
        $this->connection->setProfileName(Shopware55Profile::PROFILE_NAME);
        $this->connection->setGatewayName(ShopwareLocalGateway::GATEWAY_NAME);
    }
}
