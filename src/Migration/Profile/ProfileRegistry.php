<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Profile;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Exception\MigrationException;

#[Package('services-settings')]
class ProfileRegistry implements ProfileRegistryInterface
{
    /**
     * @param ProfileInterface[] $profiles
     */
    public function __construct(private readonly iterable $profiles)
    {
    }

    /**
     * @return ProfileInterface[]|iterable
     */
    public function getProfiles(): iterable
    {
        return $this->profiles;
    }

    public function getProfile(string $profileName): ProfileInterface
    {
        foreach ($this->profiles as $profile) {
            if ($profile->getName() === $profileName) {
                return $profile;
            }
        }

        throw MigrationException::profileNotFound($profileName);
    }
}
