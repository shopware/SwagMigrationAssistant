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
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use Symfony\Component\HttpFoundation\Response;

class AuthClient implements AuthClientInterface
{
    private const DEFAULT_API_ENDPOINT = 'api/_action/data-provider/';

    /**
     * @var Client
     */
    private $apiClient;

    /**
     * @var EntityRepositoryInterface
     */
    private $connectionRepositoy;

    /**
     * @var MigrationContextInterface
     */
    private $migrationContext;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var string
     */
    private $bearerToken = '';

    public function __construct(
        Client $apiClient,
        EntityRepositoryInterface $connectionRepositoy,
        MigrationContextInterface $migrationContext,
        Context $context
    ) {
        $this->apiClient = $apiClient;
        $this->connectionRepositoy = $connectionRepositoy;
        $this->migrationContext = $migrationContext;
        $this->context = $context;
    }

    public function getRequest(string $endpoint, array $config): ResponseInterface
    {
        $endpoint = self::DEFAULT_API_ENDPOINT . $endpoint;
        $this->setupBearerTokenIfNeeded();

        try {
            return $this->apiClient->get($endpoint, \array_merge($config, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->bearerToken,
                ],
            ]));
        } catch (ClientException $clientException) {
            if ($clientException->getCode() !== Response::HTTP_UNAUTHORIZED) {
                throw $clientException;
            }

            $this->renewBearerToken();

            return $this->apiClient->get($endpoint, \array_merge($config, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->bearerToken,
                ],
            ]));
        }
    }

    public function getAsync(string $endpoint, array $config): PromiseInterface
    {
        $endpoint = self::DEFAULT_API_ENDPOINT . $endpoint;
        $this->setupBearerTokenIfNeeded();

        try {
            return $this->apiClient->getAsync($endpoint, \array_merge($config, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->bearerToken,
                ],
            ]));
        } catch (ClientException $clientException) {
            if ($clientException->getCode() !== Response::HTTP_UNAUTHORIZED) {
                throw $clientException;
            }

            $this->renewBearerToken();

            return $this->apiClient->getAsync($endpoint, \array_merge($config, [
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

        $response = $this->apiClient->post('api/oauth/token', [
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
            $this->connectionRepositoy->update([
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

        $this->bearerToken = $credentials['bearer_token'];
    }
}
