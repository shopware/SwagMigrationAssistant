<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Logging\Log;

class ProcessorNotFoundLog implements LogEntryInterface
{
    /**
     * @var string
     */
    private $runId;

    /**
     * @var string
     */
    private $entity;

    /**
     * @var string
     */
    private $profileName;

    /**
     * @var string
     */
    private $gatewayName;

    public function __construct(string $runId, string $entity, string $profileName, string $gatewayName)
    {
        $this->runId = $runId;
        $this->entity = $entity;
        $this->profileName = $profileName;
        $this->gatewayName = $gatewayName;
    }

    public function getLevel(): string
    {
        return self::LOG_LEVEL_ERROR;
    }

    public function getCode(): string
    {
        return 'SWAG_MIGRATION_PROCESSOR_NOT_FOUND';
    }

    public function getTitle(): string
    {
        return 'Processor not found';
    }

    public function getDescriptionArguments(): array
    {
        return [
            'profileName' => $this->profileName,
            'gatewayName' => $this->gatewayName,
            'entity' => $this->getEntity(),
        ];
    }

    public function getDescription(): string
    {
        $args = $this->getDescriptionArguments();

        return sprintf(
            'Processor for profile "%s", gateway "%s" and entity "%s" not found.',
            $args['profileName'],
            $args['gatewayName'],
            $args['entity']
        );
    }

    public function getTitleSnippet(): string
    {
        return '...';
    }

    public function getDescriptionSnippet(): string
    {
        return '...';
    }

    public function getEntity(): ?string
    {
        return $this->entity;
    }

    public function getSourceId(): ?string
    {
        return null;
    }

    public function getRunId(): ?string
    {
        return $this->runId;
    }
}
