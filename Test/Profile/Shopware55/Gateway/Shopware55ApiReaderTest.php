<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Profile\Shopware55\Gateway;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\ProductDefinition;
use SwagMigrationNext\Exception\GatewayReadException;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Profile\Shopware55\Gateway\Api\Reader\Shopware55ApiReader;
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

        $apiReader = new Shopware55ApiReader(
            $client,
            new MigrationContext(
                '',
                null,
                ProductDefinition::getEntityName(),
                0,
                0
            )
        );

        $response = $apiReader->read();
        static::assertSame($response, $dataArray);
    }

    public function testReadNoRouteMapping(): void
    {
        $this->expectException(GatewayReadException::class);
        $this->expectExceptionMessage('No endpoint for entity foo available.');

        $apiReader = new Shopware55ApiReader(
            new Client(),
            new MigrationContext(
                '',
                null,
                'foo',
                0,
                0
            )
        );

        $apiReader->read();
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

        $apiReader = new Shopware55ApiReader(
            new Client($options),
            new MigrationContext(
                '',
                null,
                ProductDefinition::getEntityName(),
                0,
                0
            )
        );

        try {
            $apiReader->read();
        } catch (\Exception $e) {
            /* @var GatewayReadException $e */
            static::assertInstanceOf(GatewayReadException::class, $e);
            static::assertSame(SymfonyResponse::HTTP_NOT_FOUND, $e->getStatusCode());
            static::assertSame('Could not read from gateway: "Shopware 5.5 Api product"', $e->getMessage());
        }
    }
}
