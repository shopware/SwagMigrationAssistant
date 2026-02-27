<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace integration\Migration\ErrorResolution;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CalculatedPriceField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CartPriceField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CashRoundingConfigField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Field;
use Shopware\Core\Framework\DataAbstractionLayer\Field\PriceField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TaxFreeConfigField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\VariantListingConfigField;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\ErrorResolution\MigrationFieldExampleGenerator;
use SwagMigrationAssistant\Migration\Validation\MigrationFieldValidationService;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
class MigrationFieldExampleGeneratorTest extends TestCase
{
    use IntegrationTestBehaviour;

    #[DataProvider('exampleFieldProvider')]
    public function testGeneratedComplexExamplesShouldBeValidForSerializer(
        Field $field,
        string $entityName,
        string $fieldName,
        ?callable $transform,
    ): void {
        $validator = static::getContainer()->get(MigrationFieldValidationService::class);

        $example = MigrationFieldExampleGenerator::generateExample($field);
        static::assertNotNull($example);

        $decoded = \json_decode($example, true);
        static::assertIsArray($decoded);

        if ($transform !== null) {
            $decoded = $transform($decoded);
        }

        $validator->validateField(
            $entityName,
            $fieldName,
            $decoded,
            Context::createDefaultContext(),
        );
    }

    public static function exampleFieldProvider(): \Generator
    {
        yield 'PriceField' => [
            'field' => new PriceField('test', 'test'),
            'entityName' => 'product',
            'fieldName' => 'price',
            'transform' => fn (mixed $example) => [[
                ...$example[0],
                'currencyId' => Defaults::CURRENCY,
            ]],
        ];

        yield 'VariantListingConfigField' => [
            'field' => new VariantListingConfigField('test', 'test'),
            'entityName' => 'product',
            'fieldName' => 'variantListingConfig',
            'transform' => fn (mixed $example) => [
                ...$example,
                'mainVariantId' => Uuid::randomHex(),
            ],
        ];

        yield 'CartPriceField' => [
            'field' => new CartPriceField('test', 'test'),
            'entityName' => 'order',
            'fieldName' => 'price',
            'transform' => null,
        ];

        yield 'CalculatedPriceField' => [
            'field' => new CalculatedPriceField('test', 'test'),
            'entityName' => 'order',
            'fieldName' => 'shippingCosts',
            'transform' => null,
        ];

        yield 'CashRoundingConfigField' => [
            'field' => new CashRoundingConfigField('test', 'test'),
            'entityName' => 'currency',
            'fieldName' => 'itemRounding',
            'transform' => null,
        ];

        yield 'TaxFreeConfigField' => [
            'field' => new TaxFreeConfigField('test', 'test'),
            'entityName' => 'country',
            'fieldName' => 'customerTax',
            'transform' => fn (mixed $example) => [
                ...$example,
                'currencyId' => Defaults::CURRENCY,
            ],
        ];
    }
}
