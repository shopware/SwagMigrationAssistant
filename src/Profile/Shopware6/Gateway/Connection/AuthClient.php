<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Gateway\Connection;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionCollection;
use SwagMigrationAssistant\Migration\Gateway\HttpClientInterface;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use Symfony\Component\HttpFoundation\Response;

#[Package('services-settings')]
class AuthClient implements HttpClientInterface
{
    private string $bearerToken = '';

    /**
     * @param EntityRepository<SwagMigrationConnectionCollection> $connectionRepository
     */
    public function __construct(
        private readonly Client $apiClient,
        private readonly EntityRepository $connectionRepository,
        private readonly MigrationContextInterface $migrationContext,
        private readonly Context $context
    ) {
    }

    public function get(string $uri, array $options = []): ResponseInterface
    {
        $this->setupBearerTokenIfNeeded();

        try {
            return $this->apiClient->get($uri, \array_merge($options, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->bearerToken,
                ],
            ]));
        } catch (ClientException $clientException) {
            if ($clientException->getCode() !== Response::HTTP_UNAUTHORIZED) {
                throw $clientException;
            }

            $this->renewBearerToken();

            return $this->apiClient->get($uri, \array_merge($options, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->bearerToken,
                ],
            ]));
        }
    }

    public function getAsync(string $uri, array $options = []): PromiseInterface
    {
        $this->setupBearerTokenIfNeeded();

        try {
            return $this->apiClient->getAsync($uri, \array_merge($options, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->bearerToken,
                ],
            ]));
        } catch (ClientException $clientException) {
            if ($clientException->getCode() !== Response::HTTP_UNAUTHORIZED) {
                throw $clientException;
            }

            $this->renewBearerToken();

            return $this->apiClient->getAsync($uri, \array_merge($options, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->bearerToken,
                ],
            ]));
        }
    }

    private function setupBearerTokenIfNeeded(): void
    {
        if (empty($this->bearerToken)) {
            $this->loadBearerToken();
        }
    }

    private function renewBearerToken(): void
    {
        $connection = $this->migrationContext->getConnection();

        if ($connection === null) {
            return; // TODO: throw exception
        }

        $credentials = $connection->getCredentialFields();

        if ($credentials === null) {
            return; // TODO: throw exception
        }

        $response = $this->apiClient->post('/api/oauth/token', [
            'json' => [
                'grant_type' => 'client_credentials',
                'client_id' => $credentials['apiUser'],
                'client_secret' => $credentials['apiPassword'],
            ],
        ]);

        $result = \json_decode($response->getBody()->getContents(), true);

        if (!empty($result['access_token'])) {
            $this->bearerToken = $result['access_token'];
            $this->saveBearerToken();
        }
    }

    private function saveBearerToken(): void
    {
        $connection = $this->migrationContext->getConnection();

        if ($connection === null) {
            return;
        }

        $credentialFields = $connection->getCredentialFields();

        if ($credentialFields === null) {
            return;
        }

        $connectionUuid = $connection->getId();
        $credentialFields['bearer_token'] = $this->bearerToken;

        $this->context->scope(MigrationContext::SOURCE_CONTEXT, function (Context $context) use ($connectionUuid, $credentialFields): void {
            $this->connectionRepository->update([
                [
                    'id' => $connectionUuid,
                    'credentialFields' => $credentialFields,
                ],
            ], $context);
        });
    }

    private function loadBearerToken(): void
    {
        $connection = $this->migrationContext->getConnection();

        if ($connection === null) {
            $this->renewBearerToken();

            return;
        }

        $credentials = $connection->getCredentialFields();

        if ($credentials === null) {
            $this->renewBearerToken();

            return;
        }

        if (empty($credentials['bearer_token'])) {
            $this->renewBearerToken();

            return;
        }

        $this->bearerToken = (string) $credentials['bearer_token'];
    }
}
