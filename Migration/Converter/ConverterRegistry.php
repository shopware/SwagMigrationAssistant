<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Converter;

use SwagMigrationNext\Exception\ConverterNotFoundException;

class ConverterRegistry implements ConverterRegistryInterface
{
    /**
     * @var ConverterInterface[]
     */
    private $converters;

    public function __construct(iterable $converters)
    {
        $this->converters = $converters;
    }

    /**
     * @throws ConverterNotFoundException
     */
    public function getConverter(string $entity): ConverterInterface
    {
        foreach ($this->converters as $converter) {
            if ($converter->supports() === $entity) {
                return $converter;
            }
        }

        throw new ConverterNotFoundException($entity);
    }
}
