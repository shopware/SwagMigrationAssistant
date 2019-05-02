<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Writer;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use SwagMigrationNext\Migration\DataSelection\DefaultEntities;

class TranslationWriter implements WriterInterface
{
    /**
     * @var EntityWriterInterface
     */
    private $entityWriter;

    /**
     * @var DefinitionInstanceRegistry
     */
    private $registry;

    public function __construct(EntityWriterInterface $entityWriter, DefinitionInstanceRegistry $registry)
    {
        $this->entityWriter = $entityWriter;
        $this->registry = $registry;
    }

    public function supports(): string
    {
        return DefaultEntities::TRANSLATION;
    }

    public function writeData(array $data, Context $context): void
    {
        $translationArray = [];
        foreach ($data as $translationData) {
            $entityDefinitionClass = (string) $translationData['entityDefinitionClass'];
            unset($translationData['entityDefinitionClass']);
            $translationArray[$entityDefinitionClass][] = $translationData;
        }

        foreach ($translationArray as $entityDefinitionClass => $translation) {
            $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($entityDefinitionClass, $translation) {
                $this->entityWriter->upsert(
                    $this->registry->get($entityDefinitionClass),
                    $translation,
                    WriteContext::createFromContext($context)
                );
            });
        }
    }
}
