<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration;

interface MigrationContextInterface
{
    public function getRunUuid(): string;

    public function getProfileId(): string;

    public function getProfileName(): string;

    public function getEntity(): string;

    public function getGateway(): string;

    public function getCredentials(): array;

    public function getGatewayIdentifier(): string;

    public function getOffset(): int;

    public function getLimit(): int;
}
