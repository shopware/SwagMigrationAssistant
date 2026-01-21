<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\ErrorResolution;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Exception\MigrationException;

/**
 * @final
 */
#[Package('fundamentals@after-sales')]
readonly class MigrationFix
{
    private const PATH_SEPARATOR = '.';

    public function __construct(
        public string $id,
        public string $value,
        public string $path,
    ) {
    }

    /**
     * @param array<string, string> $data
     */
    public static function fromDatabaseQuery(array $data): self
    {
        $expectedArrayKeys = ['id', 'value', 'path'];
        foreach ($expectedArrayKeys as $expectedKey) {
            if (!\array_key_exists($expectedKey, $data)) {
                throw MigrationException::couldNotConvertFix($expectedKey);
            }
        }

        return new self(
            Uuid::fromBytesToHex($data['id']),
            $data['value'],
            $data['path'],
        );
    }

    /**
     * @param array<string|int, mixed> $item
     */
    public function apply(array &$item): void
    {
        $pathArray = explode(self::PATH_SEPARATOR, $this->path);
        $decodedValue = \json_decode($this->value, true, 512, \JSON_THROW_ON_ERROR);

        $this->applyToPath($item, $pathArray, $decodedValue);
    }

    /**
     * Recursively applies the fix value to the specified path.
     * When encountering a list (numerically-indexed array), applies the fix to all items.
     *
     * @param array<string|int, mixed> $data
     * @param array<int, string> $remainingPath
     */
    private function applyToPath(array &$data, array $remainingPath, mixed $value): void
    {
        if (empty($remainingPath)) {
            return;
        }

        $key = \array_shift($remainingPath);

        // last segment of the path, "normal" set operation
        if (empty($remainingPath)) {
            $data[$key] = $value;

            return;
        }

        // key points to a list, apply to all items in the list
        if (isset($data[$key]) && \is_array($data[$key]) && \array_is_list($data[$key])) {
            foreach ($data[$key] as &$arrayItem) {
                if (\is_array($arrayItem)) {
                    $this->applyToPath($arrayItem, $remainingPath, $value);
                }
            }

            return;
        }

        if (!isset($data[$key]) || !\is_array($data[$key])) {
            $data[$key] = [];
        }

        $this->applyToPath($data[$key], $remainingPath, $value);
    }
}
