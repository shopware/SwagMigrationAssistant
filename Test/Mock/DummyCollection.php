<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Mock;

use ArrayIterator;
use IteratorAggregate;

class DummyCollection implements IteratorAggregate
{
    /**
     * @var array
     */
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }
}
