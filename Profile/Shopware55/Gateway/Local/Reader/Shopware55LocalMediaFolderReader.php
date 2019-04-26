<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader;

class Shopware55LocalMediaFolderReader extends Shopware55LocalAbstractReader
{
    public function read(): array
    {
        $fetchedAlbums = $this->fetchAlbums();

        $albums = $this->mapData(
            $fetchedAlbums, [], ['album']
        );

        $albums = $this->prepareMediaAlbums($albums);

        return $this->cleanupResultSet($albums);
    }

    private function fetchAlbums(): array
    {
        $query = $this->connection->createQueryBuilder();

        $query->from('s_media_album', 'album');
        $this->addTableSelection($query, 's_media_album', 'album');

        $query->leftJoin('album', 's_media_album_settings', 'setting', 'setting.albumID = album.id');
        $this->addTableSelection($query, 's_media_album_settings', 'setting');

        $query->orderBy('parentID');

        return $query->execute()->fetchAll();
    }

    private function prepareMediaAlbums(array $mediaAlbums): array
    {
        // represents the main language of the migrated shop
        $locale = $this->getDefaultShopLocale();

        $returnAlbums = [];
        foreach ($mediaAlbums as $key => $mediaAlbum) {
            if ($mediaAlbum['parentID'] !== null) {
                continue;
            }

            $mediaAlbum['_locale'] = str_replace('_', '-', $locale);
            $returnAlbums[] = $mediaAlbum;
            unset($mediaAlbums[$key]);

            $childAlbums = $this->getChildAlbums($mediaAlbums, $mediaAlbum['id'], $locale);
            $returnAlbums = array_merge($returnAlbums, $childAlbums);
        }
        unset($mediaAlbum);

        return $returnAlbums;
    }

    private function getChildAlbums(array &$mediaAlbums, $id, $locale): array
    {
        $returnAlbums = [];
        foreach ($mediaAlbums as $key => $mediaAlbum) {
            if ($mediaAlbum['parentID'] !== $id) {
                continue;
            }

            $mediaAlbum['_locale'] = str_replace('_', '-', $locale);
            $returnAlbums[] = $mediaAlbum;
            unset($mediaAlbums[$key]);

            $childAlbums = $this->getChildAlbums($mediaAlbums, $mediaAlbum['id'], $locale);
            $returnAlbums = array_merge($returnAlbums, $childAlbums);
        }

        return $returnAlbums;
    }
}
