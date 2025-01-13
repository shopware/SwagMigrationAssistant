<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader;

use Doctrine\DBAL\ArrayParameterType;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Gateway\Reader\ReaderInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

#[Package('fundamentals@after-sales')]
class ProductReviewReader extends AbstractReader implements ReaderInterface
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME
            && $this->getDataSetEntity($migrationContext) === DefaultEntities::PRODUCT_REVIEW;
    }

    public function supportsTotal(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME;
    }

    public function read(MigrationContextInterface $migrationContext): array
    {
        $fetchedReviews = $this->fetchReviews($migrationContext);
        $fetchedReviews = $this->mapData($fetchedReviews, [], ['vote', 'mainShopId']);

        foreach ($fetchedReviews as &$review) {
            $review['_locale'] = \str_replace('_', '-', $review['_locale']);
        }

        return $this->cleanupResultSet($fetchedReviews);
    }

    public function readTotal(MigrationContextInterface $migrationContext): ?TotalStruct
    {
        $connection = $this->getConnection($migrationContext);

        $total = (int) $connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('s_articles_vote')
            ->executeQuery()
            ->fetchOne();

        return new TotalStruct(DefaultEntities::PRODUCT_REVIEW, $total);
    }

    private function fetchReviews(MigrationContextInterface $migrationContext): array
    {
        $ids = $this->fetchIdentifiers($migrationContext, 's_articles_vote', $migrationContext->getOffset(), $migrationContext->getLimit());

        $connection = $this->getConnection($migrationContext);
        $query = $connection->createQueryBuilder();

        $query->from('s_articles_vote', 'vote');
        $this->addTableSelection($query, 's_articles_vote', 'vote', $migrationContext);

        $query->leftJoin('vote', 's_core_shops', 'shop', 'shop.id = vote.shop_id OR (vote.shop_id IS NULL AND shop.default = 1)');
        $query->addSelect('shop.id as mainShopId');
        $query->leftJoin('shop', 's_core_locales', 'locale', 'shop.locale_id = locale.id');
        $query->addSelect('locale.locale as _locale');

        $query->where('vote.id IN (:ids)');
        $query->setParameter('ids', $ids, ArrayParameterType::STRING);

        $query->executeQuery();

        return $query->fetchAllAssociative();
    }
}
