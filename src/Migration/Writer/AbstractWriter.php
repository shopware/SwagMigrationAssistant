<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Writer;

use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\ArrayStruct;
use SwagMigrationAssistant\Exception\MigrationException;

#[Package('fundamentals@after-sales')]
abstract class AbstractWriter implements WriterInterface
{
    public const EXTENSION_NAME = 'writeEventSource';
    public const EXTENSION_SOURCE_KEY = 'source';
    public const EXTENSION_SOURCE_VALUE = 'swag-migration-assistant';

    public function __construct(
        protected EntityWriterInterface $entityWriter,
        protected EntityDefinition $definition,
    ) {
    }

    /**
     * ## Warning:
     * Be careful what context gets passed into this method,
     * as Context with AdminApiSource contains the (admin) user id
     * and thus might create side effects for CreatedBy and UpdatedBy DAL fields.
     * This is prevented by throwing a MigrationException::invalidWriteContext exception.
     *
     * @param array<mixed> $data
     *
     * @return array<string, array<EntityWriteResult>>
     */
    public function writeData(array $data, Context $context): array
    {
        if (!$context->getSource() instanceof SystemSource) {
            throw MigrationException::invalidWriteContext($context);
        }

        $writeResults = [];

        $context->addExtension(
            self::EXTENSION_NAME,
            new ArrayStruct([
                self::EXTENSION_SOURCE_KEY => self::EXTENSION_SOURCE_VALUE,
            ]),
        );

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
