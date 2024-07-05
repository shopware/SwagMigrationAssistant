<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Media\Processor;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Media\SwagMigrationMediaFileCollection;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

/**
 * @phpstan-type Media list<array{'id': string, 'run_id': string, 'media_id': string, 'uri': string, 'file_size': string, 'file_name': string}>
 */
#[Package('services-settings')]
abstract class BaseMediaService
{
    /**
     * @param EntityRepository<SwagMigrationMediaFileCollection> $mediaFileRepository
     */
    public function __construct(
        protected readonly Connection $dbalConnection,
        protected readonly EntityRepository $mediaFileRepository,
    ) {
    }

    protected function getDataSetEntity(MigrationContextInterface $migrationContext): ?string
    {
        $dataSet = $migrationContext->getDataSet();
        if ($dataSet === null) {
            return null;
        }

        return $dataSet::getEntity();
    }

    /**
     * @param array<int, string> $mediaIds
     *
     * @return Media
     */
    protected function getMediaFiles(array $mediaIds, string $runId): array
    {
        $binaryMediaIds = [];
        foreach ($mediaIds as $mediaId) {
            $binaryMediaIds[] = Uuid::fromHexToBytes($mediaId);
        }

        $query = $this->dbalConnection->createQueryBuilder();
        $query->select('*');
        $query->from('swag_migration_media_file');
        $query->where('run_id = :runId');
        $query->andWhere('media_id IN (:ids)');
        $query->andWhere('written = 1');
        $query->setParameter('ids', $binaryMediaIds, ArrayParameterType::STRING);
        $query->setParameter('runId', Uuid::fromHexToBytes($runId));

        $query->executeQuery();

        /** @var Media $result */
        $result = $query->fetchAllAssociative();
        foreach ($result as &$media) {
            $media['id'] = Uuid::fromBytesToHex($media['id']);
            $media['run_id'] = Uuid::fromBytesToHex($media['run_id']);
            $media['media_id'] = Uuid::fromBytesToHex($media['media_id']);
        }

        return $result;
    }

    /**
     * @param list<string> $finishedUuids
     * @param list<string> $failureUuids
     */
    protected function setProcessedFlag(string $runId, Context $context, array $finishedUuids, array $failureUuids): void
    {
        $mediaFiles = $this->getMediaFiles($finishedUuids, $runId);

        $mediaEntitiesToUpdate = [];
        foreach ($mediaFiles as $mediaFile) {
            $mediaFileId = $mediaFile['id'];

            if (!\in_array($mediaFileId, $failureUuids, true)) {
                $mediaEntitiesToUpdate[] = [
                    'id' => $mediaFileId,
                    'processed' => true,
                ];
            }
        }

        if (!empty($failureUuids)) {
            $mediaFiles = $this->getMediaFiles($failureUuids, $runId);

            foreach ($mediaFiles as $mediaFile) {
                $mediaEntitiesToUpdate[] = [
                    'id' => $mediaFile['id'],
                    'processFailure' => true,
                ];
            }
        }

        if (empty($mediaEntitiesToUpdate)) {
            return;
        }

        $this->mediaFileRepository->update($mediaEntitiesToUpdate, $context);
    }
}
