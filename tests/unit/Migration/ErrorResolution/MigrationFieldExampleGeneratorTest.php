<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\unit\Migration\ErrorResolution;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CalculatedPriceField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CartPriceField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CashRoundingConfigField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Field;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ListField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ObjectField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\PriceDefinitionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\PriceField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TaxFreeConfigField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\VariantListingConfigField;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\ErrorResolution\MigrationFieldExampleGenerator;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
#[CoversClass(MigrationFieldExampleGenerator::class)]
class MigrationFieldExampleGeneratorTest extends TestCase
{
    public function testGetFieldType(): void
    {
        static::assertSame(MigrationFieldExampleGenerator::getFieldType(new StringField('test', 'test')), 'StringField');
        static::assertSame(MigrationFieldExampleGenerator::getFieldType(new IntField('test', 'test')), 'IntField');
    }

    #[DataProvider('exampleFieldProvider')]
    public function testGenerateExample(Field $field, ?string $expected): void
    {
        $example = MigrationFieldExampleGenerator::generateExample($field);
        static::assertSame($expected, $example);
    }

    public static function exampleFieldProvider(): \Generator
    {
        yield 'IntField' => [
            'field' => new IntField('test', 'test'),
            'expected' => '0',
        ];

        yield 'FloatField' => [
            'field' => new FloatField('test', 'test'),
            'expected' => '0.1',
        ];

        yield 'StringField' => [
            'field' => new StringField('test', 'test'),
            'expected' => '"[string]"',
        ];

        yield 'IdField' => [
            'field' => new IdField('test', 'test'),
            'expected' => '"[uuid]"',
        ];

        yield 'FkField' => [
            'field' => new FkField('test', 'test', 'test'),
            'expected' => '"[uuid]"',
        ];

        yield 'DateField' => [
            'field' => new DateField('test', 'test'),
            'expected' => '"[date (Y-m-d)]"',
        ];

        yield 'DateTimeField' => [
            'field' => new DateTimeField('test', 'test'),
            'expected' => '"[datetime (Y-m-d H:i:s.v)]"',
        ];

        yield 'CustomFields' => [
            'field' => new CustomFields('test', 'test'),
            'expected' => null,
        ];

        yield 'ObjectField' => [
            'field' => new ObjectField('test', 'test'),
            'expected' => null,
        ];

        yield 'JsonField without property mapping' => [
            'field' => new JsonField('test', 'test'),
            'expected' => '[]',
        ];

        yield 'JsonField with property mapping' => [
            'field' => new JsonField('test', 'test', [new StringField('innerString', 'innerString')]),
            'expected' => \json_encode(['innerString' => '[string]'], \JSON_PRETTY_PRINT),
        ];

        yield 'ListField without field type' => [
            'field' => new ListField('test', 'test'),
            'expected' => '[]',
        ];

        yield 'ListField with field type' => [
            'field' => new ListField('test', 'test', StringField::class),
            'expected' => \json_encode(['[string]'], \JSON_PRETTY_PRINT),
        ];
    }

    #[DataProvider('specialFieldProvider')]
    public function testGenerateExampleSpecialFields(Field $field): void
    {
        $example = MigrationFieldExampleGenerator::generateExample($field);

        // not null means the special field was handled
        static::assertNotNull($example);
    }

    public static function specialFieldProvider(): \Generator
    {
        yield 'CalculatedPriceField' => [
            'field' => new CalculatedPriceField('test', 'test'),
        ];

        yield 'CartPriceField' => [
            'field' => new CartPriceField('test', 'test'),
        ];

        yield 'PriceDefinitionField' => [
            'field' => new PriceDefinitionField('test', 'test'),
        ];

        yield 'PriceField' => [
            'field' => new PriceField('test', 'test'),
        ];

        yield 'VariantListingConfigField' => [
            'field' => new VariantListingConfigField('test', 'test'),
        ];

        yield 'CashRoundingConfigField' => [
            'field' => new CashRoundingConfigField('test', 'test'),
        ];

        yield 'TaxFreeConfigField' => [
            'field' => new TaxFreeConfigField('test', 'test'),
        ];
    }
}
