<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Mapping;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\RepositoryInterface;
use Shopware\Core\Framework\ORM\Search\Criteria;
use Shopware\Core\Framework\ORM\Search\Query\TermQuery;
use Shopware\Core\Framework\Struct\Uuid;
use Shopware\Core\System\Country\CountryDefinition;
use Shopware\Core\System\Country\CountryStruct;
use Shopware\Core\System\Currency\CurrencyStruct;
use Shopware\Core\System\Language\LanguageDefinition;
use Shopware\Core\System\Language\LanguageStruct;
use Shopware\Core\System\Locale\LocaleStruct;
use SwagMigrationNext\Exception\LocaleNotFoundException;

class MappingService implements MappingServiceInterface
{
    /**
     * @var RepositoryInterface
     */
    protected $migrationMappingRepo;

    /**
     * @var RepositoryInterface
     */
    protected $localeRepository;

    /**
     * @var RepositoryInterface
     */
    protected $languageRepository;

    /**
     * @var RepositoryInterface
     */
    protected $countryRepository;

    protected $uuids = [];

    protected $writeArray = [];

    /**
     * @var RepositoryInterface
     */
    private $currencyRepository;

    public function __construct(
        RepositoryInterface $migrationMappingRepo,
        RepositoryInterface $localeRepository,
        RepositoryInterface $languageRepository,
        RepositoryInterface $countryRepository,
        RepositoryInterface $currencyRepository
    ) {
        $this->migrationMappingRepo = $migrationMappingRepo;
        $this->localeRepository = $localeRepository;
        $this->languageRepository = $languageRepository;
        $this->countryRepository = $countryRepository;
        $this->currencyRepository = $currencyRepository;
    }

    public function getUuid(string $profile, string $entityName, string $oldId, Context $context): ?string
    {
        if (isset($this->uuids[$profile][$entityName][$oldId])) {
            return $this->uuids[$profile][$entityName][$oldId];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new TermQuery('profile', $profile));
        $criteria->addFilter(new TermQuery('entity', $entityName));
        $criteria->addFilter(new TermQuery('oldIdentifier', $oldId));
        $criteria->setLimit(1);
        $result = $this->migrationMappingRepo->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var SwagMigrationMappingStruct $element */
            $element = $result->getEntities()->first();
            $uuid = $element->getEntityUuid();

            $this->uuids[$profile][$entityName][$oldId] = $uuid;

            return $uuid;
        }

        return null;
    }

    public function createNewUuid(
        string $profile,
        string $entityName,
        string $oldId,
        Context $context,
        array $additionalData = null,
        string $newUuid = null
    ): string {
        $uuid = $this->getUuid($profile, $entityName, $oldId, $context);
        if ($uuid !== null) {
            return $uuid;
        }

        $uuid = Uuid::uuid4()->getHex();
        if ($newUuid !== null) {
            $uuid = $newUuid;
        }

        $this->saveMapping(
            [
                'profile' => $profile,
                'entity' => $entityName,
                'oldIdentifier' => $oldId,
                'entityUuid' => $uuid,
                'additionalData' => $additionalData,
            ]
        );

        return $uuid;
    }

    public function getLanguageUuid(string $profile, string $localeCode, Context $context): array
    {
        // TODO: Revert this if the core can handle translations in a right way
        $localeCode = 'en_GB';
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

    public function getCountryUuid(string $oldId, string $iso, string $iso3, string $profile, Context $context): ?string
    {
        $countryUuid = $this->getUuid($profile, CountryDefinition::getEntityName(), $oldId, $context);

        if ($countryUuid !== null) {
            return $countryUuid;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new TermQuery('iso', $iso));
        $criteria->addFilter(new TermQuery('iso3', $iso3));
        $criteria->setLimit(1);
        $result = $this->countryRepository->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var CountryStruct $element */
            $element = $result->getEntities()->first();

            $countryUuid = $element->getId();

            $this->saveMapping(
                [
                    'profile' => $profile,
                    'entity' => CountryDefinition::getEntityName(),
                    'oldIdentifier' => $oldId,
                    'entityUuid' => $countryUuid,
                ]
            );

            return $countryUuid;
        }

        return null;
    }

    public function getCurrencyUuid(string $oldShortName, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new TermQuery('shortName', $oldShortName));
        $criteria->setLimit(1);
        $result = $this->currencyRepository->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var CurrencyStruct $element */
            $element = $result->getEntities()->first();

            return $element->getId();
        }

        return null;
    }

    public function deleteMapping(string $entityUuid, string $profile, Context $context): void
    {
        foreach ($this->writeArray as $writeMapping) {
            if ($writeMapping['profile'] === $profile && $writeMapping['entityUuid'] === $entityUuid) {
                unset($writeMapping);
                break;
            }
        }

        $criteria = new Criteria();
        $criteria->addFilter(new TermQuery('entityUuid', $entityUuid));
        $criteria->addFilter(new TermQuery('profile', $profile));
        $criteria->setLimit(1);
        $result = $this->migrationMappingRepo->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var SwagMigrationMappingStruct $element */
            $element = $result->getEntities()->first();

            $this->migrationMappingRepo->delete([['id' => $element->getId()]], $context);
        }
    }

    public function writeMapping(Context $context): void
    {
        if (empty($this->writeArray)) {
            return;
        }

        $this->migrationMappingRepo->create($this->writeArray, $context);
        $this->writeArray = [];
        $this->uuids = [];
    }

    protected function saveMapping(array $mapping): void
    {
        $profile = $mapping['profile'];
        $entity = $mapping['entity'];
        $oldId = $mapping['oldIdentifier'];
        $uuid = $mapping['entityUuid'];

        $this->uuids[$profile][$entity][$oldId] = $uuid;
        $this->writeArray[] = $mapping;
    }

    private function searchLanguageInMapping(string $localeCode, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new TermQuery('entity', LanguageDefinition::getEntityName()));
        $criteria->addFilter(new TermQuery('oldIdentifier', $localeCode));
        $criteria->setLimit(1);
        $result = $this->migrationMappingRepo->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var SwagMigrationMappingStruct $element */
            $element = $result->getEntities()->first();

            return $element->getEntityUuid();
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
        $criteria->setLimit(1);
        $result = $this->localeRepository->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var LocaleStruct $element */
            $element = $result->getEntities()->first();

            return $element->getId();
        }

        throw new LocaleNotFoundException($localeCode);
    }

    private function searchLanguageByLocale(string $localeUuid, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new TermQuery('localeId', $localeUuid));
        $criteria->setLimit(1);
        $result = $this->languageRepository->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var LanguageStruct $element */
            $element = $result->getEntities()->first();

            return $element->getId();
        }

        return null;
    }
}
