<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Media\Strategy;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('services-settings')]
class Md5StrategyResolver implements StrategyResolverInterface
{
    private const BLACKLIST = [
        '/ad/' => '/g0/',
    ];

    public function supports(string $path, MigrationContextInterface $migrationContext): bool
    {
        return \file_exists($this->resolve($path, $migrationContext));
    }

    public function resolve(string $path, MigrationContextInterface $migrationContext): string
    {
        $connection = $migrationContext->getConnection();
        if ($connection === null) {
            return '';
        }

        $credentials = $connection->getCredentialFields();

        if ($credentials === null) {
            return '';
        }

        $installationRoot = (string) ($credentials['installationRoot'] ?? '');
        if (!$path || $this->isEncoded($path)) {
            return \rtrim($installationRoot) . '/' . $this->substringPath($path);
        }

        $path = $this->normalize($path);

        $path = \ltrim($path, '/');
        $pathElements = \explode('/', $path);
        $pathInfo = \pathinfo($path);
        $md5hash = \md5($path);

        if (empty($pathInfo['extension'])) {
            return '';
        }

        $split = \mb_str_split($md5hash, 2);

        if (empty($split)) {
            return '';
        }

        $realPath = \array_slice($split, 0, 3);
        $realPath = $pathElements[0] . '/' . $pathElements[1] . '/' . \implode('/', $realPath) . '/' . $pathInfo['basename'];

        if (!$this->hasBlacklistParts($realPath)) {
            return \rtrim($installationRoot) . '/' . $realPath;
        }

        foreach (self::BLACKLIST as $key => $value) {
            // must be called 2 times, because the second level won't be matched in the first call
            $rp = \str_replace($key, $value, $realPath);
            $realPath = \str_replace($key, $value, $rp);
        }

        return \rtrim($installationRoot) . '/' . $realPath;
    }

    private function isEncoded(string $path): bool
    {
        if ($this->hasBlacklistParts($path)) {
            return false;
        }

        return (bool) \preg_match("/.*(media\/(?:archive|image|model|music|pdf|temp|unknown|video|vector)(?:\/thumbnail)?\/(?:([0-9a-f]{2}\/[0-9a-f]{2}\/[0-9a-f]{2}\/))((.+)\.(.+)))/", $path);
    }

    private function substringPath(string $path): ?string
    {
        \preg_match("/(media\/(?:archive|image|model|music|pdf|temp|unknown|video|vector)(?:\/thumbnail)?\/.*)/", $path, $matches);

        return empty($matches) ? null : $matches[0];
    }

    private function normalize(string $path): string
    {
        // remove filesystem directories
        $path = \str_replace('//', '/', $path);

        // remove everything before /media/...
        \preg_match("/.*((media\/(?:archive|image|model|music|pdf|temp|unknown|video|vector)(?:\/thumbnail)?).*\/((.+)\.(.+)))/", $path, $matches);

        if (!empty($matches)) {
            return $matches[2] . '/' . $matches[3];
        }

        return $path;
    }

    private function hasBlacklistParts(string $path): bool
    {
        foreach (\array_keys(self::BLACKLIST) as $key) {
            if (\mb_strpos($path, $key) !== false) {
                return true;
            }
        }

        return false;
    }
}
