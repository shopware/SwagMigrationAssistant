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
class NumberRangeReader extends AbstractReader implements ReaderInterface
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME
            && $this->getDataSetEntity($migrationContext) === DefaultEntities::NUMBER_RANGE;
    }

    public function supportsTotal(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME;
    }

    public function read(MigrationContextInterface $migrationContext): array
    {
        $numberRanges = $this->fetchNumberRanges($migrationContext->getOffset(), $migrationContext->getLimit(), $migrationContext);
        $prefix = \unserialize($this->fetchPrefix($migrationContext), ['allowed_classes' => false]);

        if (!$prefix) {
            $prefix = '';
        }

        $locale = $this->getDefaultShopLocale($migrationContext);

        foreach ($numberRanges as &$numberRange) {
            $numberRange['_locale'] = \str_replace('_', '-', $locale);
            $numberRange['prefix'] = $prefix;
        }

        return $numberRanges;
    }

    public function readTotal(MigrationContextInterface $migrationContext): ?TotalStruct
    {
        $connection = $this->getConnection($migrationContext);

        $total = (int) $connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('s_order_number')
            ->executeQuery()
            ->fetchOne();

        return new TotalStruct(DefaultEntities::NUMBER_RANGE, $total);
    }

    /**
     * @return array<int, array<string,mixed>>
     */
    private function fetchNumberRanges(int $offset, int $limit, MigrationContextInterface $migrationContext): array
    {
        $ids = $this->fetchIdentifiers($migrationContext, 's_order_number', $offset, $limit);
        $connection = $this->getConnection($migrationContext);

        $query = $connection->createQueryBuilder()
            ->select('*')
            ->from('s_order_number')
            ->where('id IN (:ids)')
            ->setParameter('ids', $ids, ArrayParameterType::STRING);

        return $query->executeQuery()->fetchAllAssociative();
    }

    private function fetchPrefix(MigrationContextInterface $migrationContext): string
    {
        $connection = $this->getConnection($migrationContext);

        $prefix = $connection->createQueryBuilder()
            ->select('value')
            ->from('s_core_config_elements')
            ->where('name = "backendautoordernumberprefix"')
            ->executeQuery()
            ->fetchOne();

        return $prefix ?: '';
    }
}
