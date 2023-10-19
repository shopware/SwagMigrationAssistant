<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Profile;

use Shopware\Core\Framework\Log\Package;

#[Package('services-settings')]
interface ProfileRegistryInterface
{
    /**
     * @return ProfileInterface[]|iterable
     */
    public function getProfiles(): iterable;

    /**
     * Returns the profile with the given profile name
     */
    public function getProfile(string $profileName): ProfileInterface;
}
