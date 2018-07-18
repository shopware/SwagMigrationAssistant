<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mt
 * Date: 18.07.18
 * Time: 14:26
 */

namespace SwagMigrationNext\Test\Gateway\Shopware55\Api\Reader;

use Exception;
use PHPUnit\Framework\TestCase;
use SwagMigrationNext\Gateway\Shopware55\Api\Reader\Shopware55ApiReaderRegistry;
use SwagMigrationNext\Gateway\Shopware55\Api\Reader\Shopware55ApiReaderRegistryInterface;
use SwagMigrationNext\Gateway\Shopware55\Api\Reader\Shopware55ReaderNotFoundException;
use SwagMigrationNext\Test\Mock\DummyCollection;
use SwagMigrationNext\Test\Mock\Gateway\Dummy\Api\Reader\DummyReader;
use Symfony\Component\HttpFoundation\Response;

class Shopware55ApiReaderRegistryTest extends TestCase
{
    /**
     * @var Shopware55ApiReaderRegistryInterface
     */
    private $readerRegistry;

    protected function setUp()
    {
        $this->readerRegistry = new Shopware55ApiReaderRegistry(new DummyCollection([new DummyReader()]));
    }

    public function testGetReader()
    {
        try {
            $this->readerRegistry->getReader('foo');
        } catch (Exception $e) {
            /* @var Shopware55ReaderNotFoundException $e */
            self::assertInstanceOf(Shopware55ReaderNotFoundException::class, $e);
            self::assertEquals(Response::HTTP_NOT_FOUND, $e->getStatusCode());
        }
    }
}
