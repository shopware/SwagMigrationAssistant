<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware\Gateway;

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
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\EnvironmentReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

class ApiEnvironmentReaderTest extends TestCase
{
    private $dataArray = [
        'testKey' => 'testValue',
    ];

    private $body;

    /**
     * @var RequestException
     */
    private $sslInsecureException;

    /**
     * @var ShopwareHttpException
     */
    private $sslInsecureShopwareException;

    /**
     * @var ShopwareHttpException
     */
    private $gatewayReadException;

    private $error = [
        'code' => '',
        'message' => 'No error.',
    ];

    protected function setUp(): void
    {
        $this->body = \json_encode([
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

        $migrationContext = new MigrationContext(
            new Shopware55Profile()
        );

        $mock = $this->getMockBuilder(ConnectionFactory::class)->getMock();
        $mock->expects(static::exactly(2))
            ->method('createApiClient')
            ->with($migrationContext)
            ->willReturn($client);

        $environmentReader = new EnvironmentReader($mock);

        $response = $environmentReader->read($migrationContext);
        static::assertSame($response['environmentInformation'], $this->dataArray);
        static::assertSame($response['requestStatus']->getCode(), $this->error['code']);
        static::assertSame($response['requestStatus']->getMessage(), $this->error['message']);
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
        $migrationContext = new MigrationContext(
            new Shopware55Profile()
        );
        $mock = $this->getMockBuilder(ConnectionFactory::class)->getMock();
        $mock->expects(static::exactly(2))
            ->method('createApiClient')
            ->with($migrationContext)
            ->willReturn($client);
        $environmentReader = new EnvironmentReader($mock);

        $response = $environmentReader->read($migrationContext);
        static::assertSame($response['environmentInformation'], $this->dataArray);
        static::assertSame($response['requestStatus']->getCode(), $this->sslInsecureShopwareException->getErrorCode());
        static::assertSame($response['requestStatus']->getMessage(), $this->sslInsecureShopwareException->getMessage());
        static::assertTrue($response['requestStatus']->getIsWarning());
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

        $migrationContext = new MigrationContext(
            new Shopware55Profile()
        );
        $mock = $this->getMockBuilder(ConnectionFactory::class)->getMock();
        $mock->expects(static::exactly(2))
            ->method('createApiClient')
            ->with($migrationContext)
            ->willReturn($client);
        $environmentReader = new EnvironmentReader($mock);

        $response = $environmentReader->read($migrationContext);
        static::assertSame($response['environmentInformation'], []);
        static::assertSame($response['requestStatus']->getCode(), $this->gatewayReadException->getErrorCode());
        static::assertSame($response['requestStatus']->getMessage(), $this->gatewayReadException->getMessage());
        static::assertFalse($response['requestStatus']->getIsWarning());
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
        $migrationContext = new MigrationContext(
            new Shopware55Profile()
        );
        $mock = $this->getMockBuilder(ConnectionFactory::class)->getMock();
        $mock->expects(static::exactly(2))
            ->method('createApiClient')
            ->with($migrationContext)
            ->willReturn($client);
        $environmentReader = new EnvironmentReader($mock);

        $response = $environmentReader->read($migrationContext);
        static::assertSame($response['environmentInformation'], []);
        static::assertSame($response['requestStatus']->getCode(), $this->gatewayReadException->getErrorCode());
        static::assertSame($response['requestStatus']->getMessage(), $this->gatewayReadException->getMessage());
        static::assertFalse($response['requestStatus']->getIsWarning());
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

        $migrationContext = new MigrationContext(
            new Shopware55Profile()
        );
        $mock = $this->getMockBuilder(ConnectionFactory::class)->getMock();
        $mock->expects(static::exactly(2))
            ->method('createApiClient')
            ->with($migrationContext)
            ->willReturn($client);
        $environmentReader = new EnvironmentReader($mock);

        $response = $environmentReader->read($migrationContext);
        static::assertSame($response['environmentInformation'], []);
        static::assertSame($response['requestStatus']->getCode(), $this->gatewayReadException->getErrorCode());
        static::assertSame($response['requestStatus']->getMessage(), $this->gatewayReadException->getMessage());
        static::assertFalse($response['requestStatus']->getIsWarning());
    }
}
