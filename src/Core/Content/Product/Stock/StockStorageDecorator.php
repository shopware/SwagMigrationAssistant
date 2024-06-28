<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Core\Content\Product\Stock;

use Shopware\Core\Content\Product\Stock\AbstractStockStorage;
use Shopware\Core\Content\Product\Stock\StockDataCollection;
use Shopware\Core\Content\Product\Stock\StockLoadRequest;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use SwagMigrationAssistant\Migration\Writer\AbstractWriter;

/**
 * @internal
 */
#[Package('services-settings')]
class StockStorageDecorator extends AbstractStockStorage
{
    public function __construct(
        private readonly AbstractStockStorage $innerStockStorage
    ) {
    }

    public function getDecorated(): AbstractStockStorage
    {
        return $this->innerStockStorage;
    }

    public function alter(array $changes, Context $context): void
    {
        if ($this->isOrderCreatedByMigration($context)) {
            return;
        }

        $this->innerStockStorage->alter($changes, $context);
    }

    private function isOrderCreatedByMigration(Context $context): bool
    {
        $writeEventSource = $context->getExtension(AbstractWriter::EXTENSION_NAME);
        $writeEventSource = $writeEventSource instanceof ArrayStruct ? $writeEventSource->get(AbstractWriter::EXTENSION_SOURCE_KEY) : null;

        return $writeEventSource === AbstractWriter::EXTENSION_SOURCE_VALUE;
    }

    public function load(StockLoadRequest $stockRequest, SalesChannelContext $context): StockDataCollection
    {
        return $this->innerStockStorage->load($stockRequest, $context);
    }

    public function index(array $productIds, Context $context): void
    {
        $this->innerStockStorage->index($productIds, $context);
    }
}
