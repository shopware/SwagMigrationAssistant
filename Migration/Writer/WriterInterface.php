<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Writer;

use Shopware\Core\Framework\Context;

interface WriterInterface
{
    /**
     * Identifier which internal entity this writer supports
     */
    public function supports(): string;

    /**
     * Writes the converted data of the supported entity type into the database
     */
    public function writeData(array $data, Context $context): void;
}
