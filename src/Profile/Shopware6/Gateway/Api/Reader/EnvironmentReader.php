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
    private ?HttpClientInterface $client;

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

        if ($this->doSecureCheck($information)) {
            return $information;
        }

        if (isset($information['requestStatus'])) {
            $requestStatus = $information['requestStatus'];
            \assert($requestStatus instanceof RequestStatusStruct);

            if ($requestStatus->getCode() === MigrationException::sslRequired()->getErrorCode()) {
                $requestStatus->setIsWarning(false);

                return $information;
            }
        }

        if ($this->doInsecureCheck($information)) {
            return $information;
        }

        return $information;
    }

    /**
     * @param ReadArray $information
     */
    private function doSecureCheck(array &$information): bool
    {
        try {
            $information['environmentInformation'] = $this->readData(true);

            return true;
        } catch (ShopwareHttpException $eVerified) {
            $information['requestStatus'] = new RequestStatusStruct($eVerified->getErrorCode(), $eVerified->getMessage(), false);

            return false;
        }
    }

    /**
     * @param ReadArray $information
     */
    private function doInsecureCheck(array &$information): bool
    {
        try {
            $information['environmentInformation'] = $this->readData();

            return true;
        } catch (ShopwareHttpException $eUnverified) {
            $information['requestStatus'] = new RequestStatusStruct($eUnverified->getErrorCode(), $eUnverified->getMessage(), false);

            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function readData(bool $verified = false): array
    {
        if ($this->client === null) {
            return [];
        }

        try {
            $result = $this->client->get(
                'get-environment',
                [
                    'verify' => $verified,
                ]
            );

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
