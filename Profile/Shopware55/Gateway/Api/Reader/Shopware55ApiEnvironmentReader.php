<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Gateway\Api\Reader;

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

    public function read(Client $apiClient): array
    {
        $verifiedOptions = $this->apiClientOptions;
        $verifiedOptions['verify'] = true;
        $apiClientVerified = new Client($verifiedOptions);

        try {
            $information = [
                'environmentInformation' => $this->getData($apiClientVerified),
                'warning' => [
                    'code' => -1,
                    'detail' => 'No warning.',
                ],
                'error' => [
                    'code' => -1,
                    'detail' => 'No error.',
                ],
            ];
        } catch (Exception $e) {
            try {
                $information = [
                    'environmentInformation' => $this->getData($apiClient),
                    'warning' => [
                        'code' => $e->getCode(),    // Most likely SSL not possible warning (Code 0)
                        'detail' => $e->getMessage(),
                    ],
                    'error' => [
                        'code' => -1,
                        'detail' => 'No error.',
                    ],
                ];
            } catch (Exception $e) {
                $information = [
                    'environmentInformation' => [],
                    'warning' => [
                        'code' => -1,
                        'detail' => 'No warning.',
                    ],
                    'error' => [
                        'code' => $e->getCode(),
                        'detail' => $e->getMessage(),
                    ],
                ];
            }
        }

        return $information;
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
