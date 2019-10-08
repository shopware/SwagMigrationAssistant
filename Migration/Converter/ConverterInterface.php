<?php declare(strict_types=1);

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
     * Converts the given data into the internal structure
     */
    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct;

    public function writeMapping(Context $context): void;
}
