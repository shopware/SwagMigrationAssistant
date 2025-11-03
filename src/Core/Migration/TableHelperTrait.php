<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Core\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\ColumnExistsTrait;

#[Package('after-sales')]
trait TableHelperTrait
{
    use ColumnExistsTrait;

    protected function indexExists(Connection $connection, string $table, string $indexName): bool
    {
        $exists = $connection->fetchAssociative('SHOW INDEX FROM `' . $table . '` WHERE Key_name = :indexName', ['indexName' => $indexName]);

        return !empty($exists);
    }
}
