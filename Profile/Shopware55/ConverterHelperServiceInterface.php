<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55;

interface ConverterHelperServiceInterface
{
    public const TYPE_STRING = 'string';
    public const TYPE_BOOLEAN = 'bool';
    public const TYPE_INTEGER = 'int';
    public const TYPE_FLOAT = 'float';

    public function convertValue(
        array &$newData,
        string $newKey,
        array &$sourceData,
        string $sourceKey,
        string $castType = self::TYPE_STRING
    ): void;
}
