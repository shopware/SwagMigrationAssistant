<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Validator;

interface ValidatorRegistryInterface
{
    /**
     * Returns the validator which supports the given entity
     */
    public function getValidator(string $entityName): ValidatorInterface;
}
