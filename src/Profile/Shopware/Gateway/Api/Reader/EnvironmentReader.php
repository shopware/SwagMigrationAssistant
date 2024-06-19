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
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\ShopwareHttpException;
use SwagMigrationAssistant\Exception\GatewayReadException;
use SwagMigrationAssistant\Exception\InvalidConnectionAuthenticationException;
use SwagMigrationAssistant\Exception\RequestCertificateInvalidException;
use SwagMigrationAssistant\Exception\SslRequiredException;
use SwagMigrationAssistant\Migration\Gateway\HttpClientInterface;
use SwagMigrationAssistant\Migration\Gateway\Reader\EnvironmentReaderInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\RequestStatusStruct;
use SwagMigrationAssistant\Profile\Shopware\Exception\PluginNotInstalledException;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactoryInterface;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * @phpstan-type ReadArray array{environmentInformation: array<string, mixed>, requestStatus: RequestStatusStruct}
 */
#[Package('services-settings')]
class EnvironmentReader implements EnvironmentReaderInterface
{
    private ?HttpClientInterface $client;

    private MigrationContextInterface $migrationContext;

    public function __construct(private readonly ConnectionFactoryInterface $connectionFactory)
    {
    }

    /**
     * @return ReadArray
     */
    public function read(MigrationContextInterface $migrationContext): array
    {
        $this->migrationContext = $migrationContext;
        $this->client = $this->connectionFactory->createApiClient($migrationContext);

        $information = [
            'environmentInformation' => [],
            'requestStatus' => new RequestStatusStruct(),
        ];

        if ($this->client === null) {
            $information['requestStatus'] = new RequestStatusStruct('SWAG-EMPTY-CREDENTIALS', 'Empty credentials');

            return $information;
        }

        if ($this->doSecureCheck($information)) {
            return $information;
        }

        if (isset($information['requestStatus'])) {
            $requestStatus = $information['requestStatus'];
            if ($requestStatus->getCode() === (new SslRequiredException())->getErrorCode()) {
                $requestStatus->setIsWarning(false);

                return $information;
            }
        }

        if ($this->doInsecureCheck($information)) {
            return $information;
        }

        if ($this->doShopwareCheck($information)) {
            return $information;
        }

        return $information;
    }

    /**
     * @param ReadArray $information
     */
    private function doSecureCheck(array &$information): bool
    {
        if ($this->client === null) {
            return false;
        }

        try {
            $information['environmentInformation'] = $this->readData($this->client, true);

            return true;
        } catch (ShopwareHttpException $eVerified) {
            $information['requestStatus'] = new RequestStatusStruct($eVerified->getErrorCode(), $eVerified->getMessage(), true);

            return false;
        }
    }

    /**
     * @param ReadArray $information
     */
    private function doInsecureCheck(array &$information): bool
    {
        if ($this->client === null) {
            return false;
        }

        try {
            $information['environmentInformation'] = $this->readData($this->client);

            return true;
        } catch (ShopwareHttpException) {
            return false;
        }
    }

    /**
     * @param ReadArray $information
     */
    private function doShopwareCheck(array &$information): bool
    {
        if ($this->client === null) {
            return false;
        }

        try {
            if ($this->checkForShopware($this->client)) {
                throw new PluginNotInstalledException();
            }

            throw new GatewayReadException('Shopware 5.5 Api SwagMigrationEnvironment', 466);
        } catch (ShopwareHttpException $eOther) {
            $information['requestStatus'] = new RequestStatusStruct($eOther->getErrorCode(), $eOther->getMessage());

            return true;
        }
    }

    /**
     * @throws GatewayReadException
     * @throws RequestCertificateInvalidException
     * @throws InvalidConnectionAuthenticationException
     * @throws SslRequiredException
     *
     * @return array<string, mixed>
     */
    private function readData(HttpClientInterface $apiClient, bool $verified = false): array
    {
        if ($verified) {
            $apiClient = $this->connectionFactory->createApiClient($this->migrationContext, $verified);
        }

        if ($apiClient === null) {
            throw new GatewayReadException('Shopware 5.5 Api SwagMigrationEnvironment', 466);
        }

        $result = $this->doSecureRequest($apiClient, 'SwagMigrationEnvironment');

        if ($result->getStatusCode() !== SymfonyResponse::HTTP_OK) {
            throw new GatewayReadException('Shopware 5.5 Api SwagMigrationEnvironment', 466);
        }

        $arrayResult = \json_decode($result->getBody()->getContents(), true);

        if (!isset($arrayResult['data'])) {
            throw new GatewayReadException('Shopware 5.5 Api SwagMigrationEnvironment', 466);
        }

        return $arrayResult['data'];
    }

    private function checkForShopware(HttpClientInterface $apiClient): bool
    {
        $result = $this->doSecureRequest($apiClient, 'version');

        if ($result->getStatusCode() === SymfonyResponse::HTTP_NOT_FOUND) {
            return false;
        }

        if ($result->getStatusCode() !== SymfonyResponse::HTTP_OK) {
            throw new GatewayReadException('Shopware 5.5 Api SwagMigrationEnvironment', 466);
        }

        $arrayResult = \json_decode($result->getBody()->getContents(), true);

        if (!isset($arrayResult['success'])
            || (isset($arrayResult['success']) && $arrayResult['success'] === false)
        ) {
            return false;
        }

        return true;
    }

    /**
     * @throws GatewayReadException
     * @throws InvalidConnectionAuthenticationException
     * @throws RequestCertificateInvalidException
     * @throws SslRequiredException
     */
    private function doSecureRequest(HttpClientInterface $apiClient, string $endpoint): GuzzleResponse
    {
        try {
            /** @var GuzzleResponse $result */
            $result = $apiClient->get($endpoint);

            return $result;
        } catch (ClientException $e) {
            if ($e->getCode() === 401) {
                throw new InvalidConnectionAuthenticationException($endpoint);
            }

            throw new GatewayReadException('Shopware 5.5 Api SwagMigrationEnvironment', 466);
        } catch (GuzzleRequestException $e) {
            $response = $e->getResponse();
            if ($response !== null && \mb_strpos($response->getBody()->getContents(), 'SSL required')) {
                throw new SslRequiredException();
            }

            if (isset($e->getHandlerContext()['errno']) && $e->getHandlerContext()['errno'] === 60) {
                throw new RequestCertificateInvalidException($e->getHandlerContext()['url']);
            }

            throw new GatewayReadException('Shopware 5.5 Api SwagMigrationEnvironment', 466);
        } catch (ConnectException $e) {
            throw new GatewayReadException('Shopware 5.5 Api SwagMigrationEnvironment', 466);
        }
    }
}
