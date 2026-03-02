<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware6\Gateway\Connection;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionCollection;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Connection\AuthClient;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

#[CoversClass(AuthClient::class)]
class AuthClientTest extends TestCase
{
    public function testGetUsesExistingBearerToken(): void
    {
        $connection = $this->createConnection('existing-token');
        $migrationContext = new MigrationContext($connection);
        $expectedResponse = new Response(SymfonyResponse::HTTP_OK);
        $apiClient = $this->createApiClientWithResponses([
            function (RequestInterface $request) use ($expectedResponse): ResponseInterface {
                static::assertSame('/api/version', (string) $request->getUri());
                static::assertSame('GET', $request->getMethod());
                static::assertSame('Bearer existing-token', $request->getHeaderLine('Authorization'));

                return $expectedResponse;
            },
        ]);

        $connectionRepository = $this->createConnectionRepositoryMock(0);
        $client = new AuthClient($apiClient, $connectionRepository, $migrationContext, Context::createDefaultContext());
        $response = $client->get('/api/version');

        static::assertSame($expectedResponse, $response);
    }

    public function testGetRenewsBearerTokenAfterUnauthorizedAndRetries(): void
    {
        $connection = $this->createConnection('expired-token');
        $migrationContext = new MigrationContext($connection);
        $expectedResponse = new Response(SymfonyResponse::HTTP_OK);

        $apiClient = $this->createApiClientForRenewalFlow('expired-token', 'renewed-token', $expectedResponse);
        $connectionRepository = $this->createConnectionRepositoryMock(1);
        $client = new AuthClient($apiClient, $connectionRepository, $migrationContext, Context::createDefaultContext());
        $response = $client->get('/api/version');

        static::assertSame($expectedResponse, $response);
        $credentials = $connection->getCredentialFields();
        static::assertIsArray($credentials);
        static::assertArrayHasKey('bearer_token', $credentials);
        static::assertSame('renewed-token', $credentials['bearer_token']);
    }

    public function testGetThrowsExceptionOnMissingCredentials(): void
    {
        $connection = new SwagMigrationConnectionEntity();
        $migrationContext = new MigrationContext($connection);

        $apiClient = $this->createApiClientWithResponses([]);
        $connectionRepository = $this->createConnectionRepositoryMock(0);

        $client = new AuthClient($apiClient, $connectionRepository, $migrationContext, Context::createDefaultContext());

        try {
            $client->get('/api/version');
        } catch (MigrationException $e) {
            static::assertSame(MigrationException::INVALID_CONNECTION_CREDENTIALS, $e->getErrorCode());

            return;
        }

        static::fail('Expected exception not thrown');
    }

    public function testGetIgnoresDalFailureWhenSavingRenewedToken(): void
    {
        $connection = $this->createConnection('expired-token');
        $migrationContext = new MigrationContext($connection);
        $expectedResponse = new Response(SymfonyResponse::HTTP_OK);

        $apiClient = $this->createApiClientForRenewalFlow('expired-token', 'renewed-token', $expectedResponse);
        $connectionRepository = $this->createConnectionRepositoryMock(1, new \RuntimeException('DAL write failed'));
        $client = new AuthClient($apiClient, $connectionRepository, $migrationContext, Context::createDefaultContext());
        $response = $client->get('/api/version');

        static::assertSame($expectedResponse, $response);
        $credentials = $connection->getCredentialFields();
        static::assertIsArray($credentials);
        static::assertArrayHasKey('bearer_token', $credentials);
        static::assertSame('renewed-token', $credentials['bearer_token']);
    }

    private function createConnection(string $bearerToken): SwagMigrationConnectionEntity
    {
        $connection = new SwagMigrationConnectionEntity();
        $connection->setId(Uuid::randomHex());

        $connection->setCredentialFields([
            'apiUser' => 'api-user',
            'apiPassword' => 'api-password',
            'bearer_token' => $bearerToken,
        ]);

        return $connection;
    }

    /**
     * @return EntityRepository<SwagMigrationConnectionCollection>
     */
    private function createConnectionRepositoryMock(int $expectedUpdateCalls, ?\Throwable $updateException = null): EntityRepository
    {
        /** @var EntityRepository<SwagMigrationConnectionCollection>&MockObject $connectionRepository */
        $connectionRepository = $this->createMock(EntityRepository::class);

        $updateExpectation = $connectionRepository
            ->expects($this->exactly($expectedUpdateCalls))
            ->method('update');

        if ($updateException !== null) {
            $updateExpectation->willThrowException($updateException);
        }

        return $connectionRepository;
    }

    private function createApiClientForRenewalFlow(
        string $oldBearerToken,
        string $newBearerToken,
        ResponseInterface $expectedResponse
    ): Client {
        $firstRequestException = new ClientException(
            'Unauthorized',
            new Request('GET', '/api/version'),
            new Response(SymfonyResponse::HTTP_UNAUTHORIZED)
        );
        $tokenResponse = new Response(
            SymfonyResponse::HTTP_OK,
            [],
            (string) json_encode(['access_token' => $newBearerToken])
        );

        return $this->createApiClientWithResponses(
            [
                function (RequestInterface $request) use ($oldBearerToken, $firstRequestException): void {
                    static::assertSame('/api/version', (string) $request->getUri());
                    static::assertSame('GET', $request->getMethod());
                    static::assertSame('Bearer ' . $oldBearerToken, $request->getHeaderLine('Authorization'));

                    throw $firstRequestException;
                },
                function (RequestInterface $request) use ($tokenResponse): ResponseInterface {
                    static::assertSame('/api/oauth/token', (string) $request->getUri());
                    static::assertSame('POST', $request->getMethod());
                    static::assertSame(
                        '{"grant_type":"client_credentials","client_id":"api-user","client_secret":"api-password"}',
                        (string) $request->getBody()
                    );

                    return $tokenResponse;
                },
                function (RequestInterface $request) use ($newBearerToken, $expectedResponse): ResponseInterface {
                    static::assertSame('/api/version', (string) $request->getUri());
                    static::assertSame('GET', $request->getMethod());
                    static::assertSame('Bearer ' . $newBearerToken, $request->getHeaderLine('Authorization'));

                    return $expectedResponse;
                },
            ]
        );
    }

    /**
     * @param list<\Closure(RequestInterface): ResponseInterface|void> $responses
     */
    private function createApiClientWithResponses(array $responses): Client
    {
        $handler = HandlerStack::create(new MockHandler($responses));

        return new Client([
            'handler' => $handler,
        ]);
    }
}
