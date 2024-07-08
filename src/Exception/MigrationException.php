<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Exception;

use Shopware\Core\Framework\DataAbstractionLayer\Field\Field;
use Shopware\Core\Framework\HttpException;
use Shopware\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('services-settings')]
class MigrationException extends HttpException
{
    final public const GATEWAY_READ = 'SWAG_MIGRATION__GATEWAY_READ';

    final public const GATEWAY_NOT_FOUND = 'SWAG_MIGRATION__GATEWAY_NOT_FOUND';

    final public const PARENT_ENTITY_NOT_FOUND = 'SWAG_MIGRATION__SHOPWARE_PARENT_ENTITY_NOT_FOUND';

    final public const PROVIDER_HAS_NO_TABLE_ACCESS = 'SWAG_MIGRATION__PROVIDER_HAS_NO_TABLE_ACCESS';

    public const MIGRATION_CONTEXT_NOT_CREATED = 'SWAG_MIGRATION__MIGRATION_CONTEXT_NOT_CREATED';

    public const NO_RUNNING_MIGRATION = 'SWAG_MIGRATION__NO_RUNNING_MIGRATION';

    public const NO_RUN_TO_FINISH = 'SWAG_MIGRATION__NO_RUN_TO_FINISH';

    public const MIGRATION_IS_ALREADY_RUNNING = 'SWAG_MIGRATION__MIGRATION_IS_ALREADY_RUNNING';

    public const NO_CONNECTION_IS_SELECTED = 'SWAG_MIGRATION__NO_CONNECTION_IS_SELECTED';

    public const NO_CONNECTION_FOUND = 'SWAG_MIGRATION__NO_CONNECTION_FOUND';

    public const RUN_COULD_NOT_BE_CREATED = 'SWAG_MIGRATION__RUN_COULD_NOT_BE_CREATED';

    public const PREMAPPING_IS_INCOMPLETE = 'SWAG_MIGRATION__PREMAPPING_IS_INCOMPLETE';

    public const NO_DATA_TO_MIGRATE = 'SWAG_MIGRATION__NO_DATA_TO_MIGRATE';

    public const NO_RUN_PROGRESS_FOUND = 'SWAG_MIGRATION__NO_RUN_PROGRESS_FOUND';

    public const UNKNOWN_PROGRESS_STEP = 'SWAG_MIGRATION__UNKNON_PROGRESS_STEP';

    public const ENTITY_NOT_EXISTS = 'SWAG_MIGRATION__ENTITY_NOT_EXISTS';

    public const PROCESSOR_NOT_FOUND = 'SWAG_MIGRATION__PROCESSOR_NOT_FOUND';

    public const INVALID_FIELD_SERIALIZER = 'SWAG_MIGRATION__INVALID_FIELD_SERIALIZER';

    public const INVALID_CONNECTION_AUTHENTICATION = 'SWAG_MIGRATION__INVALID_CONNECTION_AUTHENTICATION';

    public const SSL_REQUIRED = 'SWAG_MIGRATION__SSL_REQUIRED';

    public const REQUEST_CERTIFICATE_INVALID = 'SWAG_MIGRATION__REQUEST_CERTIFICATE_INVALID';

    public const CONVERTER_NOT_FOUND = 'SWAG_MIGRATION__CONVERTER_NOT_FOUND';

    public const MIGRATION_CONTEXT_PROPERTY_MISSING = 'SWAG_MIGRATION__MIGRATION_CONTEXT_PROPERTY_MISSING';

    public const READER_NOT_FOUND = 'SWAG_MIGRATION__READER_NOT_FOUND';

    public const DATASET_NOT_FOUND = 'SWAG_MIGRATION__DATASET_NOT_FOUND';

    public const LOCALE_NOT_FOUND = 'SWAG_MIGRATION__LOCALE_NOT_FOUND';

    public const UNDEFINED_RUN_STATUS = 'SWAG_MIGRATION__UNDEFINED_RUN_STATUS';

    public const NO_FILE_SYSTEM_PERMISSIONS = 'SWAG_MIGRATION__NO_FILE_SYSTEM_PERMISSIONS';

    public const PROFILE_NOT_FOUND = 'SWAG_MIGRATION__PROFILE_NOT_FOUND';

    public const WRITER_NOT_FOUND = 'SWAG_MIGRATION__WRITER_NOT_FOUND';

    public const COULD_NOT_READ_FILE = 'SWAG_MIGRATION__COULD_NOT_READ_FILE';

    public const PROVIDER_NOT_FOUND = 'SWAG_MIGRATION__PROVIDER_NOT_FOUND';

    public const COULD_NOT_GENERATE_DOCUMENT = 'SWAG_MIGRATION__COULD_NOT_GENERATE_DOCUMENT';

    public const ASSOCIATION_ENTITY_REQUIRED_MISSING = 'SWAG_MIGRATION__ASSOCIATION_REQUIRED_MISSING';

    public const DATABASE_CONNECTION_ERROR = 'SWAG_MIGRATION__DATABASE_CONNECTION_ERROR';

    public const DATABASE_CONNECTION_ATTRIBUTES_WRONG = 'SWAG_MIGRATION__DATABASE_CONNECTION_ATTRIBUTES_WRONG';

    public static function associationEntityRequiredMissing(string $entity, string $missingEntity): self
    {
        return new AssociationEntityRequiredMissingException(
            Response::HTTP_NOT_FOUND,
            self::ASSOCIATION_ENTITY_REQUIRED_MISSING,
            'Mapping of "{{ missingEntity }}" is missing, but it is a required association for "{{ entity }}". Import "{{ missingEntity }}" first.',
            [
                'missingEntity' => $missingEntity,
                'entity' => $entity,
            ]
        );
    }

    public static function databaseConnectionError(): self
    {
        return new self(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            self::DATABASE_CONNECTION_ERROR,
            'Database connection could not be established.'
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

    public static function gatewayRead(string $gateway): self
    {
        return new GatewayReadException(
            Response::HTTP_NOT_FOUND,
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
        return new DataSetNotFoundException(
            Response::HTTP_NOT_FOUND,
            self::DATASET_NOT_FOUND,
            'Data set for "{{ entity }}" entity not found.',
            ['entity' => $entity]
        );
    }

    public static function invalidConnectionAuthentication(string $url): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::INVALID_CONNECTION_AUTHENTICATION,
            'Invalid connection authentication for the request: "{{ url }}"',
            ['url' => $url]
        );
    }

    public static function sslRequired(): self
    {
        return new self(
            Response::HTTP_MISDIRECTED_REQUEST,
            self::SSL_REQUIRED,
            'The request failed, because SSL is required.'
        );
    }

    public static function requestCertificateInvalid(string $url): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
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
            Response::HTTP_BAD_REQUEST,
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

    public static function noRunningMigration(?string $runUuid = null): self
    {
        if ($runUuid !== null) {
            return new NoRunningMigrationException(
                Response::HTTP_BAD_REQUEST,
                self::NO_RUNNING_MIGRATION,
                'No running migration found with id: "{{ runUuid }}".',
                ['runUuid' => $runUuid]
            );
        }

        return new NoRunningMigrationException(
            Response::HTTP_BAD_REQUEST,
            self::NO_RUNNING_MIGRATION,
            'No running migration found.',
        );
    }

    public static function migrationIsAlreadyRunning(): self
    {
        return new MigrationIsAlreadyRunningException(
            Response::HTTP_BAD_REQUEST,
            self::MIGRATION_IS_ALREADY_RUNNING,
            'Migration is already running.',
        );
    }

    public static function noConnectionIsSelected(): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::NO_CONNECTION_IS_SELECTED,
            'No connection is selected.',
        );
    }

    public static function noConnectionFound(): self
    {
        return new NoConnectionFoundException(
            Response::HTTP_BAD_REQUEST,
            self::NO_CONNECTION_FOUND,
            'No connection found.',
        );
    }

    public static function runCouldNotBeCreated(): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::RUN_COULD_NOT_BE_CREATED,
            'Could not created migration run.',
        );
    }

    public static function noRunToFinish(): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::NO_RUN_TO_FINISH,
            'No migration run to finish found.',
        );
    }

    public static function premappingIsIncomplete(): self
    {
        return new PremappingIsIncompleteException(
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

    public static function noRunProgressFound(string $runUuid): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::NO_RUN_PROGRESS_FOUND,
            'No run progress found for run with id: "{{ runUuid }}".',
            ['runUuid' => $runUuid]
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
            Response::HTTP_BAD_REQUEST,
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
            Response::HTTP_BAD_REQUEST,
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
        return new LocaleNotFoundException(
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

    public static function noFileSystemPermissions(): self
    {
        return new self(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            self::NO_FILE_SYSTEM_PERMISSIONS,
            'No file system permissions to create or write to files or directories.'
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
        return new WriterNotFoundException(
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
}
