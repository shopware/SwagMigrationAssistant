<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\integration\Migration\Validation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use SwagMigrationAssistant\Migration\Validation\Exception\MigrationValidationException;
use SwagMigrationAssistant\Migration\Validation\MigrationFieldValidationService;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
#[CoversClass(MigrationFieldValidationService::class)]
class MigrationFieldValidationServiceTest extends TestCase
{
    use IntegrationTestBehaviour;

    private MigrationFieldValidationService $migrationFieldValidationService;

    protected function setUp(): void
    {
        $this->migrationFieldValidationService = static::getContainer()->get(MigrationFieldValidationService::class);
    }

    public function testNotExistingEntityDefinitionSkipsValidation(): void
    {
        // Unknown entities are silently skipped
        static::expectNotToPerformAssertions();

        $this->migrationFieldValidationService->validateField(
            'unknown_entity',
            'field',
            'value',
            Context::createDefaultContext(),
        );
    }

    public function testNotExistingFieldSkipsValidation(): void
    {
        // Unknown entities are silently skipped
        static::expectNotToPerformAssertions();

        $this->migrationFieldValidationService->validateField(
            'product',
            'nonExistingField',
            'value',
            Context::createDefaultContext(),
        );
    }

    public function testValidPriceField(): void
    {
        static::expectNotToPerformAssertions();

        $this->migrationFieldValidationService->validateField(
            'product',
            'price',
            [
                [
                    'currencyId' => Defaults::CURRENCY,
                    'gross' => 100.0,
                    'net' => 84.03,
                    'linked' => true,
                ],
            ],
            Context::createDefaultContext(),
        );
    }

    public function testInvalidPriceFieldGrossType(): void
    {
        static::expectException(MigrationValidationException::class);

        $this->migrationFieldValidationService->validateField(
            'product',
            'price',
            [
                [
                    'currencyId' => Defaults::CURRENCY,
                    'gross' => 'invalid', // should be numeric
                    'net' => 84.03,
                    'linked' => true,
                ],
            ],
            Context::createDefaultContext(),
        );
    }

    public function testInvalidPriceFieldMissingNet(): void
    {
        static::expectException(MigrationValidationException::class);

        $this->migrationFieldValidationService->validateField(
            'product',
            'price',
            [
                [
                    'currencyId' => Defaults::CURRENCY,
                    'gross' => 100.0,
                    // 'net' is missing
                    'linked' => true,
                ],
            ],
            Context::createDefaultContext(),
        );
    }

    public function testInvalidPriceFieldCurrencyId(): void
    {
        static::expectException(MigrationValidationException::class);

        $this->migrationFieldValidationService->validateField(
            'product',
            'price',
            [
                [
                    'currencyId' => 'not-a-valid-uuid',
                    'gross' => 100.0,
                    'net' => 84.03,
                    'linked' => true,
                ],
            ],
            Context::createDefaultContext(),
        );
    }

    public function testResolveFieldPathSimpleField(): void
    {
        $result = $this->migrationFieldValidationService->resolveFieldPath('product', 'name');

        static::assertNotNull($result);
        static::assertCount(2, $result);
        static::assertSame('product', $result[0]->getEntityName());
        static::assertSame('name', $result[1]->getPropertyName());
    }

    public function testResolveFieldPathNestedField(): void
    {
        $result = $this->migrationFieldValidationService->resolveFieldPath('shipping_method', 'prices.shippingMethodId');

        static::assertNotNull($result);
        static::assertCount(2, $result);
        static::assertSame('shipping_method_price', $result[0]->getEntityName());
        static::assertSame('shippingMethodId', $result[1]->getPropertyName());
    }

    public function testResolveFieldPathDeeplyNested(): void
    {
        $result = $this->migrationFieldValidationService->resolveFieldPath('product', 'categories.media.alt');

        static::assertNotNull($result);
        static::assertCount(2, $result);
        static::assertSame('media', $result[0]->getEntityName());
        static::assertSame('alt', $result[1]->getPropertyName());
    }

    public function testResolveFieldPathUnknownEntity(): void
    {
        $result = $this->migrationFieldValidationService->resolveFieldPath('unknown_entity', 'field');

        static::assertNull($result);
    }

    public function testResolveFieldPathUnknownField(): void
    {
        $result = $this->migrationFieldValidationService->resolveFieldPath('product', 'unknownField');

        static::assertNull($result);
    }

    public function testResolveFieldPathUnknownNestedField(): void
    {
        $result = $this->migrationFieldValidationService->resolveFieldPath('shipping_method', 'prices.unknownField');

        static::assertNull($result);
    }

    public function testValidateNestedField(): void
    {
        static::expectNotToPerformAssertions();

        $this->migrationFieldValidationService->validateField(
            'shipping_method',
            'prices.shippingMethodId',
            'a5d7a3b4c5d6e7f8a9b0c1d2e3f4a5b6',
            Context::createDefaultContext(),
        );
    }

    public function testValidateNestedFieldInvalid(): void
    {
        static::expectException(MigrationValidationException::class);

        $this->migrationFieldValidationService->validateField(
            'shipping_method',
            'prices.shippingMethodId',
            'not-a-valid-uuid',
            Context::createDefaultContext(),
        );
    }
}
