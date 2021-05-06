<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Gateway\Connection;

use GuzzleHttp\Client;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

class ConnectionFactory implements ConnectionFactoryInterface
{
    /**
     * @var EntityRepositoryInterface
     */
    private $connectionRepositoy;

    public function __construct(EntityRepositoryInterface $connectionRepositoy)
    {
        $this->connectionRepositoy = $connectionRepositoy;
    }

    public function createApiClient(MigrationContextInterface $migrationContext): ?AuthClient
    {
        $connection = $migrationContext->getConnection();

        if ($connection === null) {
            return null;
        }

        $credentials = $connection->getCredentialFields();

        if ($credentials === null) {
            return null;
        }

        $options = [
            'base_uri' => \rtrim($credentials['endpoint'], '/') . '/',
            'connect_timeout' => 5.0,
            'verify' => false,
        ];

        return new AuthClient(
            new Client($options),
            $this->connectionRepositoy,
            $migrationContext,
            Context::createDefaultContext() // ToDo maybe replace this with the real context from the request, because this could cause caching issues (but it will only write data to DB).
        );
    }
}
