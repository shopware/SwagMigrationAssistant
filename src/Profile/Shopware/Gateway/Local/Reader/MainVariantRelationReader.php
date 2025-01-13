<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Gateway\Reader\ReaderInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

#[Package('fundamentals@after-sales')]
class MainVariantRelationReader extends AbstractReader implements ReaderInterface
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME
            && $this->getDataSetEntity($migrationContext) === DefaultEntities::MAIN_VARIANT_RELATION;
    }

    public function supportsTotal(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME;
    }

    public function read(MigrationContextInterface $migrationContext): array
    {
        return $this->fetchMainVariantRelations($migrationContext);
    }

    public function readTotal(MigrationContextInterface $migrationContext): ?TotalStruct
    {
        $connection = $this->getConnection($migrationContext);

        $total = (int) $connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('s_articles')
            ->where('main_detail_id IS NOT NULL')
            ->andWhere('configurator_set_id IS NOT NULL')
            ->executeQuery()
            ->fetchOne();

        return new TotalStruct(DefaultEntities::MAIN_VARIANT_RELATION, $total);
    }

    private function fetchMainVariantRelations(MigrationContextInterface $migrationContext): array
    {
        $connection = $this->getConnection($migrationContext);

        return $connection->createQueryBuilder()
            ->addSelect('articles.id, details.ordernumber')
            ->from('s_articles', 'articles')
            ->innerJoin('articles', 's_articles_details', 'details', 'details.id = articles.main_detail_id')
            ->where('main_detail_id IS NOT NULL')
            ->andWhere('configurator_set_id IS NOT NULL')
            ->setFirstResult($migrationContext->getOffset())
            ->setMaxResults($migrationContext->getLimit())
            ->executeQuery()
            ->fetchAllAssociative();
    }
}
