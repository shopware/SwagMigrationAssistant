<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader;

use Doctrine\DBAL\ParameterType;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Gateway\Reader\ReaderInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

#[Package('fundamentals@after-sales')]
class CrossSellingReader extends AbstractReader implements ReaderInterface
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME
            && $this->getDataSetEntity($migrationContext) === DefaultEntities::CROSS_SELLING;
    }

    public function supportsTotal(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME;
    }

    public function read(MigrationContextInterface $migrationContext): array
    {
        $fetchedCrossSelling = $this->fetchData($migrationContext);
        $this->enrichWithPositionData($fetchedCrossSelling, $migrationContext->getOffset());

        return $this->cleanupResultSet($fetchedCrossSelling);
    }

    public function readTotal(MigrationContextInterface $migrationContext): TotalStruct
    {
        $connection = $this->getConnection($migrationContext);

        $sql = <<<SQL
SELECT
    COUNT(*)
FROM
    (
        SELECT 'accessory' AS type, accessory.* FROM s_articles_relationships AS accessory
        UNION
        SELECT 'similar' AS type, similar.* FROM s_articles_similar AS similar
    ) AS result
SQL;

        $total = (int) $connection->executeQuery($sql)->fetchOne();

        return new TotalStruct(DefaultEntities::CROSS_SELLING, $total);
    }

    protected function fetchData(MigrationContextInterface $migrationContext): array
    {
        $connection = $this->getConnection($migrationContext);

        $sql = <<<SQL
SELECT * FROM (
    SELECT
           :accessory AS type,
           acce.*
    FROM s_articles_relationships AS acce

    UNION

    SELECT
           :similar AS type,
           similar.*
    FROM s_articles_similar AS similar
) cross_selling
ORDER BY cross_selling.type, cross_selling.articleID LIMIT :limit OFFSET :offset
SQL;

        $statement = $connection->prepare($sql);
        $statement->bindValue('accessory', DefaultEntities::CROSS_SELLING_ACCESSORY, ParameterType::STRING);
        $statement->bindValue('similar', DefaultEntities::CROSS_SELLING_SIMILAR, ParameterType::STRING);
        $statement->bindValue('limit', $migrationContext->getLimit(), ParameterType::INTEGER);
        $statement->bindValue('offset', $migrationContext->getOffset(), ParameterType::INTEGER);

        $result = $statement->executeQuery();

        return $result->fetchAllAssociative();
    }

    private function enrichWithPositionData(array &$fetchedCrossSelling, int $offset): void
    {
        foreach ($fetchedCrossSelling as &$item) {
            $item['position'] = $offset++;
        }
    }
}
