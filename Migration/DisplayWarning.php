<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration;

use Shopware\Core\Framework\Struct\Struct;

class DisplayWarning extends Struct
{
    /**
     * @var string
     */
    protected $snippetKey;

    /**
     * @var string[]
     */
    protected $snippetArguments;

    /**
     * @var int
     */
    protected $pluralizationCount;

    /**
     * @param string[] $snippetArguments
     */
    public function __construct(string $snippetKey, array $snippetArguments = [], int $pluralizationCount = 0)
    {
        $this->snippetKey = $snippetKey;
        $this->snippetArguments = $snippetArguments;
        $this->pluralizationCount = $pluralizationCount;
    }

    public function getSnippetKey(): string
    {
        return $this->snippetKey;
    }

    public function setSnippetKey(string $snippetKey): void
    {
        $this->snippetKey = $snippetKey;
    }

    /**
     * @return string[]
     */
    public function getSnippetArguments(): array
    {
        return $this->snippetArguments;
    }

    /**
     * @param string[] $snippetArguments
     */
    public function setSnippetArguments(array $snippetArguments): void
    {
        $this->snippetArguments = $snippetArguments;
    }

    public function getPluralizationCount(): int
    {
        return $this->pluralizationCount;
    }

    public function setPluralizationCount(int $pluralizationCount): void
    {
        $this->pluralizationCount = $pluralizationCount;
    }
}
