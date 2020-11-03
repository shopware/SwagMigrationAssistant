<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

class LanguageReader extends AbstractReader
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME
            && $migrationContext->getDataSet()::getEntity() === DefaultEntities::LANGUAGE;
    }

    public function read(MigrationContextInterface $migrationContext): array
    {
        $this->setConnection($migrationContext);
        $fetchedShopLocaleIds = \array_unique($this->fetchShopLocaleIds());
        $locales = $this->fetchLocales($fetchedShopLocaleIds);

        return $this->appendAssociatedData($locales);
    }

    private function appendAssociatedData(array $locales): array
    {
        $translations = $this->fetchTranslations(\array_keys($locales));

        $defaultLocale = $this->getDefaultShopLocale();

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

    private function fetchShopLocaleIds(): array
    {
        $query = $this->connection->createQueryBuilder();
        $query->from('s_core_shops', 'shop');
        $query->addSelect('shop.locale_id');

        $query = $query->execute();
        if (!($query instanceof ResultStatement)) {
            return [];
        }

        return $query->fetchAll(\PDO::FETCH_COLUMN);
    }

    private function fetchLocales(array $fetchedShopLocaleIds): array
    {
        $query = $this->connection->createQueryBuilder()
            ->addSelect('locale.locale as groupId, locale.id, locale.locale, locale.language')
            ->from('s_core_locales', 'locale')
            ->where('locale.id IN (:localeIds)')
            ->setParameter('localeIds', $fetchedShopLocaleIds, Connection::PARAM_STR_ARRAY)
            ->execute();

        if (!($query instanceof ResultStatement)) {
            return [];
        }

        return $query->fetchAll(\PDO::FETCH_GROUP | \PDO::FETCH_UNIQUE);
    }

    private function fetchTranslations(array $locales): array
    {
        $query = $this->connection->createQueryBuilder()
            ->addSelect('snippet.name as groupId, locale.locale, snippet.value')
            ->from('s_core_snippets', 'snippet')
            ->leftJoin('snippet', 's_core_locales', 'locale', 'snippet.localeID = locale.id')
            ->where('snippet.namespace = "backend/locale/language" AND snippet.name IN (:locales)')
            ->setParameter('locales', $locales, Connection::PARAM_STR_ARRAY)
            ->execute();

        if (!($query instanceof ResultStatement)) {
            return [];
        }

        return $query->fetchAll(\PDO::FETCH_GROUP);
    }
}
