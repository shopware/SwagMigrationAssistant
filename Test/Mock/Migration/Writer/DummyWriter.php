<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Mock\Migration\Writer;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\Writer\WriterInterface;

class DummyWriter implements WriterInterface
{
    public function supports(): string
    {
        return 'dummy';
    }

    public function writeData(array $data, Context $context): ?array
    {
        return null;
    }
}
