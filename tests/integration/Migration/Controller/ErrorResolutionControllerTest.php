<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace integration\Migration\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use SwagMigrationAssistant\Controller\ErrorResolutionController;
use SwagMigrationAssistant\Exception\MigrationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
#[CoversClass(ErrorResolutionController::class)]
class ErrorResolutionControllerTest extends TestCase
{
    use IntegrationTestBehaviour;

    private ErrorResolutionController $errorResolutionController;

    protected function setUp(): void
    {
        parent::setUp();

        $this->errorResolutionController = static::getContainer()->get(ErrorResolutionController::class);
    }

    public function testGetFieldStructureUnsetEntityName(): void
    {
        static::expectExceptionObject(MigrationException::missingRequestParameter('entityName'));

        $request = new Request([], [
            'fieldName' => 'name',
        ]);

        $this->errorResolutionController->getExampleFieldStructure($request);
    }

    public function testGetFieldStructureUnsetFieldName(): void
    {
        static::expectExceptionObject(MigrationException::missingRequestParameter('fieldName'));

        $request = new Request([], [
            'entityName' => 'product',
        ]);

        $this->errorResolutionController->getExampleFieldStructure($request);
    }

    public function testGetFieldStructureUnknownField(): void
    {
        static::expectExceptionObject(MigrationException::entityFieldNotFound('product', 'unknownField'));

        $request = new Request([], [
            'entityName' => 'product',
            'fieldName' => 'unknownField',
        ]);

        $this->errorResolutionController->getExampleFieldStructure($request);
    }

    /**
     * @param array<string, string> $expected
     */
    #[DataProvider('fieldStructureProvider')]
    public function testGetFieldStructureProduct(string $entityName, string $fieldName, array $expected): void
    {
        $request = new Request([], [
            'entityName' => $entityName,
            'fieldName' => $fieldName,
        ]);

        $response = $this->errorResolutionController->getExampleFieldStructure($request);
        $responseData = $this->jsonResponseToArray($response);

        static::assertArrayHasKey('fieldType', $responseData);
        static::assertArrayHasKey('example', $responseData);

        static::assertSame($expected['fieldType'], $responseData['fieldType']);
        static::assertSame($expected['example'], $responseData['example']);
    }

    public static function fieldStructureProvider(): \Generator
    {
        yield 'product name field' => [
            'entityName' => 'product',
            'fieldName' => 'name',
            'expected' => [
                'fieldType' => 'TranslatedField',
                'example' => '"[string]"',
            ],
        ];

        yield 'product availableStock field' => [
            'entityName' => 'product',
            'fieldName' => 'availableStock',
            'expected' => [
                'fieldType' => 'IntField',
                'example' => '0',
            ],
        ];

        yield 'product price field' => [
            'entityName' => 'product',
            'fieldName' => 'price',
            'expected' => [
                'fieldType' => 'PriceField',
                'example' => \json_encode([
                    [
                        'currencyId' => '[uuid]',
                        'gross' => 0.1,
                        'net' => 0.1,
                        'linked' => false,
                    ],
                ], \JSON_PRETTY_PRINT),
            ],
        ];

        yield 'product variant listing config' => [
            'entityName' => 'product',
            'fieldName' => 'variantListingConfig',
            'expected' => [
                'fieldType' => 'VariantListingConfigField',
                'example' => \json_encode([
                    'displayParent' => false,
                    'mainVariantId' => '[uuid]',
                    'configuratorGroupConfig' => [],
                ], \JSON_PRETTY_PRINT),
            ],
        ];
    }

    public function testValidateResolutionUnsetEntityName(): void
    {
        static::expectExceptionObject(MigrationException::missingRequestParameter('entityName'));

        $request = new Request([], [
            'fieldName' => 'name',
        ]);

        $this->errorResolutionController->validateResolution(
            $request,
            Context::createDefaultContext()
        );
    }

    public function testValidateResolutionUnsetFieldName(): void
    {
        static::expectExceptionObject(MigrationException::missingRequestParameter('fieldName'));

        $request = new Request([], [
            'entityName' => 'product',
        ]);

        $this->errorResolutionController->validateResolution(
            $request,
            Context::createDefaultContext()
        );
    }

    /**
     * @param string|list<string>|null $fieldValue
     */
    #[DataProvider('invalidResolutionProvider')]
    public function testValidateResolutionInvalidRequest(string|array|null $fieldValue): void
    {
        static::expectExceptionObject(MigrationException::missingRequestParameter('fieldValue'));

        $request = new Request([], [
            'entityName' => 'product',
            'fieldName' => 'name',
            'fieldValue' => $fieldValue,
        ]);

        $this->errorResolutionController->validateResolution(
            $request,
            Context::createDefaultContext()
        );
    }

    public static function invalidResolutionProvider(): \Generator
    {
        yield 'null' => ['fieldValue' => null];
        yield 'empty string' => ['fieldValue' => ''];
        yield 'empty array' => ['fieldValue' => []];
    }

    /**
     * @param array<string, mixed> $expected
     */
    #[DataProvider('validateResolutionProvider')]
    public function testValidateResolution(string $entityName, string $fieldName, mixed $fieldValue, array $expected): void
    {
        $request = new Request([], [
            'entityName' => $entityName,
            'fieldName' => $fieldName,
            'fieldValue' => $fieldValue,
        ]);

        $response = $this->errorResolutionController->validateResolution(
            $request,
            Context::createDefaultContext()
        );
        $data = $this->jsonResponseToArray($response);

        static::assertArrayHasKey('valid', $data);
        static::assertArrayHasKey('violations', $data);

        $violationMessages = array_map(static fn (array $violation) => $violation['message'], $data['violations']);

        static::assertSame($expected['valid'], $data['valid']);
        static::assertSame($expected['violations'], $violationMessages);
    }

    public static function validateResolutionProvider(): \Generator
    {
        yield 'valid product name' => [
            'entityName' => 'product',
            'fieldName' => 'name',
            'fieldValue' => 'Valid Product Name',
            'expected' => [
                'valid' => true,
                'violations' => [],
            ],
        ];

        yield 'invalid product stock' => [
            'entityName' => 'product',
            'fieldName' => 'stock',
            'fieldValue' => 'jhdwhawbdh',
            'expected' => [
                'valid' => false,
                'violations' => [
                    'This value should be of type int.',
                ],
            ],
        ];

        yield 'valid product active' => [
            'entityName' => 'product',
            'fieldName' => 'active',
            'fieldValue' => true,
            'expected' => [
                'valid' => true,
                'violations' => [],
            ],
        ];

        yield 'invalid product taxId' => [
            'entityName' => 'product',
            'fieldName' => 'taxId',
            'fieldValue' => 'invalid-uuid',
            'expected' => [
                'valid' => false,
                'violations' => [
                    'The string "invalid-uuid" is not a valid uuid.',
                ],
            ],
        ];

        yield 'invalid product variant config' => [
            'entityName' => 'product',
            'fieldName' => 'variantListingConfig',
            'fieldValue' => [
                'displayParent' => 'not-a-boolean',
                'mainVariantId' => 'also-not-a-uuid',
                'configuratorGroupConfig' => [],
            ],
            'expected' => [
                'valid' => false,
                'violations' => [
                    'This value should be of type boolean.',
                    'The string "also-not-a-uuid" is not a valid uuid.',
                ],
            ],
        ];

        yield 'valid product price' => [
            'entityName' => 'product',
            'fieldName' => 'price',
            'fieldValue' => [
                [
                    'currencyId' => Defaults::CURRENCY,
                    'gross' => 19.99,
                    'net' => 16.81,
                    'linked' => false,
                ],
            ],
            'expected' => [
                'valid' => true,
                'violations' => [],
            ],
        ];
    }

    /**
     * @return array<string, mixed>|list<array<string, mixed>>
     */
    private function jsonResponseToArray(?Response $response): array
    {
        static::assertNotNull($response);
        static::assertInstanceOf(JsonResponse::class, $response);

        $content = $response->getContent();
        static::assertIsNotBool($content);
        static::assertJson($content);

        $array = \json_decode($content, true);
        static::assertIsArray($array);

        return $array;
    }
}
