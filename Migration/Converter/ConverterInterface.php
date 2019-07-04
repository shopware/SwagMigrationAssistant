<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Converter;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

interface ConverterInterface
{
    /**
     * Delivers the supported entity name of the converter implementation
     */
    public function getSupportedEntityName(): string;

    /**
     * Delivers the supported profile name of the converter implementation
     */
    public function getSupportedProfileName(): string;

    /**
     * Identifier which internal entity this converter supports
     */
    public function supports(MigrationContextInterface $migrationContext): bool;

    /**
     * Converts the given data into the internal structure
     */
    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct;

    public function writeMapping(Context $context): void;
}
