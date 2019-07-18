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

        $categories = $this->mapData($fetchedCategories, [], ['category', 'categorypath', 'previousSiblingId']);

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

        $query->leftJoin('category', 's_categories', 'sibling', 'category.parent = sibling.parent AND CAST(category.position AS SIGNED) - 1 = CAST(sibling.position AS SIGNED)');
        $query->addSelect('sibling.id as previousSiblingId');

        $query->andWhere('category.parent IS NOT NULL');
        $query->orderBy('LENGTH(categorypath)');
        $query->addOrderBy('category.position');
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