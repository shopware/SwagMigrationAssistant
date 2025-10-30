<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Writer;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Mapping\Lookup\ProductSortingLookup;
use SwagMigrationAssistant\Migration\Writer\AbstractWriter;

#[Package('fundamentals@after-sales')]
class ProductSortingWriter extends AbstractWriter
{
    public function __construct(
        protected EntityWriterInterface $entityWriter,
        protected EntityDefinition $definition,
        private readonly ProductSortingLookup $productSortingLookup,
    ) {
        parent::__construct($this->entityWriter, $this->definition);
    }

    public function supports(): string
    {
        return DefaultEntities::PRODUCT_SORTING;
    }

    /**
     * @param array<mixed> $data
     *
     * @return array<string, array<EntityWriteResult>>
     */
    public function writeData(array $data, Context $context): array
    {
        // do not overwrite locked product sorting
        $data = \array_filter($data, function ($value) use ($context) {
            return !$this->productSortingLookup->getIsLocked($value['key'], $context);
        });

        return parent::writeData($data, $context);
    }
}
