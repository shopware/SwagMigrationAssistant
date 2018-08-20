<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Mapping;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\RepositoryInterface;
use Shopware\Core\Framework\ORM\Search\Criteria;
use Shopware\Core\Framework\ORM\Search\Query\TermQuery;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Framework\Struct\Uuid;
use Shopware\Core\System\Country\CountryDefinition;
use Shopware\Core\System\Language\LanguageDefinition;
use SwagMigrationNext\Exception\LocaleNotFoundException;

class MappingService implements MappingServiceInterface
{
    /**
     * @var RepositoryInterface
     */
    private $migrationMappingRepo;

    /**
     * @var RepositoryInterface
     */
    private $localeRepository;

    /**
     * @var RepositoryInterface
     */
    private $languageRepository;

    /**
     * @var RepositoryInterface
     */
    private $paymentRepository;

    /**
     * @var RepositoryInterface
     */
    private $countryRepository;

    /**
     * @var RepositoryInterface
     */
    private $orderStateRepository;

    /**
     * @var RepositoryInterface
     */
    private $transactionStateRepository;

    public function __construct(
        RepositoryInterface $migrationMappingRepo,
        RepositoryInterface $localeRepository,
        RepositoryInterface $languageRepository,
        RepositoryInterface $paymentRepository,
        RepositoryInterface $countryRepository,
        RepositoryInterface $orderStateRepository,
        RepositoryInterface $transactionStateRepository
    ) {
        $this->migrationMappingRepo = $migrationMappingRepo;
        $this->localeRepository = $localeRepository;
        $this->languageRepository = $languageRepository;
        $this->paymentRepository = $paymentRepository;
        $this->countryRepository = $countryRepository;
        $this->orderStateRepository = $orderStateRepository;
        $this->transactionStateRepository = $transactionStateRepository;
    }

    public function getUuid(string $profile, string $entityName, string $oldId, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new TermQuery('profile', $profile));
        $criteria->addFilter(new TermQuery('entity', $entityName));
        $criteria->addFilter(new TermQuery('oldIdentifier', $oldId));
        $result = $this->migrationMappingRepo->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var ArrayStruct $element */
            $element = $result->getEntities()->first();

            return $element->get('entityUuid');
        }

        return null;
    }

    public function createNewUuid(
        string $profile,
        string $entityName,
        string $oldId,
        Context $context,
        array $additionalData = null
    ): string {
        $uuid = $this->getUuid($profile, $entityName, $oldId, $context);
        if ($uuid !== null) {
            return $uuid;
        }

        $uuid = Uuid::uuid4()->getHex();
        $this->writeMapping(
            [
                [
                    'profile' => $profile,
                    'entity' => $entityName,
                    'oldIdentifier' => $oldId,
                    'entityUuid' => $uuid,
                    'additionalData' => $additionalData,
                ],
            ],
            $context
        );

        return $uuid;
    }

    public function getLanguageUuid(string $profile, string $localeCode, Context $context): array
    {
        $languageUuid = $this->searchLanguageInMapping($localeCode, $context);
        $localeUuid = $this->searchLocale($localeCode, $context);

        if ($languageUuid !== null) {
            return [
                'uuid' => $languageUuid,
                'createData' => [
                    'localeId' => $localeUuid,
                    'localeCode' => $localeCode,
                ],
            ];
        }

        $languageUuid = $this->searchLanguageByLocale($localeUuid, $context);

        if ($languageUuid !== null) {
            return ['uuid' => $languageUuid];
        }

        return [
            'uuid' => $this->createNewUuid($profile, LanguageDefinition::getEntityName(), $localeCode, $context),
            'createData' => [
                'localeId' => $localeUuid,
                'localeCode' => $localeCode,
            ],
        ];
    }

    public function getPaymentUuid(string $technicalName, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new TermQuery('technicalName', $technicalName));
        $result = $this->paymentRepository->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var ArrayStruct $element */
            $element = $result->getEntities()->first();

            return $element->getId();
        }

        return null;
    }

    public function getCountryUuid(string $oldId, string $iso, string $iso3, string $profile, Context $context): ?string
    {
        $countryUuid = $this->getUuid($profile, CountryDefinition::getEntityName(), $oldId, $context);

        if ($countryUuid !== null) {
            return $countryUuid;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new TermQuery('iso', $iso));
        $criteria->addFilter(new TermQuery('iso3', $iso3));
        $result = $this->countryRepository->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var ArrayStruct $element */
            $element = $result->getEntities()->first();

            $countryUuid = $element->getId();

            $this->writeMapping(
                [
                    [
                        'profile' => $profile,
                        'entity' => CountryDefinition::getEntityName(),
                        'oldIdentifier' => $oldId,
                        'entityUuid' => $countryUuid,
                    ],
                ],
                $context
            );

            return $countryUuid;
        }

        return null;
    }

    public function getOrderStateUuid(int $oldStateId, Context $context): ?string
    {
        $criteria = new Criteria();
        switch ($oldStateId) {
            case -1: // cancelled
                $criteria->addFilter(new TermQuery('position', 3));
                break;
            case 0: // open
                $criteria->addFilter(new TermQuery('position', 1));
                break;
            case 1: // in_process
                $criteria->addFilter(new TermQuery('position', 4));
                break;
            case 2: // completed
                $criteria->addFilter(new TermQuery('position', 2));
                break;
            case 3: // partially_completed
                $criteria->addFilter(new TermQuery('position', 5));
                break;
            case 4: // cancelled_rejected
                $criteria->addFilter(new TermQuery('position', 6));
                break;
            case 5: // ready_for_delivery
                $criteria->addFilter(new TermQuery('position', 7));
                break;
            case 6: // partially_delivered
                $criteria->addFilter(new TermQuery('position', 8));
                break;
            case 7: // completely_delivered
                $criteria->addFilter(new TermQuery('position', 9));
                break;
            case 8: // clarification_required
                $criteria->addFilter(new TermQuery('position', 10));
                break;
            default:
                return null;
        }

        $result = $this->orderStateRepository->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var ArrayStruct $element */
            $element = $result->getEntities()->first();

            return $element->getId();
        }

        return null;
    }

    public function getTransactionStateUuid(int $oldStateId, Context $context): ?string
    {
        $criteria = new Criteria();
        switch ($oldStateId) {
            case 9: // partially_invoiced
                $criteria->addFilter(new TermQuery('position', 4));
                break;
            case 10: // completely_invoiced
                $criteria->addFilter(new TermQuery('position', 5));
                break;
            case 11: // partially_paid
                $criteria->addFilter(new TermQuery('position', 6));
                break;
            case 12: // completely_paid
                $criteria->addFilter(new TermQuery('position', 7));
                break;
            case 13: // 1st_reminder
                $criteria->addFilter(new TermQuery('position', 8));
                break;
            case 14: // 2nd_reminder
                $criteria->addFilter(new TermQuery('position', 9));
                break;
            case 15: // 3rd_reminder
                $criteria->addFilter(new TermQuery('position', 10));
                break;
            case 16: // encashment
                $criteria->addFilter(new TermQuery('position', 11));
                break;
            case 17: // open
                $criteria->addFilter(new TermQuery('position', 3));
                break;
            case 18: // reserved
                $criteria->addFilter(new TermQuery('position', 12));
                break;
            case 19: // delayed
                $criteria->addFilter(new TermQuery('position', 13));
                break;
            case 20: // re_crediting
                $criteria->addFilter(new TermQuery('position', 14));
                break;
            case 21: // review_necessary
                $criteria->addFilter(new TermQuery('position', 15));
                break;
            case 30: // no_credit_approved
                $criteria->addFilter(new TermQuery('position', 16));
                break;
            case 31: // the_credit_has_been_preliminarily_accepted
                $criteria->addFilter(new TermQuery('position', 17));
                break;
            case 32: // the_credit_has_been_accepted
                $criteria->addFilter(new TermQuery('position', 18));
                break;
            case 33: //the_payment_has_been_ordered
                $criteria->addFilter(new TermQuery('position', 19));
                break;
            case 34: // a_time_extension_has_been_registered
                $criteria->addFilter(new TermQuery('position', 20));
                break;
            case 35: // the_process_has_been_cancelled
                $criteria->addFilter(new TermQuery('position', 2));
                break;
            default:
                return null;
        }

        $result = $this->transactionStateRepository->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var ArrayStruct $element */
            $element = $result->getEntities()->first();

            return $element->getId();
        }

        return null;
    }

    private function writeMapping(array $writeMapping, Context $context): void
    {
        $this->migrationMappingRepo->create($writeMapping, $context);
    }

    private function searchLanguageInMapping(string $localeCode, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new TermQuery('entity', LanguageDefinition::getEntityName()));
        $criteria->addFilter(new TermQuery('oldIdentifier', $localeCode));
        $result = $this->migrationMappingRepo->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var ArrayStruct $element */
            $element = $result->getEntities()->first();

            return $element->get('entityUuid');
        }

        return null;
    }

    /**
     * @throws LocaleNotFoundException
     */
    private function searchLocale(string $localeCode, Context $context): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new TermQuery('code', $localeCode));
        $result = $this->localeRepository->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var ArrayStruct $element */
            $element = $result->getEntities()->first();

            return $element->getId();
        }

        throw new LocaleNotFoundException($localeCode);
    }

    private function searchLanguageByLocale(string $localeUuid, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new TermQuery('localeId', $localeUuid));
        $result = $this->languageRepository->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var ArrayStruct $element */
            $element = $result->getEntities()->first();

            return $element->getId();
        }

        return null;
    }
}
