<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Validation;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Validation\Exception\MigrationValidationException;
use Symfony\Component\Filesystem\Path;

#[Package('fundamentals@after-sales')]
final readonly class ExternalResourceValidator
{
    /**
     * @var string[]
     */
    private array $mergedAllowlist;

    /**
     * @param string[] $allowedExtensions
     * @param string[] $privateAllowedExtensions
     */
    public function __construct(
        array $allowedExtensions,
        array $privateAllowedExtensions,
    ) {
        $this->mergedAllowlist = array_values(array_unique(array_map('strtolower', array_merge($allowedExtensions, $privateAllowedExtensions))));
    }

    /**
     * Validates a relative file path from migrated data.
     *
     * When $allowedRoot is provided, the resolved path must remain within that root (path traversal check).
     * When null, only null byte, absolute path, and extension checks are performed.
     *
     * @throws MigrationValidationException
     */
    public function validatePath(string $path, ?string $allowedRoot = null): void
    {
        if (str_contains($path, "\0")) {
            throw MigrationValidationException::invalidExternalPath($path, 'null byte detected');
        }

        if (rawurldecode($path) !== $path) {
            throw MigrationValidationException::invalidExternalPath($path, 'percent-encoded characters are not allowed');
        }

        if (Path::isAbsolute($path)) {
            throw MigrationValidationException::invalidExternalPath($path, 'absolute paths are not allowed');
        }

        if ($allowedRoot) {
            $canonical = Path::canonicalize(Path::makeAbsolute($path, $allowedRoot));

            if (!Path::isBasePath(Path::canonicalize($allowedRoot), $canonical)) {
                throw MigrationValidationException::invalidExternalPath($path, 'path traversal detected');
            }
        }

        if (!Path::hasExtension($path, $this->mergedAllowlist, ignoreCase: true)) {
            throw MigrationValidationException::invalidExternalPath($path, 'file extension not allowed');
        }
    }
}
