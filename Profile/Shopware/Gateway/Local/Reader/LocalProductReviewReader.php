<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader;

use Doctrine\DBAL\Connection;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

class LocalProductReviewReader extends LocalAbstractReader implements LocalReaderInterface
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getDataSet()::getEntity() === DefaultEntities::PRODUCT_REVIEW;
    }

    public function read(MigrationContextInterface $migrationContext, array $params = []): array
    {
        $this->setConnection($migrationContext);
        $fetchedReviews = $this->fetchReviews($migrationContext);
        $fetchedReviews = $this->mapData($fetchedReviews, [], ['vote', 'mainShopId']);

        foreach ($fetchedReviews as &$review) {
            $review['_locale'] = str_replace('_', '-', $review['_locale']);
        }

        return $this->cleanupResultSet($fetchedReviews);
    }

    private function fetchReviews(MigrationContextInterface $migrationContext): array
    {
        $ids = $this->fetchIdentifiers('s_articles_vote', $migrationContext->getOffset(), $migrationContext->getLimit());

        $query = $this->connection->createQueryBuilder();

        $query->from('s_articles_vote', 'vote');
        $this->addTableSelection($query, 's_articles_vote', 'vote');

        $query->leftJoin('vote', 's_core_shops', 'shop', 'shop.id = vote.shop_id OR (vote.shop_id IS NULL AND shop.default = 1)');
        $query->addSelect('shop.id as mainShopId');
        $query->leftJoin('shop', 's_core_locales', 'locale', 'shop.locale_id = locale.id');
        $query->addSelect('locale.locale as _locale');

        $query->where('vote.id IN (:ids)');
        $query->setParameter('ids', $ids, Connection::PARAM_STR_ARRAY);

        return $query->execute()->fetchAll();
    }
}
