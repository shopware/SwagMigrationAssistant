<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Mock\Migration\Mapping;

use Shopware\Core\Framework\Context;
use SwagMigrationNext\Migration\Mapping\MappingServiceInterface;

class DummyMappingService implements MappingServiceInterface
{
    public function readExistingMappings(Context $context): void
    {
    }

    public function createNewUuid(string $entityName, string $oldId): string
    {
        return '';
    }

    public function writeMapping(Context $context): void
    {
    }

    public function setProfile(string $profileName): void
    {
    }

    public function getUuid(string $entityName, string $oldId): ?string
    {
        return null;
    }
}
