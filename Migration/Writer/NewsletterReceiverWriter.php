<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Writer;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use SwagMigrationNext\Migration\DataSelection\DefaultEntities;

class NewsletterReceiverWriter implements WriterInterface
{
    /**
     * @var EntityRepositoryInterface
     */
    private $newsletterReceiverRepository;

    public function __construct(EntityRepositoryInterface $newsletterReceiverRepository)
    {
        $this->newsletterReceiverRepository = $newsletterReceiverRepository;
    }

    public function supports(): string
    {
        return DefaultEntities::NEWSLETTER_RECEIVER;
    }

    public function writeData(array $data, Context $context): void
    {
        $this->newsletterReceiverRepository->upsert($data, $context);
    }
}
