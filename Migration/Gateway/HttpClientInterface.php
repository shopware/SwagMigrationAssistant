<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Gateway;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use Shopware\Core\Framework\Log\Package;

/**
 * wrapper around the guzzle client to allow for easy replacement in different situations (like auth or unauthenticated)
 * and mocking
 */
#[Package('services-settings')]
interface HttpClientInterface
{
    /**
     * @param array<string, mixed> $options
     */
    public function get(string $uri, array $options = []): ResponseInterface;

    /**
     * should return a promise constructed by the guzzle client's getAsync method
     *
     * @param array<string, mixed> $options
     */
    public function getAsync(string $uri, array $options = []): PromiseInterface;
}
