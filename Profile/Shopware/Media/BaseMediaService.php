<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Media;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;

abstract class BaseMediaService
{
    /**
     * @var Connection
     */
    protected $dbalConnection;

    public function __construct(Connection $dbalConnection)
    {
        $this->dbalConnection = $dbalConnection;
    }

    protected function getMediaFiles(array $mediaIds, string $runId): array
    {
        $query = $this->dbalConnection->createQueryBuilder();
        $query->select('hex(id) as id, hex(run_id) as run_id, entity, uri, file_name, file_size, hex(media_id) as media_id, written, processed, process_failure');
        $query->from('swag_migration_media_file');
        $query->where('HEX(run_id) = :runId');
        $query->andWhere('HEX(media_id) IN (:ids)');
        $query->setParameter('ids', $mediaIds, Connection::PARAM_STR_ARRAY);
        $query->setParameter('runId', $runId);

        $query = $query->execute();
        if (!($query instanceof ResultStatement)) {
            return [];
        }

        $result = $query->fetchAll();
        foreach ($result as &$media) {
            $media['id'] = mb_strtolower($media['id']);
            $media['run_id'] = mb_strtolower($media['run_id']);
            $media['media_id'] = mb_strtolower($media['media_id']);
        }

        return $result;
    }
}
