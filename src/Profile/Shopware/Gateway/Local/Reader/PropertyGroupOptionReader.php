<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

#[Package('services-settings')]
class PropertyGroupOptionReader extends AbstractReader
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME
            && $this->getDataSetEntity($migrationContext) === DefaultEntities::PROPERTY_GROUP_OPTION;
    }

    public function supportsTotal(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME;
    }

    public function read(MigrationContextInterface $migrationContext): array
    {
        $this->setConnection($migrationContext);
        $fetchedConfiguratorOptions = $this->fetchData($migrationContext);
        $options = $this->mapData($fetchedConfiguratorOptions, [], ['property']);
        $locale = $this->getDefaultShopLocale();

        foreach ($options as &$option) {
            $option['_locale'] = \str_replace('_', '-', $locale);
        }
        unset($option);

        return $this->cleanupResultSet($options);
    }

    public function readTotal(MigrationContextInterface $migrationContext): ?TotalStruct
    {
        $this->setConnection($migrationContext);

        $sql = <<<SQL
SELECT
    COUNT(*)
FROM
    (
        SELECT
            'property' AS "property.type",
            filter.id AS "property.id"
        FROM s_filter_values AS filter

        UNION

        SELECT
            'option' AS "property.type",
            opt.id AS "property.id"
        FROM s_article_configurator_options AS opt
    ) AS result
SQL;
        $statement = $this->connection->prepare($sql);
        $result = $statement->executeQuery();
        $total = (int) $result->fetchOne();

        return new TotalStruct(DefaultEntities::PROPERTY_GROUP_OPTION, $total);
    }

    private function fetchData(MigrationContextInterface $migrationContext): array
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
        $statement->bindValue('limit', $migrationContext->getLimit(), \PDO::PARAM_INT);
        $statement->bindValue('offset', $migrationContext->getOffset(), \PDO::PARAM_INT);

        $result = $statement->executeQuery();

        return $result->fetchAllAssociative();
    }
}
