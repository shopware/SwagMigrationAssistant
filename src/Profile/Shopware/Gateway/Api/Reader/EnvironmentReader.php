<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use Psr\Http\Message\ResponseInterface;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\ShopwareHttpException;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Gateway\HttpClientInterface;
use SwagMigrationAssistant\Migration\Gateway\Reader\EnvironmentReaderInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\RequestStatusStruct;
use SwagMigrationAssistant\Profile\Shopware\Exception\MigrationShopwareProfileException;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactoryInterface;
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
            $this->checkConnection();

            $information['environmentInformation'] = $this->getEnvironmentInformation();
        } catch (ShopwareHttpException $e) {
            $information['requestStatus'] = new RequestStatusStruct($e->getErrorCode(), $e->getMessage());
        }

        return $information;
    }

    private function checkConnection(): void
    {
        try {
            $result = $this->doRequest('version');
        } catch (ClientException|GuzzleRequestException $e) {
            throw MigrationException::gatewayRead('Shopware 5.5 Api SwagMigrationEnvironment');
        }

        $arrayResult = \json_decode($result->getBody()->getContents(), true);

        if (!isset($arrayResult['success']) || $arrayResult['success'] === false) {
            throw MigrationException::gatewayRead('Shopware 5.5 Api SwagMigrationEnvironment');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getEnvironmentInformation(): array
    {
        if ($this->client === null) {
            return [];
        }

        try {
            $result = $this->doRequest('SwagMigrationEnvironment');
        } catch (ClientException $e) {
            if ($e->getCode() === SymfonyResponse::HTTP_NOT_FOUND) {
                throw MigrationShopwareProfileException::pluginNotInstalled();
            }

            throw MigrationException::gatewayRead('Shopware 5.5 Api SwagMigrationEnvironment');
        } catch (GuzzleRequestException $e) {
            throw MigrationException::gatewayRead('Shopware 5.5 Api SwagMigrationEnvironment');
        }

        $arrayResult = \json_decode($result->getBody()->getContents(), true);

        if (!isset($arrayResult['data'])) {
            throw MigrationException::gatewayRead('Shopware 5.5 Api SwagMigrationEnvironment');
        }

        return $arrayResult['data'];
    }

    private function doRequest(string $endpoint): ResponseInterface
    {
        if ($this->client === null) {
            throw MigrationException::gatewayRead('Shopware 5.5 Api SwagMigrationEnvironment');
        }

        try {
            return $this->client->get($endpoint);
        } catch (ClientException $e) {
            if ($e->getCode() === SymfonyResponse::HTTP_UNAUTHORIZED) {
                throw MigrationException::invalidConnectionAuthentication($endpoint);
            }

            throw $e;
        } catch (GuzzleRequestException $e) {
            $response = $e->getResponse();
            if ($response !== null && \mb_strpos($response->getBody()->getContents(), 'SSL required')) {
                throw MigrationException::sslRequired();
            }

            if (isset($e->getHandlerContext()['errno']) && $e->getHandlerContext()['errno'] === 60) {
                throw MigrationException::requestCertificateInvalid($e->getHandlerContext()['url']);
            }

            throw $e;
        } catch (ConnectException $e) {
            throw MigrationException::gatewayRead('Shopware 5.5 Api SwagMigrationEnvironment');
        }
    }
}
