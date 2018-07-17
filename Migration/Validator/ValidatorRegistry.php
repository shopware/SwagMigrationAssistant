<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Validator;

use IteratorAggregate;

class ValidatorRegistry implements ValidatorRegistryInterface
{
    /**
     * @var ValidatorInterface[]
     */
    private $validators;

    /**
     * @param IteratorAggregate $validators
     */
    public function __construct(IteratorAggregate $validators)
    {
        $this->validators = $validators;
    }

    /**
     * @param string $entityName
     * @return ValidatorInterface
     * @throws ValidatorNotFoundException
     */
    public function getValidator(string $entityName): ValidatorInterface
    {
        foreach ($this->validators as $validator) {
            if ($validator->supports() === $entityName) {
                return $validator;
            }
        }

        throw new ValidatorNotFoundException($entityName);
    }
}