<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Writer;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Struct\Serializer\StructNormalizer;
use SwagMigrationNext\Migration\DataSelection\DefaultEntities;

class OrderWriter implements WriterInterface
{
    /**
     * @var EntityRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var StructNormalizer
     */
    private $structNormalizer;

    public function __construct(EntityRepositoryInterface $orderRepository, StructNormalizer $structNormalizer)
    {
        $this->orderRepository = $orderRepository;
        $this->structNormalizer = $structNormalizer;
    }

    public function supports(): string
    {
        return DefaultEntities::ORDER;
    }

    /**
     * @param array[][] $data
     */
    public function writeData(array $data, Context $context): void
    {
        foreach ($data as &$item) {
            foreach ($item['transactions'] as &$transaction) {
                $transaction['amount'] = $this->structNormalizer->denormalize($transaction['amount']);
            }
            unset($transaction);
        }
        unset($item);

        $this->orderRepository->upsert($data, $context);
    }
}
