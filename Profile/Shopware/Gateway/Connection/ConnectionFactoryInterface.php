<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Connection;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Gateway\HttpClientInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('services-settings')]
interface ConnectionFactoryInterface
{
    public function createApiClient(MigrationContextInterface $migrationContext, bool $verify = false): ?HttpClientInterface;

    public function createDatabaseConnection(MigrationContextInterface $migrationContext): ?Connection;
}
