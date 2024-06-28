<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Core\Content\Product\Stock;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\Stock\StockAlteration;
use Shopware\Core\Content\Product\Stock\StockStorage;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\ArrayStruct;
use SwagMigrationAssistant\Core\Content\Product\Stock\StockStorageDecorator;
use SwagMigrationAssistant\Migration\Writer\AbstractWriter;

#[Package('services-settings')]
class StockStorageDecoratorTest extends TestCase
{
    private StockStorage&MockObject $stockStorage;

    private StockStorageDecorator $stockStorageDecorator;

    /**
     * @var list<StockAlteration>
     */
    private array $dummyChanges;

    protected function setUp(): void
    {
        $this->stockStorage = $this->createMock(StockStorage::class);
        $this->stockStorageDecorator = new StockStorageDecorator($this->stockStorage);
        $this->dummyChanges = [
            new StockAlteration('line-item-id', 'product-id', 10, 9),
        ];
    }

    public function testAlterWithMigrationSourceShallNotExecuteStockStorage(): void
    {
        $context = $this->createContextWithExtension();

        $this->stockStorage->expects(static::never())->method('alter');

        $this->stockStorageDecorator->alter($this->dummyChanges, $context);
    }

    public function testAlterWithoutMigrationSourceShallExecuteStockStorage(): void
    {
        $this->stockStorage->expects(static::once())->method('alter');

        $this->stockStorageDecorator->alter($this->dummyChanges, Context::createDefaultContext());
    }

    private function createContextWithExtension(): Context
    {
        $context = Context::createDefaultContext();
        $context->addExtension(AbstractWriter::EXTENSION_NAME, new ArrayStruct([AbstractWriter::EXTENSION_SOURCE_KEY => AbstractWriter::EXTENSION_SOURCE_VALUE]));

        return $context;
    }
}
