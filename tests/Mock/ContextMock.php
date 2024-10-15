<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Mock;

use Shopware\Core\Framework\Api\Context\ContextSource;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;

class ContextMock extends Context
{
    public static function createDefaultContext(?ContextSource $source = null): self
    {
        $source = new SystemSource();

        return new self($source);
    }

    /**
     * @param non-empty-array<string> $chain
     */
    public function setLangaugeIdChain(array $chain): void
    {
        $this->languageIdChain = $chain;
    }
}
