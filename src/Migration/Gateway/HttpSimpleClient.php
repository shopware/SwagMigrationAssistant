<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Gateway;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use Shopware\Core\Framework\Log\Package;

#[Package('services-settings')]
class HttpSimpleClient implements HttpClientInterface
{
    protected Client $client;

    /**
     * @param array<string, mixed> $additionalOptions
     */
    public function __construct(array $additionalOptions = [])
    {
        $this->client = $this->constructClient($additionalOptions);
    }

    public function getAsync(string $uri, array $options = []): PromiseInterface
    {
        return $this->client->getAsync($uri, $options);
    }

    public function get(string $uri, array $options = []): ResponseInterface
    {
        return $this->client->get($uri, $options);
    }

    /**
     * @param array<string, mixed> $additionalOptions
     */
    protected function constructClient(array $additionalOptions = []): Client
    {
        return new Client(\array_merge([
            'verify' => false,
            'connect_timeout' => 15.0,
        ], $additionalOptions));
    }
}
