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
class SeoUrlReader extends AbstractReader
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME
            && $this->getDataSetEntity($migrationContext) === DefaultEntities::SEO_URL;
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

        $total = (int) $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('s_core_rewrite_urls')
            ->executeQuery()
            ->fetchOne();

        return new TotalStruct(DefaultEntities::SEO_URL, $total);
    }

    /**
     * @return array<int, array<string, string>>
     */
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
        $query->setParameter('ids', $ids, ArrayParameterType::STRING);

        $query->executeQuery();

        if ($this->isRouterToLower()) {
            return $this->prepareSeoUrl($query->fetchAllAssociative());
        }

        return $query->fetchAllAssociative();
    }

    /**
     * @param array<int, array<string, string>> $seoUrls #
     *
     * @return array<int, array<string, string>>
     */
    private function prepareSeoUrl(array $seoUrls): array
    {
        foreach ($seoUrls as &$seoUrl) {
            $seoUrl['url.path'] = \mb_strtolower($seoUrl['url.path']);
        }
        unset($seoUrl);

        return $seoUrls;
    }

    /**
     * @return bool
     */
    private function isRouterToLower()
    {
        $useUrlToLower = $this->connection->createQueryBuilder()
            ->select(['cv.value'])
            ->from('s_core_config_values', 'cv')
            ->innerJoin('cv', 's_core_config_elements', 'ce', 'cv.element_id = ce.id')
            ->where('ce.name = "routerToLower"')
            ->executeQuery()
            ->fetchOne();

        if (!\is_string($useUrlToLower)) {
            return true;
        }

        return (bool) \unserialize($useUrlToLower);
    }

    /**
     * @param array<int, array<string, string>> $seoUrls
     *
     * @return array<int, array<string, string>>
     */
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
