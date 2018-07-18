<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Converter;

use IteratorAggregate;

class ConverterRegistry implements ConverterRegistryInterface
{
    /**
     * @var ConverterInterface[]
     */
    private $converters;

    public function __construct(IteratorAggregate $converters)
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
