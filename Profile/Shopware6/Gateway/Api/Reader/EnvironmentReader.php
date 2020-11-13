<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Shopware\Core\Framework\ShopwareHttpException;
use SwagMigrationAssistant\Exception\GatewayReadException;
use SwagMigrationAssistant\Exception\InvalidConnectionAuthenticationException;
use SwagMigrationAssistant\Exception\RequestCertificateInvalidException;
use SwagMigrationAssistant\Exception\SslRequiredException;
use SwagMigrationAssistant\Migration\Gateway\Reader\EnvironmentReaderInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\RequestStatusStruct;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Connection\AuthClient;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Connection\ConnectionFactoryInterface;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class EnvironmentReader implements EnvironmentReaderInterface
{
    /**
     * @var ConnectionFactoryInterface
     */
    private $connectionFactory;

    /**
     * @var ?AuthClient
     */
    private $client;

    public function __construct(ConnectionFactoryInterface $connectionFactory)
    {
        $this->connectionFactory = $connectionFactory;
    }

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
            /** @var RequestStatusStruct $requestStatus */
            $requestStatus = $information['requestStatus'];
            if ($requestStatus->getCode() === (new SslRequiredException())->getErrorCode()) {
                $requestStatus->setIsWarning(false);

                return $information;
            }
        }

        if ($this->doInsecureCheck($information)) {
            return $information;
        }

        return $information;
    }

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

    private function doInsecureCheck(array &$information): bool
    {
        try {
            $information['environmentInformation'] = $this->readData(false);

            return true;
        } catch (ShopwareHttpException $eUnverified) {
            $information['requestStatus'] = new RequestStatusStruct($eUnverified->getErrorCode(), $eUnverified->getMessage(), false);

            return false;
        }
    }

    private function readData(bool $verified = false): array
    {
        if ($this->client === null) {
            return [];
        }

        try {
            $result = $this->client->getRequest(
                'get-environment',
                [
                    'verify' => $verified,
                ]
            );

            if ($result->getStatusCode() !== SymfonyResponse::HTTP_OK) {
                throw new GatewayReadException('Shopware 6 API Environment Call', 466);
            }

            return \json_decode($result->getBody()->getContents(), true);
        } catch (ClientException $e) {
            if ($e->getCode() === 401) {
                throw new InvalidConnectionAuthenticationException('get-data');
            }

            throw new GatewayReadException('Shopware 6 API Environment Call', 466);
        } catch (RequestException $e) {
            if ($e->getRequest()->getUri()->getPath() === '/api/oauth/token') {
                // something went wrong with authentication.
                throw new InvalidConnectionAuthenticationException('get-data');
            }

            $response = $e->getResponse();
            if ($response !== null && \mb_strpos($response->getBody()->getContents(), 'SSL required')) {
                throw new SslRequiredException();
            }

            if (isset($e->getHandlerContext()['errno']) && $e->getHandlerContext()['errno'] === 60) {
                throw new RequestCertificateInvalidException($e->getHandlerContext()['url']);
            }

            throw new GatewayReadException('Shopware 6 API Environment Call', 466);
        }
    }
}
