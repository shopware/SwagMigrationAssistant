<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader;

use Doctrine\DBAL\Connection;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

class LocalMediaReader extends LocalAbstractReader implements LocalReaderInterface
{
    public function supports(string $profileName, DataSet $dataSet): bool
    {
        return $profileName === Shopware55Profile::PROFILE_NAME && $dataSet::getEntity() === DefaultEntities::MEDIA;
    }

    public function read(MigrationContextInterface $migrationContext, array $params = []): array
    {
        $this->setConnection($migrationContext);
        $fetchedMedia = $this->fetchData($migrationContext);

        $media = $this->mapData(
            $fetchedMedia, [], ['asset']
        );

        $resultSet = $this->prepareMedia($media);

        return $this->cleanupResultSet($resultSet);
    }

    private function fetchData(MigrationContextInterface $migrationContext): array
    {
        $ids = $this->fetchIdentifiers('s_media', $migrationContext->getOffset(), $migrationContext->getLimit());
        $query = $this->connection->createQueryBuilder();

        $query->from('s_media', 'asset');
        $this->addTableSelection($query, 's_media', 'asset');

        $query->leftJoin('asset', 's_media_attributes', 'attributes', 'asset.id = attributes.mediaID');
        $this->addTableSelection($query, 's_media_attributes', 'attributes');

        $query->where('asset.id IN (:ids)');
        $query->setParameter('ids', $ids, Connection::PARAM_STR_ARRAY);

        $query->addOrderBy('asset.id');

        return $query->execute()->fetchAll();
    }

    private function prepareMedia(array $media): array
    {
        // represents the main language of the migrated shop
        $locale = $this->getDefaultShopLocale();

        foreach ($media as &$mediaData) {
            $mediaData['_locale'] = str_replace('_', '-', $locale);
        }
        unset($mediaData);

        return $media;
    }
}
