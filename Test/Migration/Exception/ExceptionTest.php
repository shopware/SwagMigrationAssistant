<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Exception;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\ShopwareHttpException;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Exception\ConverterNotFoundException;
use SwagMigrationAssistant\Exception\DataSetNotFoundException;
use SwagMigrationAssistant\Exception\EntityNotExistsException;
use SwagMigrationAssistant\Exception\GatewayNotFoundException;
use SwagMigrationAssistant\Exception\GatewayReadException;
use SwagMigrationAssistant\Exception\InvalidConnectionAuthenticationException;
use SwagMigrationAssistant\Exception\LocaleNotFoundException;
use SwagMigrationAssistant\Exception\MigrationContextPropertyMissingException;
use SwagMigrationAssistant\Exception\MigrationIsRunningException;
use SwagMigrationAssistant\Exception\MigrationRunUndefinedStatusException;
use SwagMigrationAssistant\Exception\MigrationWorkloadPropertyMissingException;
use SwagMigrationAssistant\Exception\ProcessorNotFoundException;
use SwagMigrationAssistant\Exception\ProfileNotFoundException;
use SwagMigrationAssistant\Exception\RequestCertificateInvalidException;
use SwagMigrationAssistant\Exception\WriterNotFoundException;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use Symfony\Component\HttpFoundation\Response;

class ExceptionTest extends TestCase
{
    /**
     * @dataProvider exceptionProvider
     *
     * @param class-string<object> $exceptionClass
     */
    public function testExceptions(ShopwareHttpException $exceptionInstance, string $exceptionClass, int $expectedStatusCode, string $expectedErrorCode): void
    {
        try {
            throw $exceptionInstance;
        } catch (ShopwareHttpException $exception) {
            static::assertInstanceOf($exceptionClass, $exception);
            static::assertSame($expectedStatusCode, $exception->getStatusCode());
            static::assertSame($expectedErrorCode, $exception->getErrorCode());
        }
    }

    public function exceptionProvider(): array
    {
        return [
            [new ConverterNotFoundException('foo'), ConverterNotFoundException::class, Response::HTTP_NOT_FOUND, 'SWAG_MIGRATION__CONVERTER_NOT_FOUND'],
            [new DataSetNotFoundException('foo'), DataSetNotFoundException::class, Response::HTTP_NOT_FOUND, 'SWAG_MIGRATION__DATASET_NOT_FOUND'],
            [new EntityNotExistsException(SwagMigrationRunEntity::class, Uuid::randomHex()), EntityNotExistsException::class, Response::HTTP_BAD_REQUEST, 'SWAG_MIGRATION__ENTITY_NOT_EXISTS'],
            [new GatewayNotFoundException('foo'), GatewayNotFoundException::class, Response::HTTP_NOT_FOUND, 'SWAG_MIGRATION__GATEWAY_NOT_FOUND'],
            [new GatewayReadException('foo'), GatewayReadException::class, Response::HTTP_NOT_FOUND, 'SWAG_MIGRATION__GATEWAY_READ'],
            [new InvalidConnectionAuthenticationException('foo'), InvalidConnectionAuthenticationException::class, Response::HTTP_BAD_REQUEST, 'SWAG_MIGRATION__CONNECTION_AUTHENTICATION_INVALID'],
            [new LocaleNotFoundException('foo'), LocaleNotFoundException::class, Response::HTTP_NOT_FOUND, 'SWAG_MIGRATION__LOCALE_NOT_FOUND'],
            [new MigrationContextPropertyMissingException('foo'), MigrationContextPropertyMissingException::class, Response::HTTP_BAD_REQUEST, 'SWAG_MIGRATION__CONTEXT_PROPERTY_MISSING'],
            [new MigrationIsRunningException(), MigrationIsRunningException::class, Response::HTTP_BAD_REQUEST, 'SWAG_MIGRATION__IS_RUNNING'],
            [new MigrationRunUndefinedStatusException(Uuid::randomHex()), MigrationRunUndefinedStatusException::class, Response::HTTP_BAD_REQUEST, 'SWAG_MIGRATION__RUN_UNDEFINED_STATUS'],
            [new MigrationWorkloadPropertyMissingException('foo'), MigrationWorkloadPropertyMissingException::class, Response::HTTP_BAD_REQUEST, 'SWAG_MIGRATION__WORKLOAD_PROPERTY_MISSING'],
            [new ProcessorNotFoundException('foo', 'bar'), ProcessorNotFoundException::class, Response::HTTP_NOT_FOUND, 'SWAG_MIGRATION__PROCESSOR_NOT_FOUND'],
            [new ProfileNotFoundException('foo'), ProfileNotFoundException::class, Response::HTTP_NOT_FOUND, 'SWAG_MIGRATION__PROFILE_NOT_FOUND'],
            [new RequestCertificateInvalidException('foo'), RequestCertificateInvalidException::class, Response::HTTP_BAD_REQUEST, 'SWAG_MIGRATION__REQUEST_CERTIFICATE_INVALID'],
            [new WriterNotFoundException('foo'), WriterNotFoundException::class, Response::HTTP_NOT_FOUND, 'SWAG_MIGRATION__WRITER_NOT_FOUND'],
        ];
    }
}
