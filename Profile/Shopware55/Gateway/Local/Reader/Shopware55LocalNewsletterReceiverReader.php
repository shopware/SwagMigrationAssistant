<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader;

use Doctrine\DBAL\Connection;

class Shopware55LocalNewsletterReceiverReader extends Shopware55LocalAbstractReader
{
    public function read(): array
    {
        $data = $this->fetchData();

        $locale = $this->getDefaultShopLocale();
        foreach ($data as &$item) {
            $item['_locale'] = $locale;
        }

        return $this->cleanupResultSet($data);
    }

    private function fetchData(): array
    {
        $ids = $this->fetchIdentifiers('s_campaigns_maildata', $this->migrationContext->getOffset(), $this->migrationContext->getLimit());
        $query = $this->connection->createQueryBuilder();

        $query->from('s_campaigns_maildata', 'newsletter');

        $query->select('*');
        $query->where('newsletter.id IN (:ids)');
        $query->setParameter('ids', $ids, Connection::PARAM_STR_ARRAY);
        $query->addOrderBy('newsletter.id');

        return $query->execute()->fetchAll();
    }
}
