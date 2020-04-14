<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Profile;

use SwagMigrationAssistant\Exception\ProfileNotFoundException;

class ProfileRegistry implements ProfileRegistryInterface
{
    /**
     * @var ProfileInterface[]
     */
    private $profiles;

    /**
     * @param ProfileInterface[] $profiles
     */
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
