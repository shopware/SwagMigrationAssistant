<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Converter;

use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

abstract class NewsletterRecipientConverter extends ShopwareConverter
{
    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::NEWSLETTER_RECIPIENT,
            $data['id'],
            $converted['id']
        );

        $converted['languageId'] = $this->getMappingIdFacade(DefaultEntities::LANGUAGE, $data['languageId']);
        $converted['salesChannelId'] = $this->getMappingIdFacade(DefaultEntities::SALES_CHANNEL, $data['salesChannelId']);
        $converted['salutationId'] = $this->getMappingIdFacade(DefaultEntities::SALUTATION, $data['salutationId']);

        return new ConvertStruct($converted, null, $this->mainMapping['id']);
    }
}
