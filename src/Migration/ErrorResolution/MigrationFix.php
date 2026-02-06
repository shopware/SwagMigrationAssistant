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
     *
     * @throws MigrationException
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
        /**
         * Explode the path to an array
         * Path example: 'category.language.name'
         * Results in an array like: ['category', 'language', 'name']
         */
        $pathArray = explode(self::PATH_SEPARATOR, $this->path);
        $decodedValue = \json_decode($this->value, true, 512, \JSON_THROW_ON_ERROR);

        $this->applyToPath($item, $pathArray, $decodedValue);
    }

    /**
     * Recursively applies the fix value to the specified path.
     * When encountering a list (numerically-indexed array), applies the fix to all items.
     *
     * @param array<string|int, mixed> $data
     * @param array<int, string> $path
     */
    private function applyToPath(array &$data, array $path, mixed $value): void
    {
        if (empty($path)) {
            return;
        }

        $nextSegment = \array_shift($path);

        // last segment of the path, "normal" set operation
        if (empty($path)) {
            $data[$nextSegment] = $value;

            return;
        }

        $nextSegmentIsList = isset($data[$nextSegment])
            && \is_array($data[$nextSegment])
            && \array_is_list($data[$nextSegment]);

        if ($nextSegmentIsList) {
            foreach ($data[$nextSegment] as &$arrayItem) {
                if (\is_array($arrayItem)) {
                    $this->applyToPath($arrayItem, $path, $value);
                }
            }

            return;
        }

        if (!isset($data[$nextSegment]) || !\is_array($data[$nextSegment])) {
            $data[$nextSegment] = [];
        }

        $this->applyToPath($data[$nextSegment], $path, $value);
    }
}
