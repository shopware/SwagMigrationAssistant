<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Writer;

use Shopware\Core\Framework\Context;

interface WriterInterface
{
    public function supports(): string;

    public function writeData(array $data, Context $context): void;
}