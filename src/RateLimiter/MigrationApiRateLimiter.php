<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\RateLimiter;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\RateLimiter\RateLimiter;
use Shopware\Core\PlatformRequest;
use Symfony\Component\HttpFoundation\Request;

#[Package('fundamentals@after-sales')]
class MigrationApiRateLimiter
{
    final public const LOG_ACCESS = 'swag_migration_log_access';

    final public const DOWNLOAD = 'swag_migration_download';

    public function __construct(
        private readonly RateLimiter $rateLimiter,
    ) {
    }

    public function ensureAccepted(string $route, Request $request): void
    {
        $this->rateLimiter->ensureAccepted($route, $this->resolveKey($request));
    }

    private function resolveKey(Request $request): string
    {
        return $request->attributes->getString(PlatformRequest::ATTRIBUTE_OAUTH_ACCESS_TOKEN_ID)
            ?: $request->getClientIp()
            ?: 'unknown';
    }
}
