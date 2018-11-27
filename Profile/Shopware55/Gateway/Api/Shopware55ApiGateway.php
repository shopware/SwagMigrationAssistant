<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Gateway\Api;

use GuzzleHttp\Client;
use SwagMigrationNext\Migration\Gateway\AbstractGateway;
use SwagMigrationNext\Profile\Shopware55\Gateway\Api\Reader\Shopware55ApiEnvironmentReader;
use SwagMigrationNext\Profile\Shopware55\Gateway\Api\Reader\Shopware55ApiReader;

class Shopware55ApiGateway extends AbstractGateway
{
    public const GATEWAY_TYPE = 'api';

    public function read(): array
    {
        $reader = new Shopware55ApiReader($this->getClient(), $this->migrationContext);

        return $reader->read();
    }

    public function readEnvironmentInformation(): array
    {
        $reader = new Shopware55ApiEnvironmentReader($this->getClient(), $this->migrationContext);

        return $reader->read();
    }

    private function getClient(): Client
    {
        $credentials = $this->migrationContext->getCredentials();

        $options = [
            'base_uri' => $credentials['endpoint'] . '/api/',
            'auth' => [$credentials['apiUser'], $credentials['apiKey'], 'digest'],
            'verify' => false,
        ];

        return new Client($options);
    }
}
