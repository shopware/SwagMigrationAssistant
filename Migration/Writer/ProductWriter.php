<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Writer;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\EntityRepository;

class ProductWriter implements WriterInterface
{

    /**
     * @var EntityRepository
     */
    private $entityRepository;

    public function __construct(EntityRepository $entityRepository)
    {
        $this->entityRepository = $entityRepository;
    }

    public function supports(): string
    {
        return 'product';
    }

    public function writeData(array $data, Context $context): void
    {
        $converted = [];
        array_map(function($data) use (&$converted) {
            $converted[] = array_filter($data->get('converted'));
        }, $data);

        $this->entityRepository->create($converted, $context);
    }
}