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
class PlainStrategyResolver implements StrategyResolverInterface
{
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
        $path = $this->normalize($path);
        $path = \ltrim($path, '/');
        $pathInfo = \pathinfo($path);

        if (empty($pathInfo['extension'])) {
            return '';
        }

        \preg_match('/.*((media\/(?:archive|image|model|music|pdf|temp|unknown|video|vector)(?:\/thumbnail)?).*\/((.+)\.(.+)))/', $path, $matches);

        if (!empty($matches)) {
            $path = $matches[2] . '/' . $matches[3];
            if (\preg_match('/.*(_[\d]+x[\d]+(@2x)?).(?:.*)$/', $path) && \mb_strpos($matches[2], '/thumbnail') === false) {
                $path = $matches[2] . '/thumbnail/' . $matches[3];
            }

            return \rtrim($installationRoot) . '/' . $path;
        }

        return \rtrim($installationRoot) . '/' . $path;
    }

    private function normalize(string $path): string
    {
        // remove filesystem directories
        $path = \str_replace('//', '/', $path);

        // remove everything before /media/...
        \preg_match('/.*((media\/(?:archive|image|music|pdf|temp|unknown|video|vector)(?:\/thumbnail)?).*\/((.+)\.(.+)))/', $path, $matches);

        if (!empty($matches)) {
            return $matches[2] . '/' . $matches[3];
        }

        return $path;
    }
}
