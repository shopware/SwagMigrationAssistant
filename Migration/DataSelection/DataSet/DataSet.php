<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\DataSelection\DataSet;

abstract class DataSet
{
    abstract public static function getEntity(): string;

    abstract public function supports(string $profileName, string $entity): bool;
}
