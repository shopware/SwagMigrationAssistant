<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Profile;

use SwagMigrationAssistant\Migration\MigrationContextInterface;

interface ProfileRegistryInterface
{
    /**
     * @return ProfileInterface[]|iterable
     */
    public function getProfiles(): iterable;

    /**
     * Returns the profile with the given profile name
     */
    public function getProfile(MigrationContextInterface $migrationContext): ProfileInterface;
}
