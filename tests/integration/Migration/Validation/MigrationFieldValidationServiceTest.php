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
use Shopware\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Validation\WriteConstraintViolationException;
use SwagMigrationAssistant\Exception\MigrationException;
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

    public function testNotExistingEntityDefinition(): void
    {
        static::expectExceptionObject(DataAbstractionLayerException::definitionNotFound('test'));

        $this->migrationFieldValidationService->validateFieldValue(
            'test',
            'field',
            'value',
            Context::createDefaultContext(),
        );
    }

    public function testNotExistingField(): void
    {
        static::expectExceptionObject(MigrationException::entityFieldNotFound('product', 'nonExistingField'));

        $this->migrationFieldValidationService->validateFieldValue(
            'product',
            'nonExistingField',
            'value',
            Context::createDefaultContext(),
        );
    }

    public function testValidPriceField(): void
    {
        static::expectNotToPerformAssertions();

        $this->migrationFieldValidationService->validateFieldValue(
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
        static::expectException(WriteConstraintViolationException::class);

        $this->migrationFieldValidationService->validateFieldValue(
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
        static::expectException(WriteConstraintViolationException::class);

        $this->migrationFieldValidationService->validateFieldValue(
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
        static::expectException(WriteConstraintViolationException::class);

        $this->migrationFieldValidationService->validateFieldValue(
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
}
