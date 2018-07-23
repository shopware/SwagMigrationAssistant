<?php declare(strict_types=1);


namespace SwagMigrationNext\Profile\Shopware55\Converter;

use Ramsey\Uuid\Uuid;
use Shopware\Core\Content\Product\Aggregate\ProductPriceRule\ProductPriceRuleDefinition;
use Shopware\Core\Defaults;
use SwagMigrationNext\Profile\Shopware55\ConvertStruct;

class PriceConverter implements ConverterInterface
{
    public function supports(): string
    {
        return ProductPriceRuleDefinition::getEntityName();
    }

    public function convert(array $data, array $additionalRelationData = []): ConvertStruct
    {
        // Todo: Add a customer group and create a price rule for this
        $uuid = Uuid::uuid4()->getHex();
        $oldId = $data['id'];

        $converted['id'] = $uuid;
        unset($data['id']);

        $converted['productId'] = Uuid::fromString($additionalRelationData['product'][$data['articledetailsID']])->getHex();
        unset($data['articledetailsID']);

        // Todo: Create rule before creating the price rule
        $converted['ruleId'] = Uuid::fromString('08564DCEBFD04381A8833B9D1B239810')->getHex();

        // Todo: Create currency before creating the price rule
        $converted['currencyId'] = Defaults::CURRENCY;

        $converted['price'] = [
            'gross' => (float) $data['price'],
            'net' => (float) $data['price'],
        ];
        unset($data['price']);

        $converted['quantityStart'] = (int) $data['from'];
        unset($data['from']);

        $converted['quantityEnd'] = ($data['to'] === 'beliebig') ? null : (int) $data['to'];
        unset($data['to']);

       return new ConvertStruct($converted, $data, $oldId, $uuid);
    }
}