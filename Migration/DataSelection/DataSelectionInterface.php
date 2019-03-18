<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\DataSelection;

interface DataSelectionInterface
{
    public function supports(string $profileName, string $gatewayIdentifier): bool;

    public function getData(): DataSelectionStruct;

    /**
     * @return string[]
     */
    public function getEntityNames(): array;
}
