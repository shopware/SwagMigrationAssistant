<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware55\Converter;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Converter\OrderDocumentConverter;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\OrderDocumentDataSet;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

#[Package('services-settings')]
class Shopware55OrderDocumentConverter extends OrderDocumentConverter
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware55Profile::PROFILE_NAME
            && $this->getDataSetEntity($migrationContext) === OrderDocumentDataSet::getEntity();
    }

    public function getSourceIdentifier(array $data): string
    {
        if (isset($data['ID'])) {
            return $data['ID'];
        }

        return $data['id'];
    }

    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        if (isset($data['ID'])) {
            $data['id'] = $data['ID'];

            unset($data['ID']);
        }

        return parent::convert($data, $context, $migrationContext);
    }
}
