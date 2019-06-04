<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Profile\Shopware55\Gateway;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use SwagMigrationAssistant\Exception\GatewayReadException;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\ProductDataSet;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Api\Reader\Shopware55ApiReader;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Test\Profile\Shopware55\DataSet\FooDataSet;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class Shopware55ApiReaderTest extends TestCase
{
    public function testRead(): void
    {
        $dataArray = [
            'testKey' => 'testValue',
        ];

        $body = json_encode([
            'data' => $dataArray,
        ]);

        $mock = new MockHandler([
            new Response(SymfonyResponse::HTTP_OK, [], $body),
        ]);
        $handler = HandlerStack::create($mock);

        $options = [
            'base_uri' => 'product/api/',
            'auth' => ['apiUser', 'apiKey', 'digest'],
            'verify' => false,
            'handler' => $handler,
        ];

        $client = new Client($options);

        $migrationContext = new MigrationContext(
            null,
            '',
            new ProductDataSet()
        );
        $mock = $this->getMockBuilder(ConnectionFactory::class)->getMock();
        $mock->expects(static::once())
            ->method('createApiClient')
            ->with($migrationContext)
            ->will(static::returnValue($client));

        $apiReader = new Shopware55ApiReader($mock);
        $response = $apiReader->read($migrationContext);
        static::assertSame($response, $dataArray);
    }

    public function testReadNoRouteMapping(): void
    {
        $this->expectException(GatewayReadException::class);

        $apiReader = new Shopware55ApiReader(new ConnectionFactory());
        $migrationContext = new MigrationContext(
            null,
            '',
            new FooDataSet(),
            0,
            0
        );

        $apiReader->read($migrationContext);
    }

    public function testReadGatewayException(): void
    {
        $mock = new MockHandler([
            new Response(SymfonyResponse::HTTP_NO_CONTENT),
        ]);
        $handler = HandlerStack::create($mock);

        $options = [
            'base_uri' => 'product/api/',
            'auth' => ['apiUser', 'apiKey', 'digest'],
            'verify' => false,
            'handler' => $handler,
        ];
        $client = new Client($options);

        $migrationContext = new MigrationContext(
            null,
            '',
            new ProductDataSet()
        );
        $mock = $this->getMockBuilder(ConnectionFactory::class)->getMock();
        $mock->expects(static::once())
            ->method('createApiClient')
            ->with($migrationContext)
            ->will(static::returnValue($client));

        $apiReader = new Shopware55ApiReader($mock);

        try {
            $apiReader->read($migrationContext);
        } catch (\Exception $e) {
            /* @var GatewayReadException $e */
            static::assertInstanceOf(GatewayReadException::class, $e);
            static::assertSame(SymfonyResponse::HTTP_NOT_FOUND, $e->getStatusCode());
            static::assertArrayHasKey('gateway', $e->getParameters());
            static::assertSame($e->getParameters()['gateway'], 'Shopware 5.5 Api product');
        }
    }
}
