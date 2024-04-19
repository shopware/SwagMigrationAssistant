<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Service;

use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexer;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexingMessage;
use Shopware\Core\Framework\Log\Package;

#[Package('services-settings')]
class EntityPartialIndexerService
{
    /**
     * @param EntityIndexer[] $indexer
     */
    public function __construct(private readonly iterable $indexer)
    {
    }

    /**
     * @param array{offset: int|null}|null $lastId
     */
    public function partial(?string $lastIndexer, ?array $lastId): ?EntityIndexingMessage
    {
        $indexers = $this->getIndexers();

        foreach ($indexers as $index => $indexer) {
            if (!$lastIndexer || $lastIndexer === $indexer->getName()) {
                $message = $indexer->iterate($lastId);

                if ($message !== null) {
                    $message->setIndexer($indexer->getName());
                    $indexer->handle($message);

                    return $message;
                }

                $nextIndex = $index + 1;
                if (isset($indexers[$nextIndex])) {
                    $message = new EntityIndexingMessage([]);
                    $message->setIndexer($indexers[$nextIndex]->getName());

                    return $message;
                }

                return null;
            }
        }

        return null;
    }

    /**
     * @return EntityIndexer[]
     */
    private function getIndexers(): array
    {
        if (!\is_array($this->indexer)) {
            return \array_values(\iterator_to_array($this->indexer));
        }

        return \array_values($this->indexer);
    }
}
