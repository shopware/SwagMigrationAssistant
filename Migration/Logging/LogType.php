<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Logging;

final class LogType
{
    public const CONVERTER_NOT_FOUND = 'SWAG_MIGRATION__CONVERTER_NOT_FOUND';
    public const WRITER_NOT_FOUND = 'SWAG_MIGRATION__WRITER_NOT_FOUND';
    public const PROCESSOR_NOT_FOUND = 'SWAG_MIGRATION__PROCESSOR_NOT_FOUND';
    public const ENTITY_ALREADY_EXISTS = 'SWAG_MIGRATION__ENTITY_EXISTS';
}
