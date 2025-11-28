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

#[Package('fundamentals@after-sales')]
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
        /*
         * Explode the path to an array
         * Path example: 'category.language.name'
         * Results in an array like: ['category', 'language', 'name']
         */
        $pathArray = explode(self::PATH_SEPARATOR, $this->path);

        /*
         * Set current item as pointer
         * Item structure for example has no valid value for name and looks like:
         *  [
         *       'someOtherKeys',
         *       ...
         *       category => [
         *           ...
         *           'language' => [
         *               ...
         *               'name' => null,
         *           ]
         *       ]
         *  ]
         */
        $nestedPointer = &$item;

        // Iterating over the path to follow them and set the nested pointer to the last key in pathArray
        // In this example the result pointer is: $item['category']['language']['name']
        foreach ($pathArray as $key) {
            $nestedPointer = &$nestedPointer[$key];
        }

        // Now set the value to the pointer like: $item['category']['language']['name'] = 'new Value'
        $nestedPointer = \json_decode($this->value, true, 512, \JSON_THROW_ON_ERROR);
        unset($nestedPointer);
    }
}
