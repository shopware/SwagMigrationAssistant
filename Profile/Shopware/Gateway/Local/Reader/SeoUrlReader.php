<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

class SeoUrlReader extends AbstractReader
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME
            && $migrationContext->getDataSet()::getEntity() === DefaultEntities::SEO_URL;
    }

    public function supportsTotal(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME;
    }

    public function read(MigrationContextInterface $migrationContext): array
    {
        $this->setConnection($migrationContext);
        $fetchedSeoUrls = $this->fetchSeoUrls($migrationContext);
        $seoUrls = $this->mapData($fetchedSeoUrls, [], ['url']);
        $seoUrls = $this->extractTypeInformation($seoUrls);

        foreach ($seoUrls as &$seoUrl) {
            $seoUrl['_locale'] = \str_replace('_', '-', $seoUrl['_locale']);
        }

        return $this->cleanupResultSet($seoUrls);
    }

    public function readTotal(MigrationContextInterface $migrationContext): ?TotalStruct
    {
        $this->setConnection($migrationContext);

        $query = $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('s_core_rewrite_urls')
            ->execute();

        $total = 0;
        if ($query instanceof ResultStatement) {
            $total = (int) $query->fetchColumn();
        }

        return new TotalStruct(DefaultEntities::SEO_URL, $total);
    }

    private function fetchSeoUrls(MigrationContextInterface $migrationContext): array
    {
        $ids = $this->fetchIdentifiers('s_core_rewrite_urls', $migrationContext->getOffset(), $migrationContext->getLimit());

        $query = $this->connection->createQueryBuilder();

        $query->from('s_core_rewrite_urls', 'url');
        $this->addTableSelection($query, 's_core_rewrite_urls', 'url');

        $query->leftJoin('url', 's_core_shops', 'shop', 'shop.id = url.subshopID');
        $query->leftJoin('shop', 's_core_locales', 'locale', 'shop.locale_id = locale.id');
        $query->addSelect('locale.locale as _locale');

        $query->where('url.id IN (:ids)');
        $query->setParameter('ids', $ids, Connection::PARAM_STR_ARRAY);

        $query = $query->execute();
        if (!($query instanceof ResultStatement)) {
            return [];
        }

        return $query->fetchAll();
    }

    private function extractTypeInformation(array $seoUrls): array
    {
        foreach ($seoUrls as &$seoUrl) {
            \parse_str($seoUrl['org_path'], $output);
            $seoUrl['type'] = $output['sViewport'];
            if ($output['sViewport'] === 'cat') {
                $seoUrl['typeId'] = $output['sCategory'];
            }
            if ($output['sViewport'] === 'detail') {
                $seoUrl['typeId'] = $output['sArticle'];
            }
        }
        unset($seoUrl);

        return $seoUrls;
    }
}
