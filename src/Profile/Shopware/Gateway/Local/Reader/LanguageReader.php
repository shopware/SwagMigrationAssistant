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
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Gateway\Reader\ReaderInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

#[Package('fundamentals@after-sales')]
class LanguageReader extends AbstractReader implements ReaderInterface
{
    public function supportsTotal(MigrationContextInterface $migrationContext): bool
    {
        return false;
    }

    public function readTotal(MigrationContextInterface $migrationContext): ?TotalStruct
    {
        return null;
    }

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME
            && $this->getDataSetEntity($migrationContext) === DefaultEntities::LANGUAGE;
    }

    public function read(MigrationContextInterface $migrationContext): array
    {
        $fetchedShopLocaleIds = \array_unique($this->fetchShopLocaleIds($migrationContext));
        $locales = $this->fetchLocales($fetchedShopLocaleIds, $migrationContext);

        return $this->appendAssociatedData($locales, $migrationContext);
    }

    private function appendAssociatedData(array $locales, MigrationContextInterface $migrationContext): array
    {
        $translations = $this->fetchTranslations(\array_keys($locales), $migrationContext);

        $defaultLocale = $this->getDefaultShopLocale($migrationContext);

        foreach ($locales as $key => &$locale) {
            if (isset($translations[$key])) {
                $locale['translations'] = $translations[$key];
            }
            $locale['locale'] = \str_replace('_', '-', $locale['locale']);
            // locale of the main language in which the dataset is probably created
            $locale['_locale'] = \str_replace('_', '-', $defaultLocale);
        }

        return \array_values($locales);
    }

    private function fetchShopLocaleIds(MigrationContextInterface $migrationContext): array
    {
        $connection = $this->getConnection($migrationContext);
        $query = $connection->createQueryBuilder();
        $query->from('s_core_shops', 'shop');
        $query->addSelect('shop.locale_id');

        $query = $query->executeQuery();

        return $query->fetchFirstColumn();
    }

    private function fetchLocales(array $fetchedShopLocaleIds, MigrationContextInterface $migrationContext): array
    {
        $connection = $this->getConnection($migrationContext);
        $query = $connection->createQueryBuilder()
            ->addSelect('locale.locale as groupId, locale.id, locale.locale, locale.language')
            ->from('s_core_locales', 'locale')
            ->where('locale.id IN (:localeIds)')
            ->setParameter('localeIds', $fetchedShopLocaleIds, ArrayParameterType::STRING)
            ->executeQuery();

        $rows = $query->fetchAllAssociative();

        return FetchModeHelper::groupUnique($rows);
    }

    private function fetchTranslations(array $locales, MigrationContextInterface $migrationContext): array
    {
        $connection = $this->getConnection($migrationContext);
        $query = $connection->createQueryBuilder()
            ->addSelect('snippet.name as groupId, locale.locale, snippet.value')
            ->from('s_core_snippets', 'snippet')
            ->leftJoin('snippet', 's_core_locales', 'locale', 'snippet.localeID = locale.id')
            ->where('snippet.namespace = "backend/locale/language" AND snippet.name IN (:locales)')
            ->setParameter('locales', $locales, ArrayParameterType::STRING)
            ->executeQuery();

        $result = $query->fetchAllAssociative();

        return FetchModeHelper::group($result);
    }
}
