<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Mock;

class DummyCollection implements \IteratorAggregate
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
        return new \ArrayIterator($this->data);
    }
}
