<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Converter;

use Shopware\Core\Framework\Context;

interface ConverterInterface
{
    /**
     * Identifier which internal entity this converter supports
     */
    public function supports(): string;

    /**
     * Converts the given data into the internal structure
     */
    public function convert(array $data, Context $context, string $runId, string $profileId, ?string $catalogId = null, ?string $salesChannelId = null): ConvertStruct;

    public function writeMapping(Context $context): void;
}
