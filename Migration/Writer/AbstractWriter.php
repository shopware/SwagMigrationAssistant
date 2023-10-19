<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Writer;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Shopware\Core\Framework\Log\Package;

#[Package('services-settings')]
abstract class AbstractWriter implements WriterInterface
{
    public function __construct(
        protected EntityWriterInterface $entityWriter,
        protected EntityDefinition $definition
    ) {
    }

    public function writeData(array $data, Context $context): array
    {
        $writeResults = [];
        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($data, &$writeResults): void {
            $writeResults = $this->entityWriter->upsert(
                $this->definition,
                $data,
                WriteContext::createFromContext($context)
            );
        });

        return $writeResults;
    }
}
