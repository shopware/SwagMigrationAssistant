<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware\Gateway;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use SwagMigrationAssistant\Exception\GatewayReadException;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ProductDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\ProductReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ApiReaderTest extends TestCase
{
    public function testRead(): void
    {
        $dataArray = [
            'testKey' => 'testValue',
        ];

        $body = \json_encode([
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
            new Shopware55Profile(),
            null,
            '',
            new ProductDataSet()
        );
        $mock = $this->getMockBuilder(ConnectionFactory::class)->getMock();
        $mock->expects(static::once())
            ->method('createApiClient')
            ->with($migrationContext)
            ->willReturn($client);

        $apiReader = new ProductReader($mock);
        $response = $apiReader->read($migrationContext);
        static::assertSame($response, $dataArray);
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
            new Shopware55Profile(),
            null,
            '',
            new ProductDataSet()
        );
        $mock = $this->getMockBuilder(ConnectionFactory::class)->getMock();
        $mock->expects(static::once())
            ->method('createApiClient')
            ->with($migrationContext)
            ->willReturn($client);

        $apiReader = new ProductReader($mock);

        try {
            $apiReader->read($migrationContext);
        } catch (\Exception $e) {
            /* @var GatewayReadException $e */
            static::assertInstanceOf(GatewayReadException::class, $e);
            static::assertSame(SymfonyResponse::HTTP_NOT_FOUND, $e->getStatusCode());
            static::assertArrayHasKey('gateway', $e->getParameters());
            static::assertSame($e->getParameters()['gateway'], 'Shopware Api product');
        }
    }
}
