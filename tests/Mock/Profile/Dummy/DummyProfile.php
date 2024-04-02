<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Mock\Profile\Dummy;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Profile\ProfileInterface;

#[Package('services-settings')]
class DummyProfile implements ProfileInterface
{
    final public const PROFILE_NAME = 'dummy';

    public function getName(): string
    {
        return self::PROFILE_NAME;
    }

    public function getSourceSystemName(): string
    {
        return 'unknown';
    }

    public function getVersion(): string
    {
        return '1.0';
    }

    public function getAuthorName(): string
    {
        return 'shopware AG';
    }

    public function getIconPath(): string
    {
        return '';
    }
}
