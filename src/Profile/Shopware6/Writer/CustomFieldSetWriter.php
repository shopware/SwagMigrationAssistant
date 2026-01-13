<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Writer;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetCollection;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Writer\AbstractWriter;

#[Package('fundamentals@after-sales')]
class CustomFieldSetWriter extends AbstractWriter
{
    /**
     * @param EntityRepository<CustomFieldSetCollection> $customFieldSetRepository
     */
    public function __construct(
        EntityWriterInterface $entityWriter,
        EntityDefinition $definition,
        private readonly EntityRepository $customFieldSetRepository,
    ) {
        parent::__construct($entityWriter, $definition);
    }

    public function supports(): string
    {
        return DefaultEntities::CUSTOM_FIELD_SET;
    }

    public function writeData(array $data, Context $context): array
    {
        $ids = \array_column($data, 'id');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('id', $ids));

        $existingIds = $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($criteria) {
            return $this->customFieldSetRepository->searchIds($criteria, $context)->getIds();
        });

        // The 'name' field of custom_field_set is immutable and cannot be updated
        // Strip it from existing entities to prevent write constraint violations
        foreach ($data as &$entry) {
            if (\in_array($entry['id'], $existingIds, true)) {
                unset($entry['name']);
            }
        }
        unset($entry);

        return parent::writeData($data, $context);
    }
}
