<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader;

use Doctrine\DBAL\ArrayParameterType;
use Shopware\Core\Framework\DataAbstractionLayer\Doctrine\FetchModeHelper;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

#[Package('services-settings')]
class PromotionReader extends AbstractReader
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME
            && $this->getDataSetEntity($migrationContext) === DefaultEntities::PROMOTION;
    }

    public function supportsTotal(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME;
    }

    public function read(MigrationContextInterface $migrationContext): array
    {
        $this->setConnection($migrationContext);
        $ids = $this->fetchIdentifiers('s_emarketing_vouchers', $migrationContext->getOffset(), $migrationContext->getLimit());
        $fetchedPromotions = $this->fetchPromotions($ids);
        $fetchedCodes = $this->fetchIndividualCodes($ids);
        $fetchedPromotions = $this->mapData($fetchedPromotions, [], ['vouchers']);

        foreach ($fetchedPromotions as &$promotion) {
            $promotionId = $promotion['id'];

            if (isset($fetchedCodes[$promotionId])) {
                $promotion['individualCodes'] = $fetchedCodes[$promotionId];
            }
        }

        return $this->cleanupResultSet($fetchedPromotions);
    }

    public function readTotal(MigrationContextInterface $migrationContext): ?TotalStruct
    {
        $this->setConnection($migrationContext);

        $total = (int) $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('s_emarketing_vouchers')
            ->executeQuery()
            ->fetchOne();

        return new TotalStruct(DefaultEntities::PROMOTION, $total);
    }

    private function fetchIndividualCodes(array $ids): array
    {
        $query = $this->connection->createQueryBuilder();

        $query->from('s_emarketing_voucher_codes', 'codes');
        $query->addSelect('codes.voucherID');
        $this->addTableSelection($query, 's_emarketing_voucher_codes', 'codes');

        $query->leftJoin('codes', 's_user', 'user', 'codes.userID = user.id');
        $query->addSelect('user.firstname AS `codes.firstname`, user.lastname AS `codes.lastname`');

        $query->where('codes.voucherID IN (:ids)');
        $query->setParameter('ids', $ids, ArrayParameterType::STRING);

        $fetchedCodes = FetchModeHelper::group($query->executeQuery()->fetchAllAssociative());

        return $this->mapData($fetchedCodes, [], ['codes']);
    }

    private function fetchPromotions(array $ids): array
    {
        $query = $this->connection->createQueryBuilder();

        $query->from('s_emarketing_vouchers', 'vouchers');
        $this->addTableSelection($query, 's_emarketing_vouchers', 'vouchers');

        $query->where('vouchers.id IN (:ids)');
        $query->setParameter('ids', $ids, ArrayParameterType::STRING);

        $query->executeQuery();

        return $query->fetchAllAssociative();
    }
}
