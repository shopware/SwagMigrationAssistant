<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader;

use Shopware\Core\Framework\DataAbstractionLayer\Doctrine\FetchModeHelper;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Gateway\Reader\EnvironmentReaderInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('fundamentals@after-sales')]
class EnvironmentReader extends AbstractReader implements EnvironmentReaderInterface
{
    /**
     * @return array{defaultShopLanguage: string, host: string, additionalData: array<int, mixed>, defaultCurrency: string}
     */
    public function read(MigrationContextInterface $migrationContext): array
    {
        $locale = $this->getDefaultShopLocale($migrationContext);

        return [
            'defaultShopLanguage' => $locale,
            'host' => $this->getHost($migrationContext),
            'additionalData' => $this->getAdditionalData($migrationContext),
            'defaultCurrency' => $this->getDefaultCurrency($migrationContext),
        ];
    }

    protected function getDefaultCurrency(MigrationContextInterface $migrationContext): string
    {
        $connection = $this->getConnection($migrationContext);
        $defaultCurrency = $connection->createQueryBuilder()
            ->select('currency')
            ->from('s_core_currencies')
            ->where('standard = 1')
            ->executeQuery()
            ->fetchOne();

        return $defaultCurrency ?: '';
    }

    private function getHost(MigrationContextInterface $migrationContext): string
    {
        $connection = $this->getConnection($migrationContext);
        $host = $connection->createQueryBuilder()
            ->select('shop.host')
            ->from('s_core_shops', 'shop')
            ->where('shop.default = 1')
            ->andWhere('shop.active = 1')
            ->executeQuery()
            ->fetchOne();

        return $host ?: '';
    }

    /**
     * @return array<int, mixed>
     */
    private function getAdditionalData(MigrationContextInterface $migrationContext): array
    {
        $connection = $this->getConnection($migrationContext);
        $query = $connection->createQueryBuilder();

        $query->from('s_core_shops', 'shop');
        $query->addSelect('shop.id as identifier');
        $this->addTableSelection($query, 's_core_shops', 'shop', $migrationContext);

        $query->leftJoin('shop', 's_core_locales', 'locale', 'shop.locale_id = locale.id');
        $this->addTableSelection($query, 's_core_locales', 'locale', $migrationContext);

        $query->orderBy('shop.main_id');

        $fetchedShops = FetchModeHelper::groupUnique($query->executeQuery()->fetchAllAssociative());

        $shops = $this->mapData($fetchedShops, [], ['shop']);

        foreach ($shops as $key => &$shop) {
            if (!empty($shop['main_id'])) {
                $shops[$shop['main_id']]['children'][] = $shop;
                unset($shops[$key]);
            }
        }

        return \array_values($shops);
    }
}
