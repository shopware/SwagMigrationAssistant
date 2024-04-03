<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Mapping;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('services-settings')]
interface Shopware6MappingServiceInterface extends MappingServiceInterface
{
    public function getMailTemplateTypeUuid(string $type, string $oldIdentifier, MigrationContextInterface $migrationContext, Context $context): ?string;

    public function getSystemDefaultMailTemplateUuid(string $type, string $oldIdentifier, string $connectionId, MigrationContextInterface $migrationContext, Context $context): string;

    public function getNumberRangeTypeUuid(string $type, string $oldIdentifier, MigrationContextInterface $migrationContext, Context $context): ?string;

    public function getDefaultFolderIdByEntity(string $entityName, MigrationContextInterface $migrationContext, Context $context): ?string;

    public function getSalutationUuid(string $oldIdentifier, string $salutationKey, MigrationContextInterface $migrationContext, Context $context): ?string;

    public function getSeoUrlTemplateUuid(string $oldIdentifier, ?string $salesChannelId, string $routeName, MigrationContextInterface $migrationContext, Context $context): ?string;

    public function getSystemConfigUuid(string $oldIdentifier, string $configurationKey, ?string $salesChannelId, MigrationContextInterface $migrationContext, Context $context): ?string;

    public function getProductSortingUuid(string $key, Context $context): array;

    public function getStateMachineStateUuid(string $oldIdentifier, string $technicalName, string $stateMachineTechnicalName, MigrationContextInterface $migrationContext, Context $context): ?string;

    public function getGlobalDocumentBaseConfigUuid(string $oldIdentifier, string $documentTypeId, string $connectionId, MigrationContextInterface $migrationContext, Context $context): string;

    public function getCmsPageUuidByNames(array $names, Context $context): ?string;

    public function mapLockedCmsPageUuidByNameAndType(array $names, string $type, string $oldIdentifier, string $connectionId, MigrationContextInterface $migrationContext, Context $context): void;

    public function getCountryStateUuid(string $oldIdentifier, string $countryIso, string $countryIso3, string $countryStateCode, string $connectionId, Context $context): ?string;

    public function getTaxUuidByCriteria(string $connectionId, string $sourceId, float $taxRate, string $name, Context $context): ?string;

    public function getTaxRuleUuidByCriteria(string $connectionId, string $sourceId, string $taxId, string $countryId, string $taxRuleTypeId, Context $context): ?string;

    public function getTaxRuleTypeUuidByCriteria(string $connectionId, string $sourceId, string $technicalName, Context $context): ?string;
}
