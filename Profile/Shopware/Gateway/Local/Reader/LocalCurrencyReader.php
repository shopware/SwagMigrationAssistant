<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader;

use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

class LocalCurrencyReader extends LocalAbstractReader implements LocalReaderInterface
{
    public function supports(string $profileName, DataSet $dataSet): bool
    {
        return $profileName === Shopware55Profile::PROFILE_NAME && $dataSet::getEntity() === DefaultEntities::CURRENCY;
    }

    public function read(MigrationContextInterface $migrationContext, array $params = []): array
    {
        $this->setConnection($migrationContext);

        $currencies = $this->fetchData($migrationContext);

        // represents the main language of the migrated shop
        $locale = $this->getDefaultShopLocale();

        foreach ($currencies as $key => &$currency) {
            $currency['_locale'] = str_replace('_', '-', $locale);
        }

        $currencies = $this->mapData($currencies, [], ['currency']);

        return $this->cleanupResultSet($currencies);
    }

    private function fetchData(MigrationContextInterface $migrationContext): array
    {
        $query = $this->connection->createQueryBuilder();
        $query->from('s_core_currencies', 'currency');
        $this->addTableSelection($query, 's_core_currencies', 'currency');

        $query->addOrderBy('standard', 'DESC');
        $query->setFirstResult($migrationContext->getOffset());
        $query->setMaxResults($migrationContext->getLimit());

        return $query->execute()->fetchAll();
    }
}
