<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware63\Converter;

use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware6\Converter\SnippetConverter;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\SnippetDataSet;
use SwagMigrationAssistant\Profile\Shopware63\Shopware63Profile;

class Shopware63SnippetConverter extends SnippetConverter
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware63Profile::PROFILE_NAME
            && $this->getDataSetEntity($migrationContext) === SnippetDataSet::getEntity();
    }
}
