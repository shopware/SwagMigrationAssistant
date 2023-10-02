<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Mock;

use Shopware\Core\Framework\Log\Package;

#[Package('services-settings')]
class DummyCollection implements \IteratorAggregate
{
    public function __construct(private readonly array $data)
    {
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->data);
    }
}
