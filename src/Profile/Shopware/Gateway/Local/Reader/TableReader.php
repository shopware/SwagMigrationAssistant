<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Gateway\TableReaderInterface;

#[Package('fundamentals@after-sales')]
class TableReader extends AbstractReader implements TableReaderInterface
{
    public function read(MigrationContextInterface $migrationContext, string $tableName, array $filter = []): array
    {
        $connection = $this->getConnection($migrationContext);

        $query = $connection->createQueryBuilder();
        $query->select('*');
        $query->from($tableName);

        if (!empty($filter)) {
            foreach ($filter as $property => $value) {
                $query->andWhere($property . ' = :value');
                $query->setParameter('value', $value);
            }
        }

        $query = $query->executeQuery();

        return $query->fetchAllAssociative();
    }
}
