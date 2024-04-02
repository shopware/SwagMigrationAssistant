<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Profile;

use Shopware\Core\Framework\Log\Package;

#[Package('services-settings')]
interface ProfileInterface
{
    /**
     * Identifier for the profile
     */
    public function getName(): string;

    public function getSourceSystemName(): string;

    public function getVersion(): string;

    public function getAuthorName(): string;

    public function getIconPath(): string;
}
