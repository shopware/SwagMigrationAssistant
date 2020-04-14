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
use Shopware\Core\Framework\Struct\Serializer\StructNormalizer;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

class OrderWriter implements WriterInterface
{
    /**
     * @var EntityWriterInterface
     */
    private $entityWriter;

    /**
     * @var StructNormalizer
     */
    private $structNormalizer;

    /**
     * @var EntityDefinition
     */
    private $definition;

    public function __construct(EntityWriterInterface $entityWriter, StructNormalizer $structNormalizer, EntityDefinition $definition)
    {
        $this->entityWriter = $entityWriter;
        $this->structNormalizer = $structNormalizer;
        $this->definition = $definition;
    }

    public function supports(): string
    {
        return DefaultEntities::ORDER;
    }

    public function writeData(array $data, Context $context): void
    {
        foreach ($data as &$item) {
            foreach ($item['transactions'] as &$transaction) {
                $transaction['amount'] = $this->structNormalizer->denormalize($transaction['amount']);
            }
            unset($transaction);
        }
        unset($item);

        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($data): void {
            $this->entityWriter->upsert(
                $this->definition,
                $data,
                WriteContext::createFromContext($context)
            );
        });
    }
}
