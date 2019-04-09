<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Writer;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use SwagMigrationNext\Migration\DataSelection\DefaultEntities;

class MediaWriter implements WriterInterface
{
    /**
     * @var EntityRepositoryInterface
     */
    private $mediaRepository;

    public function __construct(EntityRepositoryInterface $mediaRepository)
    {
        $this->mediaRepository = $mediaRepository;
    }

    public function supports(): string
    {
        return DefaultEntities::MEDIA;
    }

    public function writeData(array $data, Context $context): void
    {
        $this->mediaRepository->upsert($data, $context);
    }
}
