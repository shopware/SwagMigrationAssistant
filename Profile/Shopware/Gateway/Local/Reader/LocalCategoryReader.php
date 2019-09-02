<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader;

use Doctrine\DBAL\Connection;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

class LocalCategoryReader extends LocalAbstractReader implements LocalReaderInterface
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getDataSet()::getEntity() === DefaultEntities::CATEGORY;
    }

    public function read(MigrationContextInterface $migrationContext, array $params = []): array
    {
        $this->setConnection($migrationContext);

        $fetchedCategories = $this->fetchData($migrationContext);

        $topMostParentIds = $this->getTopMostParentIds($fetchedCategories);
        $topMostCategories = $this->fetchCategoriesById($topMostParentIds);

        $categories = $this->mapData($fetchedCategories, [], ['category', 'categorypath', 'previousSiblingId', 'categoryPosition']);

        $resultSet = $this->setAllLocales($categories, $topMostCategories);

        return $this->cleanupResultSet($resultSet);
    }

    private function fetchData(MigrationContextInterface $migrationContext): array
    {
        $query = $this->connection->createQueryBuilder();
        $query->from('s_categories', 'category');
        $this->addTableSelection($query, 's_categories', 'category');
        $query->addSelect('REPLACE(category.path, "|", "") as categorypath');

        $query->leftJoin('category', 's_categories_attributes', 'attributes', 'category.id = attributes.categoryID');
        $this->addTableSelection($query, 's_categories_attributes', 'attributes');

        $query->leftJoin('category', 's_media', 'asset', 'category.mediaID = asset.id');
        $this->addTableSelection($query, 's_media', 'asset');

        $query->leftJoin(
            'category',
            's_categories',
            'sibling',
            'sibling.id = (SELECT previous.id
                           FROM (SELECT sub_category.id, sub_category.parent,
                                        IFNULL(sub_category.position, IFNULL(
                                                                    (SELECT new_position.position + sub_category.id
                                                                     FROM s_categories new_position
                                                                     WHERE sub_category.parent = new_position.parent
                                                                     ORDER BY new_position.position DESC
                                                                     LIMIT 1),
                                                                    sub_category.id)) position
                                 FROM s_categories sub_category) previous
                           WHERE previous.position < IFNULL(category.position, IFNULL((SELECT previous.position + category.id
                                                                                       FROM s_categories previous
                                                                                       WHERE category.parent = previous.parent
                                                                                       ORDER BY previous.position DESC
                                                                                       LIMIT 1), category.id))
                                 AND category.parent = previous.parent
                           ORDER BY previous.position DESC
                           LIMIT 1)'
        );
        $query->addSelect('sibling.id as previousSiblingId');
        $query->addSelect('IFNULL(category.position, IFNULL((SELECT previous.position + category.id
                                         FROM s_categories previous
                                         WHERE category.parent = previous.parent
                                         ORDER BY previous.position DESC
                                         LIMIT 1), category.id)) as categoryPosition');

        $query->andWhere('category.parent IS NOT NULL');
        $query->orderBy('LENGTH(categorypath)');
        $query->orderBy('category.parent');
        $query->addOrderBy('IFNULL(category.position, IFNULL((SELECT previous.position + category.id
                                                                                          FROM s_categories previous
                                                                                          WHERE category.parent = previous.parent
                                                                                          ORDER BY previous.position DESC
                                                                                          LIMIT 1), category.id))');
        $query->setFirstResult($migrationContext->getOffset());
        $query->setMaxResults($migrationContext->getLimit());

        return $query->execute()->fetchAll();
    }

    private function getTopMostParentIds(array $categories): array
    {
        $ids = [];
        foreach ($categories as $key => $category) {
            if (empty($category['category.path'])) {
                continue;
            }
            $parentCategoryIds = array_values(
                array_filter(explode('|', (string) $category['category.path']))
            );
            $topMostParent = end($parentCategoryIds);
            if (!in_array($topMostParent, $ids, true)) {
                $ids[] = $topMostParent;
            }
        }

        return $ids;
    }

    private function fetchCategoriesById(array $topMostParentIds): array
    {
        $query = $this->connection->createQueryBuilder();

        $query->from('s_categories', 'category');
        $query->addSelect('category.id');

        $query->leftJoin('category', 's_core_shops', 'shop', 'category.id = shop.category_id');
        $query->leftJoin('shop', 's_core_locales', 'locale', 'locale.id = shop.locale_id');
        $query->addSelect('locale.locale');

        $query->where('category.id IN (:ids)');
        $query->setParameter('ids', $topMostParentIds, Connection::PARAM_INT_ARRAY);

        $query->orderBy('category.parent');

        return $query->execute()->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    private function setAllLocales(array $categories, array $topMostCategories): array
    {
        $resultSet = [];
        $ignoredCategories = $this->getIgnoredCategories();
        $defaultLocale = str_replace('_', '-', $this->getDefaultShopLocale());

        foreach ($categories as $key => $category) {
            $locale = '';
            if (in_array($category['parent'], $ignoredCategories, true)) {
                $category['parent'] = null;
            }
            $topMostParent = $category['id'];
            if (!empty($category['path'])) {
                $parentCategoryIds = array_values(
                    array_filter(explode('|', $category['path']))
                );
                $topMostParent = end($parentCategoryIds);
            }
            if (isset($topMostCategories[$topMostParent])) {
                $locale = str_replace('_', '-', $topMostCategories[$topMostParent]);
            }

            if (empty($locale)) {
                $locale = $defaultLocale;
            }
            $category['_locale'] = $locale;
            $resultSet[] = $category;
        }

        return $resultSet;
    }

    private function getIgnoredCategories(): array
    {
        $query = $this->connection->createQueryBuilder();

        $query->addSelect('category.id');
        $query->from('s_categories', 'category');
        $query->andWhere('category.parent IS NULL AND category.path IS NULL');

        return $query->execute()->fetchAll(\PDO::FETCH_COLUMN);
    }
}
