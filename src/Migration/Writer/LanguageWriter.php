<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Writer;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Mapping\Lookup\LanguageLookup;

#[Package('fundamentals@after-sales')]
class LanguageWriter extends AbstractWriter
{
    public function __construct(
        protected EntityWriterInterface $entityWriter,
        protected EntityDefinition $definition,
        protected readonly LanguageLookup $languageLookup,
    ) {
        parent::__construct($this->entityWriter, $this->definition);
    }

    public function supports(): string
    {
        return DefaultEntities::LANGUAGE;
    }

    /**
     * @param array<mixed> $data
     *
     * @return array<string, array<EntityWriteResult>>
     */
    public function writeData(array $data, Context $context): array
    {
        // do not create languages which already exists
        $data = array_filter($data, function ($value) use ($context) {
            return $this->languageLookup->get($value['locale'], $context) === null;
        });

        return parent::writeData($data, $context);
    }
}
