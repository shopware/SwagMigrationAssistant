<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\DataProvider\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;

#[Package('services-settings')]
interface EnvironmentServiceInterface
{
    /**
     * @return array<string, string|bool|array<mixed>>
     */
    public function getEnvironmentData(Context $context): array;
}
