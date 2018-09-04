<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Writer;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\Write\EntityWriterInterface;
use Shopware\Core\Framework\ORM\Write\WriteContext;

class TranslationWriter implements WriterInterface
{
    /**
     * @var EntityWriterInterface
     */
    private $entityWriter;

    public function __construct(EntityWriterInterface $entityWriter)
    {
        $this->entityWriter = $entityWriter;
    }

    public function supports(): string
    {
        return 'translation';
    }

    /**
     * Writes the converted data of the supported entity type into the database
     */
    public function writeData(array $data, Context $context): void
    {
        $translationArray = [];
        foreach ($data as $translationData) {
            $entityDefinitionClass = $translationData['entityDefinitionClass'];
            unset($translationData['entityDefinitionClass']);
            $translationArray[$entityDefinitionClass][] = $translationData;
        }

        foreach ($translationArray as $entityDefinitionClass => $translation) {
            $this->entityWriter->upsert(
                $entityDefinitionClass,
                $translation,
                WriteContext::createFromContext($context)
            );
        }
    }
}
