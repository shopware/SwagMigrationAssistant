<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\DependencyInjection\CompilerPass;

use Psr\Clock\ClockInterface;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\RateLimiter\RateLimiter;
use Shopware\Core\Framework\RateLimiter\RateLimiterFactory;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use SwagMigrationAssistant\RateLimiter\MigrationApiRateLimiter;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\RateLimiter\Storage\CacheStorage;

#[Package('fundamentals@after-sales')]
class MigrationRateLimiterCompilerPass implements CompilerPassInterface
{
    /**
     * @var array<string, array{enabled: bool, policy: string, limit: int, interval: string}>
     */
    private const RATE_LIMITERS = [
        MigrationApiRateLimiter::LOG_ACCESS => [
            'enabled' => true,
            'policy' => 'sliding_window',
            'limit' => 30,
            'interval' => '60 seconds',
        ],
        MigrationApiRateLimiter::DOWNLOAD => [
            'enabled' => true,
            'policy' => 'sliding_window',
            'limit' => 10,
            'interval' => '60 seconds',
        ],
    ];

    public function process(ContainerBuilder $container): void
    {
        $rateLimiter = $container->getDefinition(RateLimiter::class);

        foreach (self::RATE_LIMITERS as $name => $config) {
            $limiterFactory = new Definition(RateLimiterFactory::class);
            $limiterFactory->addArgument($config + ['id' => $name]);

            $cacheStorage = new Definition(CacheStorage::class);
            $cacheStorage->addArgument(new Reference('cache.rate_limiter'));

            $limiterFactory->addArgument($cacheStorage);
            $limiterFactory->addArgument(new Reference(SystemConfigService::class));
            $limiterFactory->addArgument(new Reference(ClockInterface::class));
            $limiterFactory->addArgument(new Reference('lock.factory'));

            $rateLimiter->addMethodCall('registerLimiterFactory', [$name, $limiterFactory]);
        }
    }
}
