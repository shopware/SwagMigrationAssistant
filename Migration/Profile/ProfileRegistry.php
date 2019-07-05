<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Profile;

use SwagMigrationAssistant\Exception\ProfileNotFoundException;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

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
     * @return ProfileInterface[]|iterable
     */
    public function getProfiles(): iterable
    {
        return $this->profiles;
    }

    /**
     * @throws ProfileNotFoundException
     */
    public function getProfile(MigrationContextInterface $migrationContext): ProfileInterface
    {
        foreach ($this->profiles as $profile) {
            if ($profile->getName() === $migrationContext->getConnection()->getProfileName()) {
                return $profile;
            }
        }

        throw new ProfileNotFoundException($migrationContext->getConnection()->getProfileName());
    }
}
