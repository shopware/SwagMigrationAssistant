<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Logging\Log;

interface LogEntryInterface
{
    public const LOG_LEVEL_INFO = 'info';
    public const LOG_LEVEL_WARNING = 'warning';
    public const LOG_LEVEL_ERROR = 'error';
    public const LOG_LEVEL_DEBUG = 'debug';

    public function getLevel(): string;

    public function getCode(): string;

    public function getTitle(): string;

    public function getDescriptionArguments(): array;

    public function getDescription(): string;

    public function getTitleSnippet(): string;

    public function getDescriptionSnippet(): string;

    public function getEntity(): ?string;

    public function getSourceId(): ?string;

    public function getRunId(): ?string;
}
