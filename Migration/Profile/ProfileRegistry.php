<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Profile;

use SwagMigrationAssistant\Exception\ProfileNotFoundException;

class ProfileRegistry implements ProfileRegistryInterface
{
    /**
     * @var ProfileInterface[]
     */
    private $profiles;

    public function __construct(iterable $profiles)
    {
        $this->profiles = $profiles;
    }

    /**
     * @throws ProfileNotFoundException
     */
    public function getProfile(string $profileName): ProfileInterface
    {
        foreach ($this->profiles as $profile) {
            if ($profile->getName() === $profileName) {
                return $profile;
            }
        }

        throw new ProfileNotFoundException($profileName);
    }
}
