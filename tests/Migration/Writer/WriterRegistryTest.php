<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Writer;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Exception\WriterNotFoundException;
use SwagMigrationAssistant\Migration\Writer\WriterRegistry;
use SwagMigrationAssistant\Migration\Writer\WriterRegistryInterface;
use SwagMigrationAssistant\Test\Mock\DummyCollection;
use SwagMigrationAssistant\Test\Mock\Migration\Writer\DummyWriter;

#[Package('services-settings')]
class WriterRegistryTest extends TestCase
{
    private WriterRegistryInterface $writerRegistry;

    protected function setUp(): void
    {
        $this->writerRegistry = new WriterRegistry(new DummyCollection([new DummyWriter()]));
    }

    public function testGetWriter(): void
    {
        $this->expectException(WriterNotFoundException::class);

        $this->writerRegistry->getWriter('foo');
    }
}
