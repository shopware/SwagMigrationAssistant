<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Profile;

interface ProfileRegistryInterface
{
    /**
     * Returns the profile with the given profile name
     */
    public function getProfile(string $profileName): ProfileInterface;
}
