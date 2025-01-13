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
use SwagMigrationAssistant\Migration\Gateway\Reader\ReaderInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

#[Package('fundamentals@after-sales')]
class MediaReader extends AbstractReader implements ReaderInterface
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME
            && $this->getDataSetEntity($migrationContext) === DefaultEntities::MEDIA;
    }

    public function supportsTotal(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME;
    }

    public function read(MigrationContextInterface $migrationContext): array
    {
        $fetchedMedia = $this->fetchData($migrationContext);

        $media = $this->mapData(
            $fetchedMedia,
            [],
            ['asset']
        );

        $resultSet = $this->prepareMedia($media, $migrationContext);

        return $this->cleanupResultSet($resultSet);
    }

    public function readTotal(MigrationContextInterface $migrationContext): ?TotalStruct
    {
        $connection = $this->getConnection($migrationContext);

        $total = (int) $connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('s_media')
            ->executeQuery()
            ->fetchOne();

        return new TotalStruct(DefaultEntities::MEDIA, $total);
    }

    private function fetchData(MigrationContextInterface $migrationContext): array
    {
        $ids = $this->fetchIdentifiers($migrationContext, 's_media', $migrationContext->getOffset(), $migrationContext->getLimit());
        $connection = $this->getConnection($migrationContext);
        $query = $connection->createQueryBuilder();

        $query->from('s_media', 'asset');
        $this->addTableSelection($query, 's_media', 'asset', $migrationContext);

        $query->leftJoin('asset', 's_media_attributes', 'attributes', 'asset.id = attributes.mediaID');
        $this->addTableSelection($query, 's_media_attributes', 'attributes', $migrationContext);

        $query->where('asset.id IN (:ids)');
        $query->setParameter('ids', $ids, ArrayParameterType::STRING);

        $query->addOrderBy('asset.id');

        $query->executeQuery();

        return $query->fetchAllAssociative();
    }

    private function prepareMedia(array $media, MigrationContextInterface $migrationContext): array
    {
        // represents the main language of the migrated shop
        $locale = $this->getDefaultShopLocale($migrationContext);

        foreach ($media as &$mediaData) {
            $mediaData['_locale'] = \str_replace('_', '-', $locale);
        }
        unset($mediaData);

        return $media;
    }
}
