<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Gateway\HttpClientInterface;
use SwagMigrationAssistant\Migration\Gateway\Reader\EnvironmentReaderInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\RequestStatusStruct;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Connection\ConnectionFactoryInterface;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * @phpstan-type ReadArray array{environmentInformation: array<string, mixed>, requestStatus: RequestStatusStruct}
 */
#[Package('fundamentals@after-sales')]
class EnvironmentReader implements EnvironmentReaderInterface
{
    private ?HttpClientInterface $client = null;

    public function __construct(private readonly ConnectionFactoryInterface $connectionFactory)
    {
    }

    /**
     * @return ReadArray
     */
    public function read(MigrationContextInterface $migrationContext): array
    {
        $this->client = $this->connectionFactory->createApiClient($migrationContext);

        $information = [
            'environmentInformation' => [],
            'requestStatus' => new RequestStatusStruct(),
        ];

        try {
            $information['environmentInformation'] = $this->getEnvironment();
        } catch (\Throwable $e) {
            $information['requestStatus'] = new RequestStatusStruct(
                method_exists($e, 'getErrorCode') ? $e->getErrorCode() : MigrationException::API_CONNECTION_ERROR,
                $e->getMessage(),
                false,
                $e
            );
        }

        return $information;
    }

    /**
     * @return array<string, mixed>
     */
    private function getEnvironment(): array
    {
        if ($this->client === null) {
            throw MigrationException::apiConnectionError(
                'Could not create API client. Could be due to empty credentials or invalid connection.'
            );
        }

        try {
            $result = $this->client->get('get-environment');

            if ($result->getStatusCode() !== SymfonyResponse::HTTP_OK) {
                throw MigrationException::apiConnectionError('The response status code was not 200.');
            }

            return \json_decode($result->getBody()->getContents(), true);
        } catch (ClientException $e) {
            if ($e->getCode() === 401) {
                throw MigrationException::invalidConnectionAuthentication('get-data');
            }

            throw $e;
        } catch (RequestException $e) {
            if ($e->getRequest()->getUri()->getPath() === '/api/oauth/token') {
                // something went wrong with authentication.
                throw MigrationException::invalidConnectionAuthentication('get-data');
            }

            $response = $e->getResponse();
            if ($response !== null && \mb_strpos($response->getBody()->getContents(), 'SSL required')) {
                throw MigrationException::sslRequired();
            }

            if (isset($e->getHandlerContext()['errno']) && $e->getHandlerContext()['errno'] === 60) {
                throw MigrationException::requestCertificateInvalid($e->getHandlerContext()['url']);
            }

            throw $e;
        }
    }
}
