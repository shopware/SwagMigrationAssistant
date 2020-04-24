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

trait LocalCredentialTrait
{
    public $db_name = 'sw55';

    public $db_user = 'root';

    public $db_password = 'app';

    public $db_host = 'mysql';

    public $db_port = '3306';

    /**
     * @var SwagMigrationConnectionEntity
     */
    private $connection;

    /**
     * @var string
     */
    private $runId;

    public function connectionSetup(): void
    {
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
