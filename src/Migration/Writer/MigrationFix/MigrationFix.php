<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Writer\MigrationFix;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Exception\MigrationException;

#[Package('after-sales')]
class MigrationFix
{
    private const PATH_SEPARATOR = '.';

    public function __construct(
        public readonly string $id,
        public readonly string $value,
        public readonly string $path,
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

        $temp = &$item;
        foreach ($pathArray as $key) {
            $temp = &$temp[$key];
        }

        $temp = \json_decode($this->value, true, 512, \JSON_THROW_ON_ERROR);
        unset($temp);
    }
}
