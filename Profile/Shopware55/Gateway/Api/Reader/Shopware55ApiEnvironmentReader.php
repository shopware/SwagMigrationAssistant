<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Gateway\Api\Reader;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use SwagMigrationNext\Exception\GatewayReadException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class Shopware55ApiEnvironmentReader extends Shopware55ApiReader
{
    public function read(): array
    {
        $information = [
            'environmentInformation' => [],
            'warning' => [
                'code' => -1,
                'detail' => 'No warning.',
            ],
            'error' => [
                'code' => -1,
                'detail' => 'No error.',
            ],
        ];

        try {
            $information['environmentInformation'] = $this->readData($this->client, true);
        } catch (Exception $e) {
            try {
                $information['environmentInformation'] = $this->readData($this->client);
                $information['warning']['code'] = $e->getCode();
                $information['warning']['detail'] = $e->getMessage();
            } catch (Exception $e) {
                $information['error']['code'] = $e->getCode();
                $information['error']['detail'] = $e->getMessage();
            }
        }

        return $information;
    }

    /**
     * @throws GatewayReadException
     */
    private function readData(Client $apiClient, bool $verified = false): array
    {
        $config = $apiClient->getConfig();

        if ($verified) {
            $config = array_merge($config, [
                'verify' => $verified,
            ]);
        }

        /** @var GuzzleResponse $result */
        $result = $apiClient->get(
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
}
