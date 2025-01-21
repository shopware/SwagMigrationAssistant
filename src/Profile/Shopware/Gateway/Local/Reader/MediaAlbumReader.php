<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Gateway\Reader\ReaderInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

#[Package('fundamentals@after-sales')]
class MediaAlbumReader extends AbstractReader implements ReaderInterface
{
    public function supportsTotal(MigrationContextInterface $migrationContext): bool
    {
        return false;
    }

    public function readTotal(MigrationContextInterface $migrationContext): ?TotalStruct
    {
        return null;
    }

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME
            && $this->getDataSetEntity($migrationContext) === DefaultEntities::MEDIA_FOLDER;
    }

    public function read(MigrationContextInterface $migrationContext): array
    {
        $fetchedAlbums = $this->fetchAlbums($migrationContext);

        $albums = $this->mapData(
            $fetchedAlbums,
            [],
            ['album']
        );

        $albums = $this->prepareMediaAlbums($albums, $migrationContext);

        return $this->cleanupResultSet($albums);
    }

    private function fetchAlbums(MigrationContextInterface $migrationContext): array
    {
        $connection = $this->getConnection($migrationContext);
        $query = $connection->createQueryBuilder();

        $query->from('s_media_album', 'album');
        $this->addTableSelection($query, 's_media_album', 'album', $migrationContext);

        $query->leftJoin('album', 's_media_album_settings', 'setting', 'setting.albumID = album.id');
        $this->addTableSelection($query, 's_media_album_settings', 'setting', $migrationContext);

        $query->orderBy('parentID');

        $query = $query->executeQuery();

        return $query->fetchAllAssociative();
    }

    private function prepareMediaAlbums(array $mediaAlbums, MigrationContextInterface $migrationContext): array
    {
        // represents the main language of the migrated shop
        $locale = $this->getDefaultShopLocale($migrationContext);

        $albums = [];
        foreach ($mediaAlbums as $key => $mediaAlbum) {
            if ($mediaAlbum['parentID'] !== null) {
                continue;
            }

            $mediaAlbum['_locale'] = $locale;
            $albums[] = [$mediaAlbum];
            unset($mediaAlbums[$key]);

            $childAlbums = $this->getChildAlbums($mediaAlbums, $mediaAlbum['id'], $locale);

            if (!empty($childAlbums)) {
                $albums[] = $childAlbums;
            }
        }
        unset($mediaAlbum);

        if (empty($albums)) {
            return $albums;
        }

        return \array_merge(...$albums);
    }

    private function getChildAlbums(array &$mediaAlbums, string $id, string $locale): array
    {
        $albums = [];
        foreach ($mediaAlbums as $key => $mediaAlbum) {
            if ($mediaAlbum['parentID'] !== $id) {
                continue;
            }

            $mediaAlbum['_locale'] = $locale;
            $albums[] = [$mediaAlbum];
            unset($mediaAlbums[$key]);

            $childAlbums = $this->getChildAlbums($mediaAlbums, $mediaAlbum['id'], $locale);

            if (!empty($childAlbums)) {
                $albums[] = $childAlbums;
            }
        }

        if (empty($albums)) {
            return $albums;
        }

        return \array_merge(...$albums);
    }
}
