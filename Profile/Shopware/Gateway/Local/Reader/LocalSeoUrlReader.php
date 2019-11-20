<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader;

use Doctrine\DBAL\Connection;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Gateway\Reader\ReaderInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

class LocalSeoUrlReader extends LocalAbstractReader implements ReaderInterface
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getDataSet()::getEntity() === DefaultEntities::SEO_URL;
    }

    public function supportsTotal(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME;
    }

    public function read(MigrationContextInterface $migrationContext, array $params = []): array
    {
        $this->setConnection($migrationContext);
        $fetchedSeoUrls = $this->fetchSeoUrls($migrationContext);
        $seoUrls = $this->mapData($fetchedSeoUrls, [], ['url']);
        $seoUrls = $this->extractTypeInformation($seoUrls);

        foreach ($seoUrls as &$seoUrl) {
            $seoUrl['_locale'] = str_replace('_', '-', $seoUrl['_locale']);
        }

        return $this->cleanupResultSet($seoUrls);
    }

    public function readTotal(MigrationContextInterface $migrationContext): ?TotalStruct
    {
        $this->setConnection($migrationContext);

        $total = (int) $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('s_core_rewrite_urls')
            ->execute()
            ->fetchColumn();

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

        return $query->execute()->fetchAll();
    }

    private function extractTypeInformation(array $seoUrls)
    {
        foreach ($seoUrls as &$seoUrl) {
            parse_str($seoUrl['org_path'], $output);
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
