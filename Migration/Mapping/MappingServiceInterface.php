<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Mapping;

use Shopware\Core\Framework\Context;

interface MappingServiceInterface
{
    public function readExistingMappings(Context $context): void;

    public function getUuid(string $entityName, string $oldId): ?string;

    public function createNewUuid(string $entityName, string $oldId, array $additionalData = null): string;

    public function writeMapping(Context $context): void;

    public function setProfile(string $profileName): void;
}
