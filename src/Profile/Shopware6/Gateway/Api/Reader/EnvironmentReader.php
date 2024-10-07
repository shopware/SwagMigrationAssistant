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
use Shopware\Core\Framework\ShopwareHttpException;
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
#[Package('services-settings')]
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

        if ($this->client === null) {
            $information['requestStatus'] = new RequestStatusStruct('SWAG-EMPTY-CREDENTIALS', 'Empty credentials');

            return $information;
        }

        try {
            $information['environmentInformation'] = $this->getEnvironment();
        } catch (ShopwareHttpException $e) {
            $information['requestStatus'] = new RequestStatusStruct($e->getErrorCode(), $e->getMessage(), false);
        }

        return $information;
    }

    /**
     * @return array<string, mixed>
     */
    private function getEnvironment(): array
    {
        if ($this->client === null) {
            return [];
        }

        try {
            $result = $this->client->get('get-environment');

            if ($result->getStatusCode() !== SymfonyResponse::HTTP_OK) {
                throw MigrationException::gatewayRead('Shopware 6 API Environment Call');
            }

            return \json_decode($result->getBody()->getContents(), true);
        } catch (ClientException $e) {
            if ($e->getCode() === 401) {
                throw MigrationException::invalidConnectionAuthentication('get-data');
            }

            throw MigrationException::gatewayRead('Shopware 6 API Environment Call');
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

            throw MigrationException::gatewayRead('Shopware 6 API Environment Call');
        }
    }
}
