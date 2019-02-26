<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration\Writer;

use Exception;
use PHPUnit\Framework\TestCase;
use SwagMigrationNext\Exception\WriterNotFoundException;
use SwagMigrationNext\Migration\Writer\WriterRegistry;
use SwagMigrationNext\Migration\Writer\WriterRegistryInterface;
use SwagMigrationNext\Test\Mock\DummyCollection;
use SwagMigrationNext\Test\Mock\Migration\Writer\DummyWriter;
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
        } catch (Exception $e) {
            /* @var WriterNotFoundException $e */
            static::assertInstanceOf(WriterNotFoundException::class, $e);
            static::assertSame(Response::HTTP_NOT_FOUND, $e->getStatusCode());
        }
    }
}
