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
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

#[Package('services-settings')]
class CustomerWishlistReader extends AbstractReader
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME
            && $this->getDataSetEntity($migrationContext) === DefaultEntities::CUSTOMER_WISHLIST;
    }

    public function supportsTotal(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME;
    }

    public function read(MigrationContextInterface $migrationContext): array
    {
        $this->setConnection($migrationContext);
        $fetched = $this->fetchData($migrationContext);
        $fetched = $this->mapData($fetched, [], ['note', 'subshopID']);

        return $this->cleanupResultSet($fetched);
    }

    public function readTotal(MigrationContextInterface $migrationContext): ?TotalStruct
    {
        $this->setConnection($migrationContext);

        $total = (int) $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('s_order_notes', 'note')
            ->innerJoin('note', 's_user', 'customer', 'note.userID = customer.id')
            ->executeQuery()
            ->fetchOne();

        return new TotalStruct(DefaultEntities::CUSTOMER_WISHLIST, $total);
    }

    private function fetchData(MigrationContextInterface $migrationContext): array
    {
        $ids = $this->fetchIdentifiers('s_order_notes', $migrationContext->getOffset(), $migrationContext->getLimit());
        $query = $this->connection->createQueryBuilder();

        $query->from('s_order_notes', 'note');
        $this->addTableSelection($query, 's_order_notes', 'note');

        $query->innerJoin('note', 's_user', 'customer', 'note.userID = customer.id');
        $query->addSelect('subshopID');

        $query->setParameter('ids', $ids, ArrayParameterType::STRING);
        $query->addOrderBy('note.id');

        $query->executeQuery();

        return $query->fetchAllAssociative();
    }
}
