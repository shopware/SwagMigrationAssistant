<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration;

use Doctrine\DBAL\Connection;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Psr7\Response;
use PDO;
use Shopware\Core\Content\Media\Exception\IllegalMimeTypeException;
use Shopware\Core\Content\Media\Exception\UploadException;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Media\File\MediaFile;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\RepositoryInterface;
use Shopware\Core\Framework\ORM\Search\Criteria;
use Shopware\Core\Framework\ORM\Search\Query\TermsQuery;
use SwagMigrationNext\Exception\NoFileSystemPermissions;
use SwagMigrationNext\Migration\Mapping\SwagMigrationMappingStruct;

class HttpAssetDownloadService implements HttpAssetDownloadServiceInterface
{
    /**
     * @var RepositoryInterface
     */
    private $migrationMappingRepository;
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var FileSaver
     */
    private $fileSaver;

    public function __construct(
        RepositoryInterface $migrationMappingRepository,
        Connection $connection,
        FileSaver $fileSaver
    ) {
        $this->migrationMappingRepository = $migrationMappingRepository;
        $this->connection = $connection;
        $this->fileSaver = $fileSaver;
    }

    public function fetchMediaUuids(Context $context, string $profile, int $offset, int $limit): array
    {
        // TODO: Normalize additional data to use the orm system instead of JSON_EXTRACT (performance)
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->addSelect('LOWER(HEX(entity_uuid))')
            ->from('swag_migration_mapping', 'mapping')
            ->where('entity = :entity')
            ->setParameter('entity', MediaDefinition::getEntityName())
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy('CONVERT(JSON_EXTRACT(`additional_data`, \'$.file_size\'), UNSIGNED INTEGER)');

        return $queryBuilder->execute()->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * @param Context $context
     * @param array   $workload          [{ "uuid": "04ed51ccbb2341bc9b352d78e64213fb", "currentOffset": 0, "state": "inProgress" }]
     * @param int     $fileChunkByteSize
     *
     * @throws IllegalMimeTypeException
     * @throws NoFileSystemPermissions
     * @throws UploadException
     *
     * @return array
     */
    public function downloadAssets(Context $context, array $workload, int $fileChunkByteSize): array
    {
        if (!is_dir('_temp') && !mkdir('_temp') && !is_dir('_temp')) {
            throw new NoFileSystemPermissions();
        }

        //Map workload with uuids as keys
        $mappedWorkload = [];
        foreach ($workload as $work) {
            $mappedWorkload[$work['uuid']] = $work;
        }

        //Fetch assets from database
        $client = new Client([
            'verify' => false,
        ]);
        $criteria = new Criteria();
        $criteria->addFilter(new TermsQuery('entityUuid', array_keys($mappedWorkload)));
        $entitySearchResult = $this->migrationMappingRepository->search($criteria, $context);
        /** @var SwagMigrationMappingStruct[] $assets */
        $assets = $entitySearchResult->getElements();

        //Do download requests and store the promises
        $promises = $this->doAssetDownloadRequests($assets, $fileChunkByteSize, $mappedWorkload, $client);

        // Wait for the requests to complete, even if some of them fail
        /** @var array $results */
        $results = Promise\settle($promises)->wait();

        //handle responses
        foreach ($results as $uuid => $result) {
            /** @var Response $response */
            $state = $result['state'];
            $additionalData = $mappedWorkload[$uuid]['additionalData'];

            $oldWorkloadSearchResult = array_filter(
                $workload,
                function ($work) use ($uuid) {
                    return $work['uuid'] === $uuid;
                }
            );
            $oldWorkload = array_pop($oldWorkloadSearchResult);

            if ($state !== 'fulfilled') {
                $mappedWorkload[$uuid] = $oldWorkload;
                $mappedWorkload[$uuid]['additionalData'] = $additionalData;

                if (isset($mappedWorkload[$uuid]['errorCount'])) {
                    ++$mappedWorkload[$uuid]['errorCount'];
                } else {
                    $mappedWorkload[$uuid]['errorCount'] = 1;
                }

                continue;
            }

            $response = $result['value'];
            $fileExtension = pathinfo($additionalData['uri'], PATHINFO_EXTENSION);
            $filePath = sprintf('_temp/%s.%s', $uuid, $fileExtension);

            $fileHandle = fopen($filePath, 'ab');
            fwrite($fileHandle, $response->getBody()->getContents());
            fclose($fileHandle);

            if ($mappedWorkload[$uuid]['state'] === 'finished') {
                //move asset to media system
                $this->persistFileToMedia($filePath, $uuid, (int) $additionalData['file_size'], $fileExtension, $context);
                unlink($filePath);
            }

            if (isset($mappedWorkload[$uuid]['errorCount'], $oldWorkload['errorCount']) &&
                $oldWorkload['errorCount'] === $mappedWorkload[$uuid]['errorCount']) {
                unset($mappedWorkload[$uuid]['errorCount']);
            }
        }

        return array_values($mappedWorkload);
    }

    /**
     * Start all the download requests for the assets in parallel (async) and return the promise array.
     *
     * @param SwagMigrationMappingStruct[] $assets
     */
    private function doAssetDownloadRequests(array $assets, int $fileChunkByteSize, array &$mappedWorkload, Client $client): array
    {
        $promises = [];
        foreach ($assets as $asset) {
            $uuid = strtolower($asset->getEntityUuid());
            $additionalData = $asset->getAdditionalData();
            $mappedWorkload[$uuid]['additionalData'] = $additionalData;

            if ($additionalData['file_size'] <= $fileChunkByteSize) {
                $promise = $this->doNormalDownloadRequest($mappedWorkload[$uuid], $client);
            } else {
                $promise = $this->doChunkDownloadRequest($fileChunkByteSize, $mappedWorkload[$uuid], $client);
            }

            if ($promise !== null) {
                $promises[$uuid] = $promise;
            }
        }

        return $promises;
    }

    /**
     * Persists the file to the flysystem.
     *
     * @throws IllegalMimeTypeException
     * @throws UploadException
     */
    private function persistFileToMedia(string $filePath, string $uuid, int $fileSize, string $fileExtension, Context $context): void
    {
        $mimeType = mime_content_type($filePath);
        $mediaFile = new MediaFile($filePath, $mimeType, $fileExtension, $fileSize);
        $this->fileSaver->persistFileToMedia($mediaFile, $uuid, $context);
    }

    private function doNormalDownloadRequest(array &$workload, Client $client): ?Promise\PromiseInterface
    {
        $additionalData = $workload['additionalData'];

        try {
            $promise = $client->getAsync(
                $additionalData['uri'],
                [
                    'query' => ['alt' => 'media'],
                ]
            );

            $workload['currentOffset'] = $additionalData['file_size'];
            $workload['state'] = 'finished';
        } catch (Exception $exception) {
            $promise = null;
            if (isset($workload['errorCount'])) {
                ++$workload['errorCount'];
            } else {
                $workload['errorCount'] = 1;
            }
        }

        return $promise;
    }

    private function doChunkDownloadRequest(int $fileChunkByteSize, array &$workload, Client $client): ?Promise\PromiseInterface
    {
        $additionalData = $workload['additionalData'];
        $chunkStart = (int) $workload['currentOffset'];
        $chunkEnd = $chunkStart + $fileChunkByteSize;

        try {
            $promise = $client->getAsync(
                $additionalData['uri'],
                [
                    'query' => ['alt' => 'media'],
                    'headers' => [
                        'Range' => sprintf('bytes=%s-%s', $chunkStart, $chunkEnd),
                    ],
                ]
            );

            //check if chunk is big enough to finish the download of that asset
            if ($chunkEnd < $additionalData['file_size']) {
                $workload['state'] = 'inProgress';
                $workload['currentOffset'] = $chunkEnd + 1;
            } else {
                $workload['state'] = 'finished';
                $workload['currentOffset'] = $additionalData['file_size'];
            }
        } catch (Exception $exception) {
            $promise = null;
            if (isset($workload['errorCount'])) {
                ++$workload['errorCount'];
            } else {
                $workload['errorCount'] = 1;
            }
        }

        return $promise;
    }
}
