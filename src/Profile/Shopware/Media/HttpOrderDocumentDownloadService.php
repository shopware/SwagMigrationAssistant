<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Media;

use Doctrine\DBAL\Connection;
use GuzzleHttp\Promise;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Gateway\HttpClientInterface;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Media\Processor\HttpDownloadServiceBase;
use SwagMigrationAssistant\Migration\Media\SwagMigrationMediaFileCollection;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\OrderDocumentDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\ShopwareApiGateway;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactoryInterface;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

#[Package('services-settings')]
class HttpOrderDocumentDownloadService extends HttpDownloadServiceBase
{
    private const DOCUMENTS_URI = 'SwagMigrationOrderDocuments/';

    /**
     * @param EntityRepository<SwagMigrationMediaFileCollection> $mediaFileRepo
     */
    public function __construct(
        Connection $dbalConnection,
        EntityRepository $mediaFileRepo,
        FileSaver $fileSaver,
        LoggingServiceInterface $loggingService,
        private readonly ConnectionFactoryInterface $connectionFactory,
    ) {
        parent::__construct(
            $dbalConnection,
            $mediaFileRepo,
            $fileSaver,
            $loggingService,
        );
    }

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareApiGateway::GATEWAY_NAME
            && $this->getDataSetEntity($migrationContext) === OrderDocumentDataSet::getEntity();
    }

    protected function getMediaEntity(): string
    {
        return DefaultEntities::ORDER_DOCUMENT;
    }

    protected function getHttpClient(MigrationContextInterface $migrationContext): ?HttpClientInterface
    {
        return $this->connectionFactory->createApiClient($migrationContext);
    }

    protected function httpRequest(HttpClientInterface $client, array $additionalData): Promise\PromiseInterface
    {
        return $client->getAsync(self::DOCUMENTS_URI . $additionalData['uri']);
    }

    protected function getFileExtension(string $originalFileExtension): string
    {
        // ensure pdf file extension, because the order document download endpoint does not return the file extension
        return 'pdf';
    }
}
