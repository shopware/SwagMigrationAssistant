<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

#[Package('services-settings')]
class ProductPropertyRelationReader extends AbstractReader
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME
            && $this->getDataSetEntity($migrationContext) === DefaultEntities::PRODUCT_PROPERTY_RELATION;
    }

    public function supportsTotal(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME;
    }

    public function read(MigrationContextInterface $migrationContext): array
    {
        $this->setConnection($migrationContext);
        $fetchedProductProperties = $this->fetchData($migrationContext);

        $resultSet = $this->mapData($fetchedProductProperties, [], ['identifier', 'type', 'filter', 'name', 'value', 'productId']);

        return $this->cleanupResultSet($resultSet);
    }

    public function readTotal(MigrationContextInterface $migrationContext): ?TotalStruct
    {
        $this->setConnection($migrationContext);

        $sql = <<<SQL
SELECT COUNT(*)
FROM s_filter_values filter_value
INNER JOIN s_filter_articles filter_products ON filter_value.id = filter_products.valueID;
SQL;

        $total = (int) $this->connection->executeQuery($sql)->fetchOne();

        return new TotalStruct(DefaultEntities::PRODUCT_PROPERTY_RELATION, $total);
    }

    public function fetchData(MigrationContextInterface $migrationContext): array
    {
        $query = $this->connection->createQueryBuilder();

        $query->from('s_filter_values', 'filter_value');
        $query->addSelect('"property" AS type');
        $query->addSelect('value AS name');
        $this->addTableSelection($query, 's_filter_values', 'filter_value');

        $query->innerJoin('filter_value', 's_filter_articles', 'filter_product', 'filter_value.id = filter_product.valueID');
        $query->addSelect('MD5(CONCAT(filter_value.id, filter_product.articleID)) AS identifier');
        $query->addSelect('filter_product.articleID AS productId');

        $query->leftJoin('filter_value', 's_filter_options', 'filter_value_group', 'filter_value_group.id = filter_value.optionID');
        $this->addTableSelection($query, 's_filter_options', 'filter_value_group');

        $query->setFirstResult($migrationContext->getOffset());
        $query->setMaxResults($migrationContext->getLimit());

        $query->executeQuery();

        return $query->fetchAllAssociative();
    }
}
