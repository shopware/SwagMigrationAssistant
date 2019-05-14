<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Mock\Migration\Writer;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\Writer\WriterInterface;

class DummyWriter implements WriterInterface
{
    public function supports(): string
    {
        return 'dummy';
    }

    public function writeData(array $data, Context $context): void
    {
    }
}
