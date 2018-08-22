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

    public function __construct(
        RepositoryInterface $migrationMappingRepo,
        RepositoryInterface $localeRepository,
        RepositoryInterface $languageRepository,
        RepositoryInterface $countryRepository
    ) {
        $this->migrationMappingRepo = $migrationMappingRepo;
        $this->localeRepository = $localeRepository;
        $this->languageRepository = $languageRepository;
        $this->countryRepository = $countryRepository;
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

    protected function writeMapping(array $writeMapping, Context $context): void
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
