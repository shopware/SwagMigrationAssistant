<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile;

use IteratorAggregate;
use SwagMigrationNext\Exception\ProfileNotFoundException;

class ProfileRegistry implements ProfileRegistryInterface
{
    /**
     * @var ProfileInterface[]
     */
    private $profiles;

    public function __construct(IteratorAggregate $profiles)
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
