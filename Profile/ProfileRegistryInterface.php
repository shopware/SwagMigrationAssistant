<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile;

interface ProfileRegistryInterface
{
    public function getProfile(string $profileName): ProfileInterface;
}
