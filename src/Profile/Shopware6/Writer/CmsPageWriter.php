<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Writer;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Writer\AbstractWriter;

#[Package('fundamentals@after-sales')]
class CmsPageWriter extends AbstractWriter
{
    public function supports(): string
    {
        return DefaultEntities::CMS_PAGE;
    }

    /**
     * @param array<mixed> $data
     *
     * @return array<string, array<EntityWriteResult>>
     */
    public function writeData(array $data, Context $context): array
    {
        // handle locked default layouts
        // locked data should not be written
        $data = \array_filter($data, static function ($value) {
            return !(isset($value['locked']) && $value['locked'] === true);
        });

        return parent::writeData($data, $context);
    }
}
