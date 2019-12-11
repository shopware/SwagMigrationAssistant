<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;

class SwagMigrationLoggingEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $level;

    /**
     * @var string
     */
    protected $code;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var array
     */
    protected $parameters;

    /**
     * @var string
     */
    protected $titleSnippet;

    /**
     * @var string
     */
    protected $descriptionSnippet;

    /**
     * @var ?string
     */
    protected $entity;

    /**
     * @var ?string
     */
    protected $sourceId;

    /**
     * @var ?string
     */
    protected $runId;

    /**
     * @var ?SwagMigrationRunEntity
     */
    protected $run;

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
}
