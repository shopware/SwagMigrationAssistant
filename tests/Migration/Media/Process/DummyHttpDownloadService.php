<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Media\Process;

use Doctrine\DBAL\Connection;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Gateway\HttpClientInterface;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Media\Processor\HttpDownloadServiceBase;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('services-settings')]
class DummyHttpDownloadService extends HttpDownloadServiceBase
{
    public function __construct(
        Connection $dbalConnection,
        EntityRepository $mediaFileRepo,
        FileSaver $fileSaver,
        LoggingServiceInterface $loggingService,
        private readonly HttpClientInterface $httpClient,
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
        return true;
    }

    /**
     * {@inheritDoc}
     */
    protected function getMediaEntity(): string
    {
        return DefaultEntities::MEDIA;
    }

    protected function getHttpClient(MigrationContextInterface $migrationContext): ?HttpClientInterface
    {
        return $this->httpClient;
    }
}
