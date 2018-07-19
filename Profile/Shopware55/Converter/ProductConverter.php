<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Converter;

use Ramsey\Uuid\Uuid;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Content\Rule\RuleStruct;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Rule\Container\AndRule;
use SwagMigrationNext\Profile\Shopware55\ConvertStruct;

class ProductConverter implements ConverterInterface
{
    public function supports(): string
    {
        return ProductDefinition::getEntityName();
    }

    public function convert(array $data): ConvertStruct
    {
        $product_uuid = Uuid::uuid4()->getHex();

        $converted['id'] = $product_uuid;
        $converted['tendantId'] = Uuid::fromString('FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF')->getHex();

        $converted['name'] = $data['name'];
        unset($data['name']);

        if (!empty($data['supplierID'])) {
            $converted['manufacturer']['id'] = Uuid::uuid4()->getHex();
            $converted['manufacturer']['name'] = $data['supplier.name'];
        }

        if (!empty($data['taxID'])) {
            $converted['tax']['id'] = Uuid::uuid4()->getHex();
            $converted['tax']['rate'] = $data['tax.rate'];
            $converted['tax']['name'] = $data['tax.name'];
        }

        if (!empty($data['prices'])) {
            foreach ($data['prices'] as $price) {
                $converted['price'] = [
                    'gross' => (float) $price['price'],
                    'net' => (float) $price['price'],
                ];

                // Todo: Add a customer group and create a price rule for this
                $converted['priceRules'][] = [
                    'tendantId' => Uuid::fromString('FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF')->getHex(),
                    'id' => Uuid::uuid4()->getHex(),
                    // Todo: Create rule before creating the price rule
                    'ruleId' => Uuid::fromString('FD2816FCCA184FB18581CFC4EC367B2D')->getHex(),
                    // Todo: Create currency before creating the price rule
                    'currencyId' => Defaults::CURRENCY,

                    'price' => [
                        'gross' => (float) $price['price'],
                        'net' => (float) $price['price'],
                    ],
                    'quantityStart' => (int) $price['from'],
                    'quantityEnd' => ($price['to'] === 'beliebig') ? null : (int) $price['to'],
                ];
            }
        }

        return new ConvertStruct($converted, $data);
    }
}
