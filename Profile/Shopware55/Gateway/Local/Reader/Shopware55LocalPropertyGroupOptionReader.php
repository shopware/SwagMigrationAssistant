<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware55\Gateway\Local\Reader;

class Shopware55LocalPropertyGroupOptionReader extends Shopware55LocalAbstractReader
{
    public function read(): array
    {
        $fetchedConfiguratorOptions = $this->fetchData();
        $options = $this->mapData($fetchedConfiguratorOptions, [], ['property']);
        $locale = $this->getDefaultShopLocale();

        foreach ($options as $key => &$option) {
            $option['_locale'] = str_replace('_', '-', $locale);
        }
        unset($option);

        return $this->cleanupResultSet($options);
    }

    private function fetchData(): array
    {
        $sql = <<<SQL
SELECT
           'property' AS "property.type",
           filter.id AS "property.id",
           filter.value AS "property.name",
           filter.position AS "property.position",
           filterOpt.id AS "property_group.id",
           filterOpt.name AS "property_group.name",
           filterOpt.name AS "property_group.description",
           media.id AS "property_media.id",
           media.name AS "property_media.name",
           media.description AS "property_media.description",
           media.path AS "property_media.path",
           media.file_size AS "property_media.file_size",
           media.albumID AS "property_media.albumID",
           mediaAttr.id AS "property_media.attribute"
    FROM s_filter_values AS filter
           INNER JOIN s_filter_options AS filterOpt ON filterOpt.id = filter.optionID
           LEFT JOIN s_media AS media ON media.id = filter.media_id
           LEFT JOIN s_media_attributes AS mediaAttr ON mediaAttr.mediaID = media.id

UNION

(
    SELECT
           'option' AS "property.type",
           opt.id AS "property.id",
           opt.name AS "property.name",
           opt.position AS "property.position",
           optGroup.id AS "property_group.id",
           optGroup.name AS "property_group.name",
           optGroup.description AS "property_group.description",
           media.id AS "property_media.id",
           media.name AS "property_media.name",
           media.description AS "property_media.description",
           media.path AS "property_media.path",
           media.file_size AS "property_media.file_size",
           media.albumID AS "property_media.albumID",
           mediaAttr.id AS "property_media.attribute"
    FROM s_article_configurator_options AS opt
          INNER JOIN s_article_configurator_groups AS optGroup ON optGroup.id = opt.group_id
          LEFT JOIN s_media AS media ON media.id = opt.media_id
          LEFT JOIN s_media_attributes AS mediaAttr ON mediaAttr.mediaID = media.id
)
ORDER BY "property.type", "property.id" LIMIT :limit OFFSET :offset
SQL;

        $statement = $this->connection->prepare($sql);
        $statement->bindValue('limit', $this->migrationContext->getLimit(), \PDO::PARAM_INT);
        $statement->bindValue('offset', $this->migrationContext->getOffset(), \PDO::PARAM_INT);
        $statement->setFetchMode(\PDO::FETCH_ASSOC);
        $statement->execute();

        return $statement->fetchAll();
    }
}
