<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\DataSelection;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DataSelectionInterface;
use SwagMigrationAssistant\Migration\DataSelection\DataSelectionStruct;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\CustomerDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\CustomerWishlistDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6ProfileInterface;

#[Package('services-settings')]
class WishlistDataSelection implements DataSelectionInterface
{
    final public const IDENTIFIER = 'customerWishlists';

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof Shopware6ProfileInterface;
    }

    public function getData(): DataSelectionStruct
    {
        return new DataSelectionStruct(
            self::IDENTIFIER,
            $this->getDataSets(),
            $this->getDataSetsRequiredForCount(),
            'swag-migration.index.selectDataCard.dataSelection.customerWishlists',
            220,
            true
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDataSets(): array
    {
        return \array_merge(
            (new ProductDataSelection())->getDataSets(),
            [
                new CustomerDataSet(),
                new CustomerWishlistDataSet(),
            ]
        );
    }

    public function getDataSetsRequiredForCount(): array
    {
        return [
            new CustomerWishlistDataSet(),
        ];
    }
}
