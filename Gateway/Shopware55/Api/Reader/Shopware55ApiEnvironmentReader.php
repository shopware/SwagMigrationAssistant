<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway\Shopware55\Api\Reader;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use SwagMigrationNext\Exception\GatewayReadException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class Shopware55ApiEnvironmentReader
{
    /**
     * @var array
     */
    private $apiClientOptions;

    public function __construct(array $apiClientOptions)
    {
        $this->apiClientOptions = $apiClientOptions;
    }

    /**
     * @throws GatewayReadException
     */
    public function read(Client $apiClient): array
    {
        $verifiedOptions = $this->apiClientOptions;
        $verifiedOptions['verify'] = true;
        $apiClientVerified = new Client($verifiedOptions);

        try {
            $information = [
                'environmentInformation' => $this->getData($apiClientVerified),
                'error' => false,
            ];

            return $information;
        } catch (Exception $e) {
            $information = [
                'environmentInformation' => $this->getData($apiClient),
                'error' => [
                    'code' => $e->getCode(),
                    'detail' => $e->getMessage(),
                ],
            ];

            return $information;
        }
    }

    /**
     * @throws GatewayReadException
     */
    private function getData(Client $apiClient)
    {
        /** @var GuzzleResponse $result */
        $result = $apiClient->get(
            'SwagMigrationEnvironment'
        );

        if ($result->getStatusCode() !== SymfonyResponse::HTTP_OK) {
            throw new GatewayReadException('Shopware 5.5 Api SwagMigrationEnvironment');
        }

        $arrayResult = json_decode($result->getBody()->getContents(), true);

        return $arrayResult['data'];
    }
}
