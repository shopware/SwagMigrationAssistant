<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware63\Converter;

use SwagMigrationAssistant\Profile\Shopware6\Shopware6ProfileInterface;
use SwagMigrationAssistant\Profile\Shopware63\Shopware63Profile;
use SwagMigrationAssistant\Test\Profile\Shopware6\Converter\ShopwareConverterTest as ShopwareConverterTestBase;

abstract class ShopwareConverterTest extends ShopwareConverterTestBase
{
    protected function createProfile(): Shopware6ProfileInterface
    {
        return new Shopware63Profile();
    }

    protected function getProfileName(): string
    {
        return Shopware63Profile::PROFILE_NAME;
    }
}
