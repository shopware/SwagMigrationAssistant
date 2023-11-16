<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Converter;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\OrderDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;

#[Package('services-settings')]
class OrderConverter extends ShopwareConverter
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware6MajorProfile::PROFILE_NAME
            && $this->getDataSetEntity($migrationContext) === OrderDataSet::getEntity();
    }

    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::ORDER,
            $data['id'],
            $converted['id']
        );

        $converted['currencyId'] = $this->getMappingIdFacade(
            DefaultEntities::CURRENCY,
            $converted['currencyId']
        );

        $converted['languageId'] = $this->getMappingIdFacade(
            DefaultEntities::LANGUAGE,
            $converted['languageId']
        );

        $converted['salesChannelId'] = $this->getMappingIdFacade(
            DefaultEntities::SALES_CHANNEL,
            $converted['salesChannelId']
        );

        $converted['orderCustomer']['salutationId'] = $this->getMappingIdFacade(
            DefaultEntities::SALUTATION,
            $converted['orderCustomer']['salutationId']
        );

        $converted['stateId'] = $this->mappingService->getStateMachineStateUuid(
            $converted['stateId'],
            $converted['stateMachineState']['technicalName'],
            $converted['stateMachineState']['stateMachine']['technicalName'],
            $this->migrationContext,
            $this->context
        );
        unset($converted['stateMachineState']);

        foreach ($converted['deliveries'] as &$delivery) {
            $delivery['stateId'] = $this->mappingService->getStateMachineStateUuid(
                $delivery['stateId'],
                $delivery['stateMachineState']['technicalName'],
                $delivery['stateMachineState']['stateMachine']['technicalName'],
                $this->migrationContext,
                $this->context
            );
            unset($delivery['stateMachineState']);

            if (isset($delivery['shippingOrderAddress']['countryStateId'])) {
                $delivery['shippingOrderAddress']['countryStateId'] = $this->getMappingIdFacade(DefaultEntities::COUNTRY_STATE, $delivery['shippingOrderAddress']['countryStateId']);
            }

            $delivery['shippingOrderAddress']['countryId'] = $this->getMappingIdFacade(DefaultEntities::COUNTRY, $delivery['shippingOrderAddress']['countryId']);
            $delivery['shippingOrderAddress']['salutationId'] = $this->getMappingIdFacade(DefaultEntities::SALUTATION, $delivery['shippingOrderAddress']['salutationId']);
        }
        unset($delivery);

        foreach ($converted['transactions'] as &$transaction) {
            $transaction['stateId'] = $this->mappingService->getStateMachineStateUuid(
                $transaction['stateId'],
                $transaction['stateMachineState']['technicalName'],
                $transaction['stateMachineState']['stateMachine']['technicalName'],
                $this->migrationContext,
                $this->context
            );
            unset($transaction['stateMachineState']);
        }
        unset($transaction);

        $this->updateAssociationIds(
            $converted['transactions'],
            DefaultEntities::PAYMENT_METHOD,
            'paymentMethodId',
            DefaultEntities::ORDER
        );

        $this->updateAssociationIds(
            $converted['addresses'],
            DefaultEntities::COUNTRY,
            'countryId',
            DefaultEntities::ORDER
        );

        $this->updateAssociationIds(
            $converted['addresses'],
            DefaultEntities::COUNTRY_STATE,
            'countryStateId',
            DefaultEntities::ORDER
        );

        $this->updateAssociationIds(
            $converted['addresses'],
            DefaultEntities::SALUTATION,
            'salutationId',
            DefaultEntities::ORDER
        );

        $this->updateAssociationIds(
            $converted['lineItems'],
            DefaultEntities::PRODUCT,
            'productId',
            DefaultEntities::ORDER,
            false,
            true
        );

        $this->updateAssociationIds(
            $converted['lineItems'],
            DefaultEntities::MEDIA,
            'coverId',
            DefaultEntities::ORDER,
            false,
            true
        );

        $this->updateLineItems($converted['lineItems']);

        if (!isset($converted['price']['rawTotal']) && isset($converted['price']['totalPrice'])) {
            $converted['price']['rawTotal'] = $converted['price']['totalPrice'];
        }

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }

    private function updateLineItems(array &$lineItems): void
    {
        foreach ($lineItems as &$converted) {
            if (!isset($converted['productId'])) {
                unset($converted['referencedId'], $converted['payload']['productNumber']);
            }

            if (!isset($converted['payload'])) {
                continue;
            }

            if (isset($converted['payload']['taxId'])) {
                $taxId = $this->getMappingIdFacade(
                    DefaultEntities::TAX,
                    $converted['payload']['taxId']
                );

                if ($taxId !== null) {
                    $converted['payload']['taxId'] = $taxId;
                }
            }
        }
    }
}
