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
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Gateway\HttpClientInterface;
use SwagMigrationAssistant\Migration\Gateway\Reader\EnvironmentReaderInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\RequestStatusStruct;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactoryInterface;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

#[Package('services-settings')]
class EnvironmentReader implements EnvironmentReaderInterface
{
    private ?HttpClientInterface $client = null;

    private MigrationContextInterface $migrationContext;

    public function __construct(private readonly ConnectionFactoryInterface $connectionFactory)
    {
    }

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
            /** @var RequestStatusStruct $requestStatus */
            $requestStatus = $information['requestStatus'];
            if ($requestStatus->getCode() === MigrationException::sslRequired()->getErrorCode()) {
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

    private function doInsecureCheck(array &$information): bool
    {
        if ($this->client === null) {
            return false;
        }

        try {
            $information['environmentInformation'] = $this->readData($this->client, false);

            return true;
        } catch (ShopwareHttpException $eUnverified) {
            return false;
        }
    }

    private function doShopwareCheck(array &$information): bool
    {
        if ($this->client === null) {
            return false;
        }

        try {
            if ($this->checkForShopware($this->client)) {
                throw MigrationException::pluginNotInstalled();
            }

            throw MigrationException::gatewayRead('Shopware 5.5 Api SwagMigrationEnvironment');
        } catch (ShopwareHttpException $eOther) {
            $information['requestStatus'] = new RequestStatusStruct($eOther->getErrorCode(), $eOther->getMessage());

            return true;
        }
    }

    /**
     * @throws MigrationException
     */
    private function readData(HttpClientInterface $apiClient, bool $verified = false): array
    {
        if ($verified) {
            $apiClient = $this->connectionFactory->createApiClient($this->migrationContext, $verified);
        }

        if ($apiClient === null) {
            throw MigrationException::gatewayRead('Shopware 5.5 Api SwagMigrationEnvironment');
        }

        $result = $this->doSecureRequest($apiClient, 'SwagMigrationEnvironment');

        if ($result->getStatusCode() !== SymfonyResponse::HTTP_OK) {
            throw MigrationException::gatewayRead('Shopware 5.5 Api SwagMigrationEnvironment');
        }

        $arrayResult = \json_decode($result->getBody()->getContents(), true);

        if (!isset($arrayResult['data'])) {
            throw MigrationException::gatewayRead('Shopware 5.5 Api SwagMigrationEnvironment');
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
            throw MigrationException::gatewayRead('Shopware 5.5 Api SwagMigrationEnvironment');
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
     * @throws MigrationException
     */
    private function doSecureRequest(HttpClientInterface $apiClient, string $endpoint): GuzzleResponse
    {
        try {
            /** @var GuzzleResponse $result */
            $result = $apiClient->get($endpoint);

            return $result;
        } catch (ClientException $e) {
            if ($e->getCode() === 401) {
                throw MigrationException::invalidConnectionAuthentication($endpoint);
            }

            throw MigrationException::gatewayRead('Shopware 5.5 Api SwagMigrationEnvironment');
        } catch (GuzzleRequestException $e) {
            $response = $e->getResponse();
            if ($response !== null && \mb_strpos($response->getBody()->getContents(), 'SSL required')) {
                throw MigrationException::sslRequired();
            }

            if (isset($e->getHandlerContext()['errno']) && $e->getHandlerContext()['errno'] === 60) {
                throw MigrationException::requestCertificateInvalid($e->getHandlerContext()['url']);
            }

            throw MigrationException::gatewayRead('Shopware 5.5 Api SwagMigrationEnvironment');
        } catch (ConnectException $e) {
            throw MigrationException::gatewayRead('Shopware 5.5 Api SwagMigrationEnvironment');
        }
    }
}
