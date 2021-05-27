<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Media;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use GuzzleHttp\Client;
use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\ShopwareApiGateway;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\DocumentDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Media\HttpMediaDownloadService as BaseHttpMediaDownloadService;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6ProfileInterface;

class HttpOrderDocumentService extends BaseHttpMediaDownloadService
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof Shopware6ProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareApiGateway::GATEWAY_NAME
            && $this->getDataSetEntity($migrationContext) === DocumentDataSet::getEntity();
    }

    public function process(MigrationContextInterface $migrationContext, Context $context, array $workload, int $fileChunkByteSize): array
    {
        return $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($migrationContext, $workload, $fileChunkByteSize) {
            return parent::process($migrationContext, $context, $workload, $fileChunkByteSize);
        });
    }

    protected function doMediaDownloadRequests(array $media, array &$mappedWorkload, Client $client): array
    {
        $promises = [];
        foreach ($media as $mediaFile) {
            $uuid = \mb_strtolower($mediaFile['media_id']);
            $additionalData = [];
            $additionalData['file_size'] = $mediaFile['file_size'];
            $additionalData['file_extension'] = $mediaFile['file_extension'];
            $additionalData['uri'] = $mediaFile['uri'];
            $mappedWorkload[$uuid]->setAdditionalData($additionalData);

            $promise = $this->doNormalDownloadRequest($mappedWorkload[$uuid], $client);

            if ($promise !== null) {
                $promises[$uuid] = $promise;
            }
        }

        return $promises;
    }

    protected function getMediaFiles(array $mediaIds, string $runId): array
    {
        $query = $this->dbalConnection->createQueryBuilder();
        $query->select([
            'hex(migrationFile.id) as id',
            'hex(migrationFile.run_id) as run_id',
            'migrationFile.entity',
            'migrationFile.uri',
            'migrationFile.file_name',
            'media.file_extension',
            'migrationFile.file_size',
            'hex(migrationFile.media_id) as media_id',
            'migrationFile.written',
            'migrationFile.processed',
            'migrationFile.process_failure',
        ]);
        $query->from('swag_migration_media_file', 'migrationFile');
        $query->leftJoin('migrationFile', 'media', 'media', 'migrationFile.media_id = media.id');
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
            $media['id'] = \mb_strtolower($media['id']);
            $media['run_id'] = \mb_strtolower($media['run_id']);
            $media['media_id'] = \mb_strtolower($media['media_id']);
        }

        return $result;
    }
}
