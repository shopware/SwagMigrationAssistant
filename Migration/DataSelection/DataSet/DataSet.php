<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\DataSelection\DataSet;

abstract class DataSet
{
    abstract public static function getEntity(): string;

    abstract public function supports(string $profileName): bool;

    public function getCountingInformation(): ?CountingInformationStruct
    {
        return null;
    }
}
