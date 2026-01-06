<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Validation\Exception;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\MigrationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * @codeCoverageIgnore
 */
#[Package('fundamentals@after-sales')]
class MigrationValidationException extends MigrationException
{
    final public const VALIDATION_UNEXPECTED_NULL_VALUE = 'SWAG_MIGRATION_VALIDATION__UNEXPECTED_NULL_VALUE';

    final public const VALIDATION_INVALID_ID = 'SWAG_MIGRATION_VALIDATION__INVALID_ID';

    final public const VALIDATION_INVALID_REQUIRED_FIELD_VALUE = 'SWAG_MIGRATION_VALIDATION__INVALID_REQUIRED_FIELD_VALUE';

    final public const VALIDATION_INVALID_OPTIONAL_FIELD_VALUE = 'SWAG_MIGRATION_VALIDATION__INVALID_OPTIONAL_FIELD_VALUE';

    final public const VALIDATION_INVALID_TRANSLATION = 'SWAG_MIGRATION_VALIDATION__INVALID_TRANSLATION';

    final public const VALIDATION_INVALID_ASSOCIATION = 'SWAG_MIGRATION_VALIDATION__INVALID_ASSOCIATION';

    public static function unexpectedNullValue(string $fieldName): self
    {
        return new self(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            self::VALIDATION_UNEXPECTED_NULL_VALUE,
            'Unexpected null value for field "{{ fieldName }}".',
            ['fieldName' => $fieldName]
        );
    }

    public static function invalidId(string $entityId, string $entityName): self
    {
        return new self(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            self::VALIDATION_INVALID_ID,
            'The id "{{ entityId }}" for entity "{{ entityName }}" is not a valid Uuid',
            ['entityId' => $entityId, 'entityName' => $entityName]
        );
    }

    public static function invalidRequiredFieldValue(string $entityName, string $fieldName, string $message): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::VALIDATION_INVALID_REQUIRED_FIELD_VALUE,
            'Invalid value for required field "{{ fieldName }}" in entity "{{ entityName }}": {{ message }}',
            ['fieldName' => $fieldName, 'entityName' => $entityName, 'message' => $message]
        );
    }

    public static function invalidOptionalFieldValue(string $entityName, string $fieldName, string $message): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::VALIDATION_INVALID_OPTIONAL_FIELD_VALUE,
            'Invalid value for optional field "{{ fieldName }}" in entity "{{ entityName }}": {{ message }}',
            ['fieldName' => $fieldName, 'entityName' => $entityName, 'message' => $message]
        );
    }

    public static function invalidTranslation(string $entityName, string $fieldName, string $message): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::VALIDATION_INVALID_TRANSLATION,
            'Invalid translation for field "{{ fieldName }}" in entity "{{ entityName }}": {{ message }}',
            ['fieldName' => $fieldName, 'entityName' => $entityName, 'message' => $message]
        );
    }

    public static function invalidAssociation(string $entityName, string $fieldName, string $message): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::VALIDATION_INVALID_ASSOCIATION,
            'Invalid association "{{ fieldName }}" in entity "{{ entityName }}": {{ message }}',
            ['fieldName' => $fieldName, 'entityName' => $entityName, 'message' => $message]
        );
    }
}
