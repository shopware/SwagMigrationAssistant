<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Gateway\Api\Reader;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Shopware\Core\Framework\ShopwareHttpException;
use SwagMigrationNext\Exception\GatewayReadException;
use SwagMigrationNext\Exception\InvalidConnectionAuthenticationException;
use SwagMigrationNext\Exception\RequestCertificateInvalidException;
use SwagMigrationNext\Profile\Shopware55\Exception\PluginNotInstalledException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class Shopware55ApiEnvironmentReader extends Shopware55ApiReader
{
    public function read(): array
    {
        $information = [
            'environmentInformation' => [],
            'warning' => [
                'code' => '',
                'detail' => 'No warning.',
            ],
            'error' => [
                'code' => '',
                'detail' => 'No error.',
            ],
        ];

        if ($this->doSecureCheck($information)) {
            return $information;
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
        try {
            $information['environmentInformation'] = $this->readData($this->client, true);

            return true;
        } catch (ShopwareHttpException $eVerified) {
            $information['warning']['code'] = $eVerified->getErrorCode();
            $information['warning']['detail'] = $eVerified->getMessage();

            return false;
        }
    }

    private function doInsecureCheck(array &$information): bool
    {
        try {
            $information['environmentInformation'] = $this->readData($this->client, false);

            return true;
        } catch (ShopwareHttpException $eUnverified) {
            return false;
        }
    }

    private function doShopwareCheck(array &$information): bool
    {
        try {
            if ($this->checkForShopware($this->client)) {
                throw new PluginNotInstalledException();
            }

            throw new GatewayReadException('Shopware 5.5 Api SwagMigrationEnvironment', 466);
        } catch (ShopwareHttpException $eOther) {
            $information['error']['code'] = $eOther->getErrorCode();
            $information['error']['detail'] = $eOther->getMessage();

            return true;
        }
    }

    /**
     * @throws GatewayReadException
     * @throws RequestCertificateInvalidException
     * @throws InvalidConnectionAuthenticationException
     */
    private function readData(Client $apiClient, bool $verified = false): array
    {
        $config = $apiClient->getConfig();
        $config = array_merge($config, [
            'verify' => $verified,
        ]);

        $result = $this->doSecureRequest(
            $apiClient,
            'SwagMigrationEnvironment',
            $config
        );

        if ($result->getStatusCode() !== SymfonyResponse::HTTP_OK) {
            throw new GatewayReadException('Shopware 5.5 Api SwagMigrationEnvironment', 466);
        }

        $arrayResult = json_decode($result->getBody()->getContents(), true);

        if (!isset($arrayResult['data'])) {
            throw new GatewayReadException('Shopware 5.5 Api SwagMigrationEnvironment', 466);
        }

        return $arrayResult['data'];
    }

    private function checkForShopware(Client $apiClient): bool
    {
        $config = $apiClient->getConfig();
        $config['verify'] = false;

        $result = $this->doSecureRequest(
            $apiClient,
            'version',
            $config
        );

        if ($result->getStatusCode() === SymfonyResponse::HTTP_NOT_FOUND) {
            return false;
        }

        if ($result->getStatusCode() !== SymfonyResponse::HTTP_OK) {
            throw new GatewayReadException('Shopware 5.5 Api SwagMigrationEnvironment', 466);
        }

        $arrayResult = json_decode($result->getBody()->getContents(), true);

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
     */
    private function doSecureRequest(Client $apiClient, string $endpoint, array $config): GuzzleResponse
    {
        try {
            /** @var GuzzleResponse $result */
            $result = $apiClient->get(
                $endpoint,
                $config
            );

            return $result;
        } catch (ClientException $e) {
            if ($e->getCode() === 401) {
                throw new InvalidConnectionAuthenticationException($endpoint);
            }

            throw new GatewayReadException('Shopware 5.5 Api SwagMigrationEnvironment', 466);
        } catch (GuzzleRequestException $e) {
            if (isset($e->getHandlerContext()['errno']) && $e->getHandlerContext()['errno'] === 60) {
                throw new RequestCertificateInvalidException($e->getHandlerContext()['url']);
            }
            throw new GatewayReadException('Shopware 5.5 Api SwagMigrationEnvironment', 466);
        }
    }
}
