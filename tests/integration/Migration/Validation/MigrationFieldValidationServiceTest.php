<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\integration\Migration\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Validation\Exception\MigrationValidationException;
use SwagMigrationAssistant\Migration\Validation\MigrationFieldValidationService;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
class MigrationFieldValidationServiceTest extends TestCase
{
    use IntegrationTestBehaviour;

    private MigrationFieldValidationService $migrationFieldValidationService;

    protected function setUp(): void
    {
        $this->migrationFieldValidationService = static::getContainer()->get(MigrationFieldValidationService::class);
    }

    /**
     * @param class-string<\Throwable>|null $exception
     */
    #[DataProvider('validateFieldProvider')]
    public function testValidateField(
        string $entityName,
        string $fieldName,
        mixed $value,
        ?string $exception,
    ): void {
        if ($exception) {
            static::expectException($exception);
        } else {
            static::expectNotToPerformAssertions();
        }

        $this->migrationFieldValidationService->validateField(
            $entityName,
            $fieldName,
            $value,
            Context::createDefaultContext(),
        );
    }

    public static function validateFieldProvider(): \Generator
    {
        yield 'not existing entity' => [
            'entityName' => 'unknown_entity',
            'fieldName' => 'name',
            'value' => 'value',
            'exception' => null,
        ];

        yield 'not existing field' => [
            'entityName' => 'product',
            'fieldName' => 'nonExistingField',
            'value' => 'value',
            'exception' => null,
        ];

        yield 'valid string field' => [
            'entityName' => 'product',
            'fieldName' => 'name',
            'value' => 'Valid Product Name',
            'exception' => null,
        ];

        yield 'valid price field' => [
            'entityName' => 'product',
            'fieldName' => 'price',
            'value' => [
                [
                    'currencyId' => Defaults::CURRENCY,
                    'gross' => 100.0,
                    'net' => 84.03,
                    'linked' => true,
                ],
            ],
            'exception' => null,
        ];

        yield 'invalid price field (gross type)' => [
            'entityName' => 'product',
            'fieldName' => 'price',
            'value' => [
                [
                    'currencyId' => Defaults::CURRENCY,
                    'gross' => 'invalid', // should be numeric
                    'net' => 84.03,
                    'linked' => true,
                ],
            ],
            'exception' => MigrationValidationException::class,
        ];

        yield 'invalid price field (missing net)' => [
            'entityName' => 'product',
            'fieldName' => 'price',
            'value' => [
                [
                    'currencyId' => Defaults::CURRENCY,
                    'gross' => 100.0,
                    // 'net' is missing
                    'linked' => true,
                ],
            ],
            'exception' => MigrationValidationException::class,
        ];

        yield 'invalid price field (invalid currencyId)' => [
            'entityName' => 'product',
            'fieldName' => 'price',
            'value' => [
                [
                    'currencyId' => 'not-a-valid-uuid',
                    'gross' => 100.0,
                    'net' => 84.03,
                    'linked' => true,
                ],
            ],
            'exception' => MigrationValidationException::class,
        ];

        yield 'valid UUID field' => [
            'entityName' => 'product',
            'fieldName' => 'id',
            'value' => Uuid::randomHex(),
            'exception' => null,
        ];

        yield 'invalid UUID field' => [
            'entityName' => 'product',
            'fieldName' => 'id',
            'value' => 'invalid-uuid',
            'exception' => MigrationValidationException::class,
        ];

        yield 'valid nested field' => [
            'entityName' => 'shipping_method',
            'fieldName' => 'prices.shippingMethodId',
            'value' => Uuid::randomHex(),
            'exception' => null,
        ];

        yield 'invalid nested field' => [
            'entityName' => 'shipping_method',
            'fieldName' => 'prices.shippingMethodId',
            'value' => 'invalid-uuid',
            'exception' => MigrationValidationException::class,
        ];

        yield 'valid translation field' => [
            'entityName' => 'product',
            'fieldName' => 'translations',
            'value' => ['en-GB' => [], 'de-DE' => []],
            'exception' => null,
        ];

        yield 'invalid translation field (non array)' => [
            'entityName' => 'product',
            'fieldName' => 'translations',
            'value' => 'not-an-array',
            'exception' => MigrationValidationException::class,
        ];

        yield 'invalid translation field (non translations)' => [
            'entityName' => 'product',
            'fieldName' => 'translations',
            'value' => ['en-GB' => null, 'de-DE' => []],
            'exception' => MigrationValidationException::class,
        ];

        yield 'valid to many field' => [
            'entityName' => 'product',
            'fieldName' => 'tags',
            'value' => [
                ['id' => Uuid::randomHex()],
                ['id' => Uuid::randomHex()],
            ],
            'exception' => null,
        ];

        yield 'valid to many field (resolver id)' => [
            'entityName' => 'product',
            'fieldName' => 'tags',
            'value' => [
                [Uuid::randomHex() => ['resolver' => 'dummy', 'value' => 'tag-1']],
                [Uuid::randomHex() => ['resolver' => 'dummy', 'value' => 'tag-2']],
            ],
            'exception' => null,
        ];

        yield 'invalid to many field (non array)' => [
            'entityName' => 'product',
            'fieldName' => 'tags',
            'value' => 'not-an-array',
            'exception' => MigrationValidationException::class,
        ];

        yield 'invalid to many field (non array child)' => [
            'entityName' => 'product',
            'fieldName' => 'tags',
            'value' => [
                ['id' => Uuid::randomHex()],
                'not-an-array',
            ],
            'exception' => MigrationValidationException::class,
        ];

        yield 'invalid to many field (invalid uuid)' => [
            'entityName' => 'product',
            'fieldName' => 'tags',
            'value' => [
                ['id' => Uuid::randomHex()],
                ['id' => 'invalid-uuid'],
            ],
            'exception' => MigrationValidationException::class,
        ];

        yield 'valid to one field' => [
            'entityName' => 'product',
            'fieldName' => 'tax',
            'value' => [
                'id' => Uuid::randomHex(),
            ],
            'exception' => null,
        ];

        yield 'valid to one field (resolver id)' => [
            'entityName' => 'product',
            'fieldName' => 'tax',
            'value' => [
                Uuid::randomHex() => ['resolver' => 'dummy', 'value' => 'tax-1'],
            ],
            'exception' => null,
        ];

        yield 'invalid to one field (non array)' => [
            'entityName' => 'product',
            'fieldName' => 'tax',
            'value' => 'not-an-array',
            'exception' => MigrationValidationException::class,
        ];

        yield 'invalid to one field (invalid id)' => [
            'entityName' => 'product',
            'fieldName' => 'tax',
            'value' => [
                'id' => 'invalid-uuid',
            ],
            'exception' => MigrationValidationException::class,
        ];
    }

    /**
     * @param array{entityName: string, propertyName: string}|null $expectedResult
     */
    #[DataProvider('resolveFieldPathProvider')]
    public function testResolveFieldPath(
        string $entityName,
        string $fieldName,
        ?array $expectedResult,
    ): void {
        $result = $this->migrationFieldValidationService->resolveFieldPath($entityName, $fieldName);

        if ($expectedResult !== null) {
            static::assertNotNull($result);
            static::assertCount(2, $result);
            static::assertSame($expectedResult['entityName'], $result[0]->getEntityName());
            static::assertSame($expectedResult['propertyName'], $result[1]->getPropertyName());
        } else {
            static::assertNull($result);
        }
    }

    public static function resolveFieldPathProvider(): \Generator
    {
        yield 'simple field' => [
            'entityName' => 'product',
            'fieldName' => 'name',
            'expectedResult' => [
                'entityName' => 'product',
                'propertyName' => 'name',
            ],
        ];

        yield 'nested field' => [
            'entityName' => 'shipping_method',
            'fieldName' => 'prices.shippingMethodId',
            'expectedResult' => [
                'entityName' => 'shipping_method_price',
                'propertyName' => 'shippingMethodId',
            ],
        ];

        yield 'deeply nested field' => [
            'entityName' => 'product',
            'fieldName' => 'categories.media.alt',
            'expectedResult' => [
                'entityName' => 'media',
                'propertyName' => 'alt',
            ],
        ];

        yield 'unknown entity' => [
            'entityName' => 'unknown_entity',
            'fieldName' => 'field',
            'expectedResult' => null,
        ];

        yield 'unknown field' => [
            'entityName' => 'product',
            'fieldName' => 'unknownField',
            'expectedResult' => null,
        ];

        yield 'unknown nested field' => [
            'entityName' => 'shipping_method',
            'fieldName' => 'prices.unknownField',
            'expectedResult' => null,
        ];
    }
}
