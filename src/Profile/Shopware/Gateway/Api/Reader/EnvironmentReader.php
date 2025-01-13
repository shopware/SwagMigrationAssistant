<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use Psr\Http\Message\ResponseInterface;
use Shopware\Core\Framework\Log\Package;
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
        $information = [
            'environmentInformation' => [],
            'requestStatus' => new RequestStatusStruct(),
        ];

        try {
            $this->client = $this->connectionFactory->createApiClient($migrationContext);
            $information['environmentInformation'] = $this->getEnvironmentInformation();
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
    private function getEnvironmentInformation(): array
    {
        try {
            $data = $this->getEnvironmentData();
        } catch (ClientException $e) {
            if ($e->getCode() === SymfonyResponse::HTTP_NOT_FOUND) {
                $this->checkVersion();

                throw MigrationShopwareProfileException::pluginNotInstalled();
            }

            throw $e;
        }

        return $data;
    }

    private function checkVersion(): void
    {
        $result = $this->doRequest('version');

        $arrayResult = \json_decode($result->getBody()->getContents(), true);

        if (!isset($arrayResult['success']) || $arrayResult['success'] === false) {
            throw MigrationException::apiConnectionError('The version endpoint did not return success');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getEnvironmentData(): array
    {
        $result = $this->doRequest('SwagMigrationEnvironment');

        $arrayResult = \json_decode($result->getBody()->getContents(), true);

        if (!isset($arrayResult['data'])) {
            throw MigrationException::apiConnectionError('The environment endpoint did not return data');
        }

        return $arrayResult['data'];
    }

    private function doRequest(string $endpoint): ResponseInterface
    {
        if ($this->client === null) {
            throw MigrationException::apiConnectionError(
                'Could not create API client. Could be due to empty credentials or invalid connection.'
            );
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
        }
    }
}
