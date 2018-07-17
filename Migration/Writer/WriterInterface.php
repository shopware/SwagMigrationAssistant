<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Writer;

use Shopware\Core\Framework\Context;

interface WriterInterface
{
    /**
     * Identifier which internal entity this writer supports
     *
     * @return string
     */
    public function supports(): string;

    /**
     * Writes the converted data of the supported entity type into the database
     *
     * @param array $data
     * @param Context $context
     */
    public function writeData(array $data, Context $context): void;
}