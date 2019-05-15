<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware55\Gateway\Api;

use GuzzleHttp\Client;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\EnvironmentInformation;
use SwagMigrationAssistant\Migration\Gateway\GatewayInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Api\Reader\Shopware55ApiEnvironmentReader;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Api\Reader\Shopware55ApiReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

class Shopware55ApiGateway implements GatewayInterface
{
    public const GATEWAY_NAME = 'api';

    public function supports(string $gatewayIdentifier): bool
    {
        return $gatewayIdentifier === Shopware55Profile::PROFILE_NAME . self::GATEWAY_NAME;
    }

    public function read(MigrationContextInterface $migrationContext): array
    {
        $reader = new Shopware55ApiReader($this->getClient($migrationContext), $migrationContext);

        return $reader->read();
    }

    public function readEnvironmentInformation(MigrationContextInterface $migrationContext): EnvironmentInformation
    {
        $reader = new Shopware55ApiEnvironmentReader($this->getClient($migrationContext), $migrationContext);
        $environmentData = $reader->read();
        $environmentDataArray = $environmentData['environmentInformation'];

        if (empty($environmentDataArray)) {
            return new EnvironmentInformation(
                Shopware55Profile::SOURCE_SYSTEM_NAME,
                Shopware55Profile::SOURCE_SYSTEM_VERSION,
                '',
                [],
                [],
                $environmentData['warning']['code'],
                $environmentData['warning']['detail'],
                $environmentData['error']['code'],
                $environmentData['error']['detail']
            );
        }

        if (!isset($environmentDataArray['translations'])) {
            $environmentDataArray['translations'] = 0;
        }

        $updateAvailable = false;
        if (isset($environmentData['environmentInformation']['updateAvailable'])) {
            $updateAvailable = $environmentData['environmentInformation']['updateAvailable'];
        }

        $totals = [
            DefaultEntities::CATEGORY => $environmentDataArray['categories'],
            DefaultEntities::PRODUCT => $environmentDataArray['products'],
            DefaultEntities::CUSTOMER => $environmentDataArray['customers'],
            DefaultEntities::ORDER => $environmentDataArray['orders'],
            DefaultEntities::MEDIA => $environmentDataArray['assets'],
            DefaultEntities::CUSTOMER_GROUP => $environmentDataArray['customerGroups'],
            DefaultEntities::PROPERTY_GROUP_OPTION => $environmentDataArray['configuratorOptions'],
            DefaultEntities::TRANSLATION => $environmentDataArray['translations'],
            DefaultEntities::NUMBER_RANGE => $environmentDataArray['numberRanges'],
            DefaultEntities::CURRENCY => $environmentDataArray['currencies'],
        ];
        $credentials = $migrationContext->getConnection()->getCredentialFields();

        return new EnvironmentInformation(
            Shopware55Profile::SOURCE_SYSTEM_NAME,
            $environmentDataArray['shopwareVersion'],
            $credentials['endpoint'],
            $environmentDataArray['structure'],
            $totals,
            $environmentData['warning']['code'],
            $environmentData['warning']['detail'],
            $environmentData['error']['code'],
            $environmentData['error']['detail'],
            $updateAvailable
        );
    }

    private function getClient(MigrationContextInterface $migrationContext): Client
    {
        $credentials = $migrationContext->getConnection()->getCredentialFields();

        $options = [
            'base_uri' => $credentials['endpoint'] . '/api/',
            'auth' => [$credentials['apiUser'], $credentials['apiKey'], 'digest'],
            'connect_timeout' => 5.0,
            'verify' => false,
        ];

        return new Client($options);
    }
}
