<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Writer;

use PHPUnit\Framework\TestCase;
use SwagMigrationAssistant\Exception\WriterNotFoundException;
use SwagMigrationAssistant\Migration\Writer\WriterRegistry;
use SwagMigrationAssistant\Migration\Writer\WriterRegistryInterface;
use SwagMigrationAssistant\Test\Mock\DummyCollection;
use SwagMigrationAssistant\Test\Mock\Migration\Writer\DummyWriter;
use Symfony\Component\HttpFoundation\Response;

class WriterRegistryTest extends TestCase
{
    /**
     * @var WriterRegistryInterface
     */
    private $writerRegistry;

    protected function setUp(): void
    {
        $this->writerRegistry = new WriterRegistry(new DummyCollection([new DummyWriter()]));
    }

    public function testGetWriter(): void
    {
        try {
            $this->writerRegistry->getWriter('foo');
        } catch (\Exception $e) {
            /* @var WriterNotFoundException $e */
            static::assertInstanceOf(WriterNotFoundException::class, $e);
            static::assertSame(Response::HTTP_NOT_FOUND, $e->getStatusCode());
        }
    }
}
