<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Profile\Shopware55\Gateway;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\ShopwareHttpException;
use SwagMigrationAssistant\Exception\GatewayReadException;
use SwagMigrationAssistant\Exception\RequestCertificateInvalidException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Api\Reader\Shopware55ApiEnvironmentReader;

class Shopware55ApiEnvironmentReaderTest extends TestCase
{
    private $dataArray = [
        'testKey' => 'testValue',
    ];

    private $body;

    /** @var RequestException */
    private $sslInsecureException;

    /** @var ShopwareHttpException */
    private $sslInsecureShopwareException;

    /** @var ShopwareHttpException */
    private $gatewayReadException;

    private $warning = [
        'code' => '',
        'detail' => 'No warning.',
    ];

    private $error = [
        'code' => '',
        'detail' => 'No error.',
    ];

    protected function setUp(): void
    {
        $this->body = json_encode([
            'data' => $this->dataArray,
        ]);

        $this->sslInsecureException = new RequestException(
            'SSL insecure',
            new Request('GET', 'product/api/SwagMigrationEnvironment'),
            null,
            null,
            [
                'errno' => 60,
                'url' => 'product/api/SwagMigrationEnvironment',
            ]
        );

        $this->sslInsecureShopwareException = new RequestCertificateInvalidException('product/api/SwagMigrationEnvironment');
        $this->gatewayReadException = new GatewayReadException('Shopware 5.5 Api SwagMigrationEnvironment', 466);
    }

    public function testEnvironmentReaderVerifiedTest(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->body),
        ]);
        $handler = HandlerStack::create($mock);

        $options = [
            'base_uri' => 'product/api/',
            'auth' => ['apiUser', 'apiKey', 'digest'],
            'verify' => false,
            'handler' => $handler,
        ];

        $client = new Client($options);

        $environmentReader = new Shopware55ApiEnvironmentReader(
            $client,
            new MigrationContext(
                new SwagMigrationConnectionEntity()
            )
        );

        $response = $environmentReader->read();
        static::assertSame($response['environmentInformation'], $this->dataArray);
        static::assertSame($response['warning'], $this->warning);
        static::assertSame($response['error'], $this->error);
    }

    public function testEnvironmentReaderNotVerifiedTest(): void
    {
        $mock = new MockHandler([
            $this->sslInsecureException,
            new Response(200, [], $this->body),
        ]);
        $handler = HandlerStack::create($mock);

        $options = [
            'base_uri' => 'product/api/',
            'auth' => ['apiUser', 'apiKey', 'digest'],
            'verify' => false,
            'handler' => $handler,
        ];

        $client = new Client($options);

        $environmentReader = new Shopware55ApiEnvironmentReader(
            $client,
            new MigrationContext(
                new SwagMigrationConnectionEntity()
            )
        );

        $response = $environmentReader->read();
        static::assertSame($response['environmentInformation'], $this->dataArray);
        static::assertSame($response['warning']['code'], $this->sslInsecureShopwareException->getErrorCode());
        static::assertSame($response['warning']['detail'], $this->sslInsecureShopwareException->getMessage());
        static::assertSame($response['error'], $this->error);
    }

    public function testEnvironmentReaderNotConnectedTest(): void
    {
        $mock = new MockHandler([
            $this->sslInsecureException,
            $this->gatewayReadException,
            new Response(200, [], $this->body),
        ]);
        $handler = HandlerStack::create($mock);

        $options = [
            'base_uri' => 'product/api/',
            'auth' => ['apiUser', 'apiKey', 'digest'],
            'verify' => false,
            'handler' => $handler,
        ];

        $client = new Client($options);

        $environmentReader = new Shopware55ApiEnvironmentReader(
            $client,
            new MigrationContext(
                new SwagMigrationConnectionEntity()
            )
        );

        $response = $environmentReader->read();
        static::assertSame($response['environmentInformation'], []);
        static::assertSame($response['warning']['code'], $this->sslInsecureShopwareException->getErrorCode());
        static::assertSame($response['warning']['detail'], $this->sslInsecureShopwareException->getMessage());
        static::assertSame($response['error']['code'], $this->gatewayReadException->getErrorCode());
        static::assertSame($response['error']['detail'], $this->gatewayReadException->getMessage());
    }

    public function testEnvironmentReaderWithGatewayReadException(): void
    {
        $mock = new MockHandler([
            new Response(404, [], $this->body),
            new Response(300, [], $this->body),
            new Response(200, [], $this->body),
        ]);
        $handler = HandlerStack::create($mock);

        $options = [
            'handler' => $handler,
        ];

        $client = new Client($options);

        $environmentReader = new Shopware55ApiEnvironmentReader(
            $client,
            new MigrationContext(
                new SwagMigrationConnectionEntity()
            )
        );

        $response = $environmentReader->read();
        static::assertSame($response['environmentInformation'], []);
        static::assertSame($response['error']['code'], $this->gatewayReadException->getErrorCode());
        static::assertSame($response['error']['detail'], $this->gatewayReadException->getMessage());
    }

    public function testEnvironmentReaderWithInvalidJsonResponse(): void
    {
        $mock = new MockHandler([
            new Response(404, [], $this->body),
            new Response(200, [], 'invalid JSON Response'),
            new Response(200, [], $this->body),
        ]);
        $handler = HandlerStack::create($mock);

        $options = [
            'handler' => $handler,
        ];

        $client = new Client($options);

        $environmentReader = new Shopware55ApiEnvironmentReader(
            $client,
            new MigrationContext(
                new SwagMigrationConnectionEntity()
            )
        );

        $response = $environmentReader->read();
        static::assertSame($response['environmentInformation'], []);
        static::assertSame($response['error']['code'], $this->gatewayReadException->getErrorCode());
        static::assertSame($response['error']['detail'], $this->gatewayReadException->getMessage());
    }
}
