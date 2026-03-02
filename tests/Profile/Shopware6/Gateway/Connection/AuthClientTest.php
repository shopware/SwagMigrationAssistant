<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware6\Gateway\Connection;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
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

        $apiClient = $this->createMock(Client::class);
        $apiClient
            ->expects($this->once())
            ->method('get')
            ->with('/api/version', [
                'headers' => [
                    'Authorization' => 'Bearer existing-token',
                ],
            ])
            ->willReturn($expectedResponse);
        $apiClient->expects($this->never())->method('post');

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
        static::assertSame('renewed-token', $connection->getCredentialFields()['bearer_token']);
    }

    public function testGetThrowsExceptionOnMissingCredentials(): void
    {
        $connection = new SwagMigrationConnectionEntity();
        $migrationContext = new MigrationContext($connection);

        $apiClient = $this->createMock(Client::class);
        /** @var EntityRepository<SwagMigrationConnectionCollection> $connectionRepository */
        $connectionRepository = $this->createMock(EntityRepository::class);

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
        static::assertSame('renewed-token', $connection->getCredentialFields()['bearer_token']);
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
        /** @var EntityRepository<SwagMigrationConnectionCollection> $connectionRepository */
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
        $unauthorizedException = new ClientException(
            'Unauthorized',
            new Request('GET', '/api/version'),
            new Response(SymfonyResponse::HTTP_UNAUTHORIZED)
        );
        $tokenResponse = new Response(
            SymfonyResponse::HTTP_OK,
            [],
            (string) json_encode(['access_token' => $newBearerToken])
        );

        $getCallCount = 0;
        $apiClient = $this->createMock(Client::class);
        $apiClient
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(function (string $uri, array $options) use (
                &$getCallCount,
                $unauthorizedException,
                $oldBearerToken,
                $newBearerToken,
                $expectedResponse
            ): ResponseInterface {
                ++$getCallCount;

                static::assertSame('/api/version', $uri);

                if ($getCallCount === 1) {
                    static::assertSame('Bearer ' . $oldBearerToken, $options['headers']['Authorization']);
                    throw $unauthorizedException;
                }

                static::assertSame('Bearer ' . $newBearerToken, $options['headers']['Authorization']);

                return $expectedResponse;
            });
        $apiClient
            ->expects($this->once())
            ->method('post')
            ->with('/api/oauth/token', [
                'json' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => 'api-user',
                    'client_secret' => 'api-password',
                ],
            ])
            ->willReturn($tokenResponse);

        return $apiClient;
    }
}
