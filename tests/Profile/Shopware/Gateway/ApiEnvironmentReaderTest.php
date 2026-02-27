<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware\Gateway;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Gateway\HttpSimpleClient;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\RequestStatusStruct;
use SwagMigrationAssistant\Profile\Shopware\Exception\MigrationShopwareProfileException;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\EnvironmentReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

#[Package('fundamentals@after-sales')]
#[CoversClass(EnvironmentReader::class)]
class ApiEnvironmentReaderTest extends TestCase
{
    public function testEmptyClientReturnsRequestStatus(): void
    {
        $connectionFactory = $this->createMock(ConnectionFactory::class);
        $connectionFactory
            ->method('createApiClient')
            ->willThrowException(MigrationException::apiConnectionError('Could not create API client. Could be due to empty credentials or invalid connection.'));

        $environmentReader = new EnvironmentReader($connectionFactory);

        $migrationContext = new MigrationContext(
            new SwagMigrationConnectionEntity(),
            new Shopware55Profile(),
        );

        $response = $environmentReader->read($migrationContext);

        static::assertSame($response['environmentInformation'], []);

        /** @var RequestStatusStruct $requestStatus */
        $requestStatus = $response['requestStatus'];

        static::assertInstanceOf(RequestStatusStruct::class, $requestStatus);
        static::assertSame($requestStatus->getCode(), MigrationException::API_CONNECTION_ERROR);
        static::assertSame($requestStatus->getMessage(), 'Could not create API client. Could be due to empty credentials or invalid connection.');
        static::assertNotNull($requestStatus->getErrorFile());
        static::assertNotNull($requestStatus->getErrorLine());
    }

    /**
     * @param array<Response|\Exception> $responses
     */
    #[DataProvider('provideResponseExceptions')]
    public function testResponseExceptions(
        array $responses,
        string $expectedErrorCode,
        ?string $expectedMessage,
        bool $shouldHaveException,
    ): void {
        $mock = new MockHandler($responses);
        $handler = HandlerStack::create($mock);

        $options = [
            'base_uri' => 'api/',
            'auth' => ['apiUser', 'apiKey', 'digest'],
            'handler' => $handler,
        ];

        $client = new HttpSimpleClient($options);

        $migrationContext = new MigrationContext(
            new SwagMigrationConnectionEntity(),
            new Shopware55Profile(),
        );

        $connectionFactory = $this->createMock(ConnectionFactory::class);
        $connectionFactory
            ->method('createApiClient')
            ->willReturn($client);

        $environmentReader = new EnvironmentReader($connectionFactory);

        $response = $environmentReader->read($migrationContext);

        static::assertSame($response['environmentInformation'], []);

        /** @var RequestStatusStruct $requestStatus */
        $requestStatus = $response['requestStatus'];

        static::assertInstanceOf(RequestStatusStruct::class, $requestStatus);
        static::assertSame($requestStatus->getCode(), $expectedErrorCode);

        if ($expectedMessage !== null) {
            static::assertSame($requestStatus->getMessage(), $expectedMessage);
        }

        if ($shouldHaveException) {
            static::assertNotNull($requestStatus->getErrorFile());
            static::assertNotNull($requestStatus->getErrorLine());
        } else {
            static::assertNull($requestStatus->getErrorFile());
            static::assertNull($requestStatus->getErrorLine());
        }
    }

    public static function provideResponseExceptions(): \Generator
    {
        yield 'getting environment fails with unauthorized ClientException' => [
            'responses' => [
                new Response(SymfonyResponse::HTTP_UNAUTHORIZED),
            ],
            'expectedErrorCode' => MigrationException::INVALID_CONNECTION_CREDENTIALS,
            'expectedMessage' => 'The connection credentials are invalid or incomplete for "SwagMigrationEnvironment".',
            'shouldHaveException' => true,
        ];

        yield 'getting environment fails with SSL required RequestException' => [
            'responses' => [
                new RequestException(
                    'SSL required',
                    new Request('GET', 'version'),
                    new Response(SymfonyResponse::HTTP_UPGRADE_REQUIRED, [], 'Error: SSL required: "version"')
                ),
            ],
            'expectedErrorCode' => MigrationException::SSL_REQUIRED,
            'expectedMessage' => 'The request failed, because SSL is required.',
            'shouldHaveException' => true,
        ];

        yield 'getting environment fails with invalid certificate' => [
            'responses' => [
                new RequestException(
                    'Invalid certificate',
                    new Request('GET', 'version'),
                    null,
                    null,
                    ['errno' => 60, 'url' => 'version']
                ),
            ],
            'expectedErrorCode' => MigrationException::REQUEST_CERTIFICATE_INVALID,
            'expectedMessage' => 'The following cURL request failed with an SSL certificate problem: "version"',
            'shouldHaveException' => true,
        ];

        yield 'getting environment fails with ConnectException' => [
            'responses' => [
                new ConnectException('Could not connect', new Request('GET', 'version')),
            ],
            'expectedErrorCode' => MigrationException::API_CONNECTION_ERROR,
            'expectedMessage' => 'Could not connect',
            'shouldHaveException' => true,
        ];

        yield 'getting environment fails with ClientException' => [
            'responses' => [
                new ClientException(
                    'Could not connect',
                    new Request('GET', 'version'),
                    new Response(SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR)
                ),
            ],
            'expectedErrorCode' => MigrationException::API_CONNECTION_ERROR,
            'expectedMessage' => 'Could not connect',
            'shouldHaveException' => true,
        ];

        yield 'getting environment fails with RequestException' => [
            'responses' => [
                new RequestException('Could not connect', new Request('GET', 'version')),
            ],
            'expectedErrorCode' => MigrationException::API_CONNECTION_ERROR,
            'expectedMessage' => 'Could not connect',
            'shouldHaveException' => true,
        ];

        yield 'getting environment fails with internal server error' => [
            'responses' => [
                new Response(SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR),
            ],
            'expectedErrorCode' => MigrationException::API_CONNECTION_ERROR,
            'expectedMessage' => null, // we don't care about the exact message, it's guzzle specific
            'shouldHaveException' => true,
        ];

        yield 'getting environment fails without data' => [
            'responses' => [
                new Response(SymfonyResponse::HTTP_OK, [], 'nothing'),
            ],
            'expectedErrorCode' => MigrationException::API_CONNECTION_ERROR,
            'expectedMessage' => 'The environment endpoint did not return data',
            'shouldHaveException' => true,
        ];

        yield 'getting environment fails with 404' => [
            'responses' => [
                new Response(SymfonyResponse::HTTP_NOT_FOUND),
                new Response(SymfonyResponse::HTTP_OK, [], (string) json_encode(['success' => true])),
            ],
            'expectedErrorCode' => MigrationShopwareProfileException::PLUGIN_NOT_INSTALLED,
            'expectedMessage' => 'The required plugin is not installed in the source shop system. Please look up the documentation for this gateway.',
            'shouldHaveException' => true,
        ];
    }

    public function testGetsEnvironmentInformation(): void
    {
        $mock = new MockHandler([
            new Response(200, [], (string) json_encode(['data' => ['version' => 'test']])),
        ]);

        $handler = HandlerStack::create($mock);

        $options = [
            'base_uri' => 'api/',
            'auth' => ['apiUser', 'apiKey', 'digest'],
            'handler' => $handler,
        ];

        $client = new HttpSimpleClient($options);

        $migrationContext = new MigrationContext(
            new SwagMigrationConnectionEntity(),
            new Shopware55Profile()
        );

        $connectionFactory = $this->createMock(ConnectionFactory::class);
        $connectionFactory
            ->method('createApiClient')
            ->willReturn($client);

        $environmentReader = new EnvironmentReader($connectionFactory);

        $response = $environmentReader->read($migrationContext);

        static::assertEquals(['version' => 'test'], $response['environmentInformation']);
        static::assertEquals(new RequestStatusStruct(), $response['requestStatus']);
    }
}
