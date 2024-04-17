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
class NumberRangeReader extends AbstractReader
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
        $this->setConnection($migrationContext);
        $numberRanges = $this->fetchNumberRanges();
        $prefix = \unserialize($this->fetchPrefix(), ['allowed_classes' => false]);

        if (!$prefix) {
            $prefix = '';
        }

        $locale = $this->getDefaultShopLocale();

        foreach ($numberRanges as &$numberRange) {
            $numberRange['_locale'] = \str_replace('_', '-', $locale);
            $numberRange['prefix'] = $prefix;
        }

        return $numberRanges;
    }

    public function readTotal(MigrationContextInterface $migrationContext): ?TotalStruct
    {
        $this->setConnection($migrationContext);

        $total = (int) $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('s_order_number')
            ->executeQuery()
            ->fetchOne();

        return new TotalStruct(DefaultEntities::NUMBER_RANGE, $total);
    }

    private function fetchNumberRanges(): array
    {
        $query = $this->connection->createQueryBuilder()
            ->select('*')
            ->from('s_order_number');

        $query->executeQuery();

        return $query->fetchAllAssociative();
    }

    private function fetchPrefix(): string
    {
        $prefix = $this->connection->createQueryBuilder()
            ->select('value')
            ->from('s_core_config_elements')
            ->where('name = "backendautoordernumberprefix"')
            ->executeQuery()
            ->fetchOne();

        return $prefix ?: '';
    }
}
