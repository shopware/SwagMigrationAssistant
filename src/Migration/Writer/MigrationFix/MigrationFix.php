<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Writer\MigrationFix;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Exception\MigrationException;

#[Package('after-sales')]
class MigrationFix
{
    private const PATH_SEPERATOR = '.';

    public function __construct(
        public readonly string $id,
        public readonly string $connectionId,
        public readonly string $mainMappingId,
        public readonly string $value,
        public readonly string $path,
    ) {
    }

    /**
     * @param array<string, string> $data
     */
    public static function fromDatabaseQuery(array $data): self
    {
        $expectedArrayKeys = ['id', 'connection_id', 'main_mapping_id', 'value', 'path'];
        foreach ($expectedArrayKeys as $expectedKey) {
            \assert(\array_key_exists($expectedKey, $data), MigrationException::couldNotConvertFix($expectedKey));
        }

        return new self(
            $data['id'],
            $data['connection_id'],
            $data['main_mapping_id'],
            $data['value'],
            $data['path'],
        );
    }

    /**
     * @param array<string|int, mixed> $item
     */
    public function apply(array &$item): void
    {
        $pathArray = explode(self::PATH_SEPERATOR, $this->path);

        $temp = &$item;
        foreach ($pathArray as $key) {
            $temp = &$temp[$key];
        }

        $temp = \json_decode($this->value, true, 512, \JSON_THROW_ON_ERROR);
        unset($temp);
    }
}
