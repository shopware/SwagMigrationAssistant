<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Exception;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Field;
use Shopware\Core\Framework\HttpException;
use Shopware\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

/**
 * @codeCoverageIgnore
 */
#[Package('fundamentals@after-sales')]
class MigrationException extends HttpException
{
    final public const GATEWAY_READ = 'SWAG_MIGRATION__GATEWAY_READ';

    final public const GATEWAY_NOT_FOUND = 'SWAG_MIGRATION__GATEWAY_NOT_FOUND';

    final public const PARENT_ENTITY_NOT_FOUND = 'SWAG_MIGRATION__SHOPWARE_PARENT_ENTITY_NOT_FOUND';

    final public const PROVIDER_HAS_NO_TABLE_ACCESS = 'SWAG_MIGRATION__PROVIDER_HAS_NO_TABLE_ACCESS';

    final public const MIGRATION_CONTEXT_NOT_CREATED = 'SWAG_MIGRATION__MIGRATION_CONTEXT_NOT_CREATED';

    final public const RUN_NOT_FOUND = 'SWAG_MIGRATION__RUN_NOT_FOUND';

    final public const NO_RUN_PROGRESS_FOUND = 'SWAG_MIGRATION__NO_RUN_PROGRESS_FOUND';

    final public const MIGRATION_PROCESSING = 'SWAG_MIGRATION__MIGRATION_PROCESSING';

    final public const NO_CONNECTION_FOUND = 'SWAG_MIGRATION__NO_CONNECTION_FOUND';

    final public const RUN_COULD_NOT_BE_CREATED = 'SWAG_MIGRATION__RUN_COULD_NOT_BE_CREATED';

    final public const PREMAPPING_IS_INCOMPLETE = 'SWAG_MIGRATION__PREMAPPING_IS_INCOMPLETE';

    final public const NO_DATA_TO_MIGRATE = 'SWAG_MIGRATION__NO_DATA_TO_MIGRATE';

    final public const UNKNOWN_PROGRESS_STEP = 'SWAG_MIGRATION__UNKNOWN_PROGRESS_STEP';

    final public const ENTITY_NOT_EXISTS = 'SWAG_MIGRATION__ENTITY_NOT_EXISTS';

    final public const PROCESSOR_NOT_FOUND = 'SWAG_MIGRATION__PROCESSOR_NOT_FOUND';

    final public const INVALID_FIELD_SERIALIZER = 'SWAG_MIGRATION__INVALID_FIELD_SERIALIZER';

    final public const INVALID_CONNECTION_CREDENTIALS = 'SWAG_MIGRATION__INVALID_CONNECTION_CREDENTIALS';

    final public const SSL_REQUIRED = 'SWAG_MIGRATION__SSL_REQUIRED';

    final public const REQUEST_CERTIFICATE_INVALID = 'SWAG_MIGRATION__REQUEST_CERTIFICATE_INVALID';

    final public const CONVERTER_NOT_FOUND = 'SWAG_MIGRATION__CONVERTER_NOT_FOUND';

    final public const MIGRATION_CONTEXT_PROPERTY_MISSING = 'SWAG_MIGRATION__MIGRATION_CONTEXT_PROPERTY_MISSING';

    final public const READER_NOT_FOUND = 'SWAG_MIGRATION__READER_NOT_FOUND';

    final public const DATASET_NOT_FOUND = 'SWAG_MIGRATION__DATASET_NOT_FOUND';

    final public const LOCALE_NOT_FOUND = 'SWAG_MIGRATION__LOCALE_NOT_FOUND';

    final public const UNDEFINED_RUN_STATUS = 'SWAG_MIGRATION__UNDEFINED_RUN_STATUS';

    final public const PROFILE_NOT_FOUND = 'SWAG_MIGRATION__PROFILE_NOT_FOUND';

    final public const WRITER_NOT_FOUND = 'SWAG_MIGRATION__WRITER_NOT_FOUND';

    final public const COULD_NOT_READ_FILE = 'SWAG_MIGRATION__COULD_NOT_READ_FILE';

    final public const PROVIDER_NOT_FOUND = 'SWAG_MIGRATION__PROVIDER_NOT_FOUND';

    final public const COULD_NOT_GENERATE_DOCUMENT = 'SWAG_MIGRATION__COULD_NOT_GENERATE_DOCUMENT';

    final public const ASSOCIATION_ENTITY_REQUIRED_MISSING = 'SWAG_MIGRATION__ASSOCIATION_REQUIRED_MISSING';

    final public const DATABASE_CONNECTION_ATTRIBUTES_WRONG = 'SWAG_MIGRATION__DATABASE_CONNECTION_ATTRIBUTES_WRONG';

    final public const INVALID_WRITE_CONTEXT = 'SWAG_MIGRATION__INVALID_WRITE_CONTEXT';

    final public const API_CONNECTION_ERROR = 'SWAG_MIGRATION__API_CONNECTION_ERROR';

    final public const COULD_NOT_CONVERT_FIX = 'SWAG_MIGRATION__COULD_NOT_CONVERT_FIX';

    final public const MIGRATION_NOT_IN_STEP = 'SWAG_MIGRATION__MIGRATION_NOT_IN_STEP';

    final public const DUPLICATE_SOURCE_CONNECTION = 'SWAG_MIGRATION__DUPLICATE_SOURCE_CONNECTION';

    final public const MISSING_REQUEST_PARAMETER = 'SWAG_MIGRATION__MISSING_REQUEST_PARAMETER';

    public const MIGRATION_DISABLED_BY_SOURCE = 'SWAG_MIGRATION__MIGRATION_DISABLED_BY_SOURCE';

    public const UNRESOLVED_ERRORS_REMAINING = 'SWAG_MIGRATION__UNRESOLVED_ERRORS_REMAINING';

    public const INVALID_VALUE_FOR_LIMIT_PARAMETER = 'SWAG_MIGRATION__INVALID_LIMIT_PARAMETER';

    public const CONNECTION_NAME_NOT_UNIQUE = 'SWAG_MIGRATION__CONNECTION_NAME_NOT_UNIQUE';

    public const LOCAL_DATABASE_CONNECTION_ERROR = 'SWAG_MIGRATION__LOCAL_DATABASE_CONNECTION_ERROR';

    public static function associationEntityRequiredMissing(string $entity, string $missingEntity): self
    {
        return new self(
            Response::HTTP_NOT_FOUND,
            self::ASSOCIATION_ENTITY_REQUIRED_MISSING,
            'Mapping of "{{ missingEntity }}" is missing, but it is a required association for "{{ entity }}". Import "{{ missingEntity }}" first.',
            [
                'missingEntity' => $missingEntity,
                'entity' => $entity,
            ]
        );
    }

    public static function databaseConnectionAttributesWrong(): self
    {
        return new self(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            self::DATABASE_CONNECTION_ATTRIBUTES_WRONG,
            'Database connection does not have the right attributes and they can not be set.'
        );
    }

    public static function apiConnectionError(string $message): self
    {
        return new self(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            self::API_CONNECTION_ERROR,
            $message
        );
    }

    public static function gatewayRead(string $gateway): self
    {
        return new self(
            Response::HTTP_BAD_GATEWAY,
            self::GATEWAY_READ,
            'Could not read from gateway: "{{ gateway }}".',
            ['gateway' => $gateway]
        );
    }

    public static function gatewayNotFound(string $profile, string $gateway): self
    {
        return new self(
            Response::HTTP_NOT_FOUND,
            self::GATEWAY_NOT_FOUND,
            'Gateway for profile "{{ profile }}" and gateway "{{ gateway }}" not found.',
            ['profile' => $profile, 'gateway' => $gateway]
        );
    }

    public static function readerNotFound(string $entityName): self
    {
        return new self(
            Response::HTTP_NOT_FOUND,
            self::READER_NOT_FOUND,
            'Data reader for "{{ entityName }}" not found.',
            ['entityName' => $entityName]
        );
    }

    public static function dataSetNotFound(string $entity): self
    {
        return new self(
            Response::HTTP_NOT_FOUND,
            self::DATASET_NOT_FOUND,
            'Data set for "{{ entity }}" entity not found.',
            ['entity' => $entity]
        );
    }

    public static function invalidConnectionCredentials(?string $url = null): self
    {
        $message = 'The connection credentials are invalid or incomplete.';

        if ($url !== null) {
            $message = \sprintf(
                'The connection credentials are invalid or incomplete for "%s".',
                $url
            );
        }

        return new self(
            Response::HTTP_UNAUTHORIZED,
            self::INVALID_CONNECTION_CREDENTIALS,
            $message,
        );
    }

    public static function sslRequired(): self
    {
        return new self(
            Response::HTTP_FORBIDDEN,
            self::SSL_REQUIRED,
            'The request failed, because SSL is required.'
        );
    }

    public static function requestCertificateInvalid(string $url): self
    {
        return new self(
            Response::HTTP_BAD_GATEWAY,
            self::REQUEST_CERTIFICATE_INVALID,
            'The following cURL request failed with an SSL certificate problem: "{{ url }}"',
            ['url' => $url]
        );
    }

    public static function parentEntityForChildNotFound(string $entity, string $oldIdentifier): self
    {
        return new self(
            Response::HTTP_NOT_FOUND,
            self::PARENT_ENTITY_NOT_FOUND,
            'Parent entity for "{{ entity }}: {{ oldIdentifier }}" child not found.',
            ['entity' => $entity, 'oldIdentifier' => $oldIdentifier]
        );
    }

    public static function providerHasNoTableAccess(string $identifier): self
    {
        return new self(
            Response::HTTP_NOT_IMPLEMENTED,
            self::PROVIDER_HAS_NO_TABLE_ACCESS,
            'Data provider "{{ identifier }}" has no direct table access found.',
            ['identifier' => $identifier]
        );
    }

    public static function providerNotFound(string $identifier): self
    {
        return new self(
            Response::HTTP_NOT_FOUND,
            self::PROVIDER_NOT_FOUND,
            'Data provider for "{{ identifier }}" not found.',
            ['identifier' => $identifier]
        );
    }

    public static function migrationContextNotCreated(): self
    {
        return new self(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            self::MIGRATION_CONTEXT_NOT_CREATED,
            'Migration context could not be created.',
        );
    }

    public static function migrationContextPropertyMissing(string $property): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::MIGRATION_CONTEXT_PROPERTY_MISSING,
            'Required property "{{ property }}" for migration context is missing.',
            ['property' => $property]
        );
    }

    public static function runNotFound(?string $runId = null): self
    {
        return new self(
            Response::HTTP_NOT_FOUND,
            self::RUN_NOT_FOUND,
            'No migration run found for run with id: "{{ runUuid }}".',
            ['runUuid' => $runId ?? 'unknown']
        );
    }

    public static function noRunProgressFound(string $runId): self
    {
        return new self(
            Response::HTTP_NOT_FOUND,
            self::NO_RUN_PROGRESS_FOUND,
            'No progress found for migration run with id: "{{ runUuid }}".',
            ['runUuid' => $runId]
        );
    }

    public static function migrationProcessing(?string $process = null): self
    {
        $message = 'Migration is busy processing.';

        if ($process !== null) {
            $message = \sprintf('Migration is busy processing: "%s".', $process);
        }

        return new self(
            Response::HTTP_CONFLICT,
            self::MIGRATION_PROCESSING,
            $message,
        );
    }

    public static function noConnectionFound(): self
    {
        return new self(
            Response::HTTP_NOT_FOUND,
            self::NO_CONNECTION_FOUND,
            'No connection found.',
        );
    }

    public static function runCouldNotBeCreated(): self
    {
        return new self(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            self::RUN_COULD_NOT_BE_CREATED,
            'Could not created migration run.',
        );
    }

    public static function premappingIsIncomplete(): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::PREMAPPING_IS_INCOMPLETE,
            'Premapping is incomplete.',
        );
    }

    public static function noDataToMigrate(): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::NO_DATA_TO_MIGRATE,
            'No data to migrate.',
        );
    }

    public static function unknownProgressStep(?string $step): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::UNKNOWN_PROGRESS_STEP,
            'Unknown progress step: "{{ step }}".',
            ['step' => $step ?? 'null']
        );
    }

    public static function entityNotExists(string $entityClassName, string $uuid): self
    {
        return new self(
            Response::HTTP_NOT_FOUND,
            self::ENTITY_NOT_EXISTS,
            'No {{ entityClassName }} with UUID {{ uuid }} found. Make sure the entity with the UUID exists.',
            [
                'entityClassName' => $entityClassName,
                'uuid' => $uuid,
            ]
        );
    }

    public static function processorNotFound(string $profile, string $gateway): self
    {
        return new self(
            Response::HTTP_NOT_FOUND,
            self::PROCESSOR_NOT_FOUND,
            'Processor for profile "{{ profile }}" and gateway "{{ gateway }}" not found.',
            [
                'profile' => $profile,
                'gateway' => $gateway,
            ]
        );
    }

    public static function invalidSerializerField(string $expectedClass, Field $field): self
    {
        return new self(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            self::INVALID_FIELD_SERIALIZER,
            'Expected field of type "{{ expectedField }}" got "{{ field }}".',
            ['expectedField' => $expectedClass, 'field' => $field::class]
        );
    }

    public static function converterNotFound(string $entity): self
    {
        return new self(
            Response::HTTP_NOT_FOUND,
            self::CONVERTER_NOT_FOUND,
            'Converter for "{{ entity }}" entity not found.',
            ['entity' => $entity]
        );
    }

    public static function localeNotFound(string $localeCode): self
    {
        return new self(
            Response::HTTP_NOT_FOUND,
            self::LOCALE_NOT_FOUND,
            'Locale entity for code "{{ localeCode }}" not found.',
            ['localeCode' => $localeCode]
        );
    }

    public static function undefinedRunStatus(string $status): self
    {
        return new self(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            self::UNDEFINED_RUN_STATUS,
            'Migration run status "{{ status }}" is not a valid status.',
            ['status' => $status]
        );
    }

    public static function profileNotFound(string $profileName): self
    {
        return new self(
            Response::HTTP_NOT_FOUND,
            self::PROFILE_NOT_FOUND,
            'Profile "{{ profileName }}" not found.',
            ['profileName' => $profileName]
        );
    }

    public static function writerNotFound(string $entityName): self
    {
        return new self(
            Response::HTTP_NOT_FOUND,
            self::WRITER_NOT_FOUND,
            'Writer for "{{ entityName }}" entity not found.',
            ['entityName' => $entityName]
        );
    }

    public static function couldNotReadFile(string $sourcePath): self
    {
        return new self(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            self::COULD_NOT_READ_FILE,
            'Could not read file from path: "{{ sourcePath }}".',
            ['sourcePath' => $sourcePath]
        );
    }

    public static function couldNotGenerateDocument(): self
    {
        return new self(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            self::COULD_NOT_GENERATE_DOCUMENT,
            'Document could not be generated.'
        );
    }

    public static function invalidWriteContext(Context $invalidContext): self
    {
        return new self(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            self::INVALID_WRITE_CONTEXT,
            'Invalid context passed to writer. It must have a source of SystemSource but got {{ sourceType }}',
            [
                'sourceType' => $invalidContext->getSource()::class,
            ]
        );
    }

    public static function couldNotConvertFix(string $missingKey): self
    {
        return new self(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            self::COULD_NOT_CONVERT_FIX,
            'Missing key "{{ missingKey }}" to construct MigrationFix.',
            ['missingKey' => $missingKey]
        );
    }

    public static function migrationNotInStep(string $step): self
    {
        return new self(
            Response::HTTP_CONFLICT,
            self::MIGRATION_NOT_IN_STEP,
            'Migration is not in step "{{ step }}".',
            ['step' => $step]
        );
    }

    public static function duplicateSourceConnection(): self
    {
        return new self(
            Response::HTTP_CONFLICT,
            self::DUPLICATE_SOURCE_CONNECTION,
            'A connection to this source system already exists.',
        );
    }

    public static function missingRequestParameter(string $parameterName): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::MISSING_REQUEST_PARAMETER,
            'Required request parameter "{{ parameterName }}" is missing.',
            ['parameterName' => $parameterName]
        );
    }

    public static function migrationDisabledBySource(): self
    {
        return new self(
            Response::HTTP_CONFLICT,
            self::MIGRATION_DISABLED_BY_SOURCE,
            'Migration is disabled by the source system.',
        );
    }

    public static function unresolvedErrorsRemaining(int $count): self
    {
        return new self(
            Response::HTTP_CONFLICT,
            self::UNRESOLVED_ERRORS_REMAINING,
            'Cannot continue migration: {{ count }} unresolved fixable errors remaining.',
            ['count' => $count]
        );
    }

    public static function invalidValueForLimitParameter(int $maxLimit): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::INVALID_VALUE_FOR_LIMIT_PARAMETER,
            'Limit has to be a positive integer greater than 0 and not greater than {{ maxLimit }}.',
            ['maxLimit' => $maxLimit]
        );
    }

    public static function connectionNameNotUnique(): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::CONNECTION_NAME_NOT_UNIQUE,
            'Connection name has to be unique.',
        );
    }

    public static function localDatabaseConnectionError(string $message, ?\Throwable $previous = null): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::LOCAL_DATABASE_CONNECTION_ERROR,
            'Connection to database failed: {{ message }}',
            ['message' => $message],
            $previous
        );
    }

    public static function connectionValidationFailed(string $code, string $message): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            $code,
            $message,
        );
    }
}
