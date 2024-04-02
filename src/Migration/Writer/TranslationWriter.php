<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Writer;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

#[Package('services-settings')]
class TranslationWriter implements WriterInterface
{
    public function __construct(
        private readonly EntityWriterInterface $entityWriter,
        private readonly DefinitionInstanceRegistry $registry
    ) {
    }

    public function supports(): string
    {
        return DefaultEntities::TRANSLATION;
    }

    public function writeData(array $data, Context $context): array
    {
        $translationArray = [];
        foreach ($data as $translationData) {
            $entityDefinitionClass = (string) $translationData['entityDefinitionClass'];
            unset($translationData['entityDefinitionClass']);
            $translationArray[$entityDefinitionClass][] = $translationData;
        }

        $writeResults = [];
        foreach ($translationArray as $entityDefinitionClass => $translation) {
            $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($entityDefinitionClass, $translation, &$writeResults): void {
                $writeResults[] = $this->entityWriter->upsert(
                    $this->registry->get($entityDefinitionClass),
                    $translation,
                    WriteContext::createFromContext($context)
                );
            });
        }

        return $writeResults;
    }
}
