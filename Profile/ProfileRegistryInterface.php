<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile;

interface ProfileRegistryInterface
{
    /**
     * Returns the profile with the given profile name
     *
     * @param string $profileName
     * @return ProfileInterface
     */
    public function getProfile(string $profileName): ProfileInterface;
}
