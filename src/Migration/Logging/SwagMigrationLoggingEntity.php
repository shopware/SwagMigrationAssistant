<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;

#[Package('services-settings')]
class SwagMigrationLoggingEntity extends Entity
{
    use EntityIdTrait;

    protected string $level;

    protected string $code;

    protected string $title;

    protected string $description;

    protected array $parameters;

    protected string $titleSnippet;

    protected string $descriptionSnippet;

    protected ?string $entity;

    protected ?string $sourceId;

    protected ?string $runId;

    protected ?SwagMigrationRunEntity $run;

    protected int $autoIncrement;

    public function getLevel(): string
    {
        return $this->level;
    }

    public function setLevel(string $level): void
    {
        $this->level = $level;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    public function getTitleSnippet(): string
    {
        return $this->titleSnippet;
    }

    public function setTitleSnippet(string $titleSnippet): void
    {
        $this->titleSnippet = $titleSnippet;
    }

    public function getDescriptionSnippet(): string
    {
        return $this->descriptionSnippet;
    }

    public function setDescriptionSnippet(string $descriptionSnippet): void
    {
        $this->descriptionSnippet = $descriptionSnippet;
    }

    public function getEntity(): ?string
    {
        return $this->entity;
    }

    public function setEntity(?string $entity): void
    {
        $this->entity = $entity;
    }

    public function getSourceId(): ?string
    {
        return $this->sourceId;
    }

    public function setSourceId(?string $sourceId): void
    {
        $this->sourceId = $sourceId;
    }

    public function getRunId(): ?string
    {
        return $this->runId;
    }

    public function setRunId(?string $runId): void
    {
        $this->runId = $runId;
    }

    public function getRun(): ?SwagMigrationRunEntity
    {
        return $this->run;
    }

    public function setRun(?SwagMigrationRunEntity $run): void
    {
        $this->run = $run;
    }

    public function getAutoIncrement(): int
    {
        return $this->autoIncrement;
    }

    public function setAutoIncrement(int $autoIncrement): void
    {
        $this->autoIncrement = $autoIncrement;
    }
}
