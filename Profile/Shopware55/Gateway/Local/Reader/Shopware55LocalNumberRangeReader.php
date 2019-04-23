<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader;

class Shopware55LocalNumberRangeReader extends Shopware55LocalAbstractReader
{
    public function read(): array
    {
        $numberRanges = $this->fetchNumberRanges();
        $prefix = unserialize($this->fetchPrefix(), ['allowedClasses' => false]);

        if (!$prefix) {
            $prefix = '';
        }

        $locale = $this->getDefaultShopLocale();

        foreach ($numberRanges as &$numberRange) {
            $numberRange['_locale'] = $locale;
            $numberRange['prefix'] = $prefix;
        }

        return $numberRanges;
    }

    private function fetchNumberRanges(): array
    {
        return $this->connection->createQueryBuilder()
            ->select('*')
            ->from('s_order_number')
            ->execute()
            ->fetchAll();
    }

    private function fetchPrefix(): string
    {
        return $this->connection->createQueryBuilder()
            ->select('value')
            ->from('s_core_config_elements')
            ->where('name = "backendautoordernumberprefix"')
            ->execute()
            ->fetch(\PDO::FETCH_COLUMN);
    }
}
