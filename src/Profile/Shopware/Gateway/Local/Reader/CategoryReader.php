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
class CategoryReader extends AbstractReader
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME
            && $this->getDataSetEntity($migrationContext) === DefaultEntities::CATEGORY;
    }

    public function supportsTotal(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME;
    }

    public function read(MigrationContextInterface $migrationContext): array
    {
        $this->setConnection($migrationContext);

        $fetchedCategories = $this->fetchData($migrationContext);
        $mainCategoryLocales = $this->fetchMainCategoryLocales();

        $categories = $this->mapData($fetchedCategories, [], ['category', 'categorypath', 'previousSiblingId', 'categoryPosition']);
        $resultSet = $this->generateAllLocales($categories, $mainCategoryLocales);

        return $this->cleanupResultSet($resultSet);
    }

    public function readTotal(MigrationContextInterface $migrationContext): TotalStruct
    {
        $this->setConnection($migrationContext);

        $total = (int) $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('s_categories')
            ->where('path IS NOT NULL AND parent IS NOT NULL')
            ->executeQuery()
            ->fetchOne();

        return new TotalStruct(DefaultEntities::CATEGORY, $total);
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
        $query->addOrderBy('category.parent');
        $query->addOrderBy('IFNULL(category.position, IFNULL((SELECT previous.position + category.id
                                                                                          FROM s_categories previous
                                                                                          WHERE category.parent = previous.parent
                                                                                          ORDER BY previous.position DESC
                                                                                          LIMIT 1), category.id))');
        $query->setFirstResult($migrationContext->getOffset());
        $query->setMaxResults($migrationContext->getLimit());
        $query->executeQuery();

        return $query->fetchAllAssociative();
    }

    private function generateAllLocales(array $categories, array $mainCategoryLocales): array
    {
        $resultSet = [];
        $ignoredCategories = $this->getIgnoredCategories();
        $defaultLocale = \str_replace('_', '-', $this->getDefaultShopLocale());

        foreach ($categories as $category) {
            $locale = '';
            if (\in_array($category['parent'], $ignoredCategories, true)) {
                $category['parent'] = null;
            }
            if (!empty($category['path'])) {
                $parentCategoryIds = \array_values(
                    \array_filter(\explode('|', $category['path']))
                );
                foreach ($parentCategoryIds as $parentCategoryId) {
                    if (isset($mainCategoryLocales[$parentCategoryId])) {
                        $locale = \str_replace('_', '-', $mainCategoryLocales[$parentCategoryId]);

                        break;
                    }
                }
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
        $query->executeQuery();

        return $query->fetchFirstColumn();
    }

    private function fetchMainCategoryLocales(): array
    {
        $query = $this->connection->createQueryBuilder();

        $query->from('s_core_shops', 'shop');
        $query->addSelect('shop.category_id');
        $query->leftJoin('shop', 's_core_locales', 'locale', 'locale.id = shop.locale_id');
        $query->addSelect('locale.locale');
        $query->executeQuery();

        return $query->fetchAllKeyValue();
    }
}
