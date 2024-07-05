<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader;

use Doctrine\DBAL\ArrayParameterType;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

#[Package('services-settings')]
class TranslationReader extends AbstractReader
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME
            && $this->getDataSetEntity($migrationContext) === DefaultEntities::TRANSLATION;
    }

    public function supportsTotal(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME;
    }

    public function read(MigrationContextInterface $migrationContext): array
    {
        $this->setConnection($migrationContext);
        $fetchedTranslations = $this->fetchTranslations($migrationContext->getOffset(), $migrationContext->getLimit());

        $resultSet = $this->mapData(
            $fetchedTranslations,
            [],
            ['translation', 'locale', 'name', 'ordernumber', 'mediaId']
        );

        return $this->cleanupResultSet($resultSet);
    }

    public function readTotal(MigrationContextInterface $migrationContext): ?TotalStruct
    {
        $this->setConnection($migrationContext);

        $total = (int) $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('s_core_translations')
            ->executeQuery()
            ->fetchOne();

        return new TotalStruct(DefaultEntities::TRANSLATION, $total);
    }

    private function fetchTranslations(int $offset, int $limit): array
    {
        $ids = $this->fetchIdentifiers('s_core_translations', $offset, $limit);

        $query = $this->connection->createQueryBuilder();

        $query->from('s_core_translations', 'translation');
        $this->addTableSelection($query, 's_core_translations', 'translation');

        $query->innerJoin('translation', 's_core_shops', 'shop', 'shop.id = translation.objectlanguage');
        $query->leftJoin('shop', 's_core_locales', 'locale', 'locale.id = shop.locale_id');
        $query->addSelect('REPLACE(locale.locale, "_", "-") as locale');

        $query->leftJoin('translation', 's_articles_supplier', 'manufacturer', 'translation.objecttype = "supplier" AND translation.objectkey = manufacturer.id');
        $query->addSelect('manufacturer.name');

        $query->leftJoin('translation', 's_articles_details', 'variant', 'translation.objecttype = "variant" AND translation.objectkey = variant.id');
        $query->addSelect('variant.ordernumber');

        $query->leftJoin('translation', 's_articles_img', 'images', 'translation.objecttype = "articleimage" AND translation.objectkey = images.id');
        $query->addSelect('images.media_id AS mediaId');

        $query->where('translation.id IN (:ids)');
        $query->setParameter('ids', $ids, ArrayParameterType::STRING);

        $query->addOrderBy('translation.id');

        $query->executeQuery();

        return $query->fetchAllAssociative();
    }
}
