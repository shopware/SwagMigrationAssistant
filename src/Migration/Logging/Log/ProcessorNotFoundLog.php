<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging\Log;

use Shopware\Core\Framework\Log\Package;

#[Package('services-settings')]
class ProcessorNotFoundLog implements LogEntryInterface
{
    public function __construct(
        private readonly string $runId,
        private readonly string $entity,
        private readonly string $profileName,
        private readonly string $gatewayName
    ) {
    }

    public function getLevel(): string
    {
        return self::LOG_LEVEL_ERROR;
    }

    public function getCode(): string
    {
        return 'SWAG_MIGRATION__PROCESSOR_NOT_FOUND';
    }

    public function getTitle(): string
    {
        return 'Processor not found';
    }

    /**
     * @return array{profileName: string, gatewayName: string, entity: ?string}
     */
    public function getParameters(): array
    {
        return [
            'profileName' => $this->profileName,
            'gatewayName' => $this->gatewayName,
            'entity' => $this->getEntity(),
        ];
    }

    public function getDescription(): string
    {
        $args = $this->getParameters();

        return \sprintf(
            'Processor for profile "%s", gateway "%s" and entity "%s" not found.',
            $args['profileName'],
            $args['gatewayName'],
            $args['entity']
        );
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

    public function getSnippetRoot(): string
    {
        return 'swag-migration.index.error';
    }

    public function getTitleSnippet(): string
    {
        return \sprintf('%s.%s.title', $this->getSnippetRoot(), $this->getCode());
    }

    public function getDescriptionSnippet(): string
    {
        return \sprintf('%s.%s.description', $this->getSnippetRoot(), $this->getCode());
    }
}
