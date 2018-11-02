<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Writer;

use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;

class AssetWriter implements WriterInterface
{
    /**
     * @var RepositoryInterface
     */
    private $mediaRepository;

    public function __construct(RepositoryInterface $mediaRepository)
    {
        $this->mediaRepository = $mediaRepository;
    }

    public function supports(): string
    {
        return MediaDefinition::getEntityName();
    }

    public function writeData(array $data, Context $context): void
    {
        $this->mediaRepository->upsert($data, $context);
    }
}
