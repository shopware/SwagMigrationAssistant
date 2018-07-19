<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Converter;

use SwagMigrationNext\Profile\Shopware55\ConvertStruct;

interface ConverterInterface
{
    /**
     * Identifier which internal entity this converter supports
     */
    public function supports(): string;

    /**
     * Converts the given data into the internal structure
     */
    public function convert(array $data, array $additionalRelationData = []): ConvertStruct;
}
