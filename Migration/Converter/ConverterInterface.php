<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Converter;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

interface ConverterInterface
{
    /**
     * Identifier which internal entity this converter supports
     */
    public function supports(MigrationContextInterface $migrationContext): bool;

    /**
     * Get the identifier of the source data which is only known to converter
     */
    public function getSourceIdentifier(array $data): string;

    /**
     * Gets the contained media uuids from converted entity data.
     */
    public function getMediaUuids(array $converted): ?array;

    /**
     * Converts the given data into the internal structure
     */
    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct;

    public function writeMapping(Context $context): void;
}
