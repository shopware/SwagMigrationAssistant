<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\RateLimiter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\RateLimiter\RateLimiter;
use Shopware\Core\PlatformRequest;
use SwagMigrationAssistant\RateLimiter\MigrationApiRateLimiter;
use Symfony\Component\HttpFoundation\Request;

#[Package('fundamentals@after-sales')]
class MigrationApiRateLimiterTest extends TestCase
{
    public function testLogAccessUsesOauthTokenIdWhenAvailable(): void
    {
        $request = new Request();
        $request->attributes->set(PlatformRequest::ATTRIBUTE_OAUTH_ACCESS_TOKEN_ID, 'token-id');

        $rateLimiter = $this->createMock(RateLimiter::class);
        $rateLimiter->expects($this->once())
            ->method('ensureAccepted')
            ->with(MigrationApiRateLimiter::LOG_ACCESS, 'token-id');

        $migrationApiRateLimiter = new MigrationApiRateLimiter($rateLimiter);
        $migrationApiRateLimiter->ensureLogAccessAccepted($request);
    }

    public function testDownloadFallsBackToClientIp(): void
    {
        $request = new Request(server: ['REMOTE_ADDR' => '127.0.0.1']);

        $rateLimiter = $this->createMock(RateLimiter::class);
        $rateLimiter->expects($this->once())
            ->method('ensureAccepted')
            ->with(MigrationApiRateLimiter::DOWNLOAD, '127.0.0.1');

        $migrationApiRateLimiter = new MigrationApiRateLimiter($rateLimiter);
        $migrationApiRateLimiter->ensureDownloadAccepted($request);
    }

    public function testDownloadUsesUnknownWhenNoRequestIdentityIsAvailable(): void
    {
        $request = new Request();

        $rateLimiter = $this->createMock(RateLimiter::class);
        $rateLimiter->expects($this->once())
            ->method('ensureAccepted')
            ->with(MigrationApiRateLimiter::DOWNLOAD, 'unknown');

        $migrationApiRateLimiter = new MigrationApiRateLimiter($rateLimiter);
        $migrationApiRateLimiter->ensureDownloadAccepted($request);
    }
}
