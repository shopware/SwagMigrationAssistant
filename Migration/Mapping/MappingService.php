<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Mapping;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\RepositoryInterface;
use Shopware\Core\Framework\ORM\Search\Criteria;
use Shopware\Core\Framework\ORM\Search\EntitySearchResult;
use Shopware\Core\Framework\ORM\Search\Query\TermQuery;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Framework\Struct\Uuid;

class MappingService implements MappingServiceInterface
{
    /**
     * @var RepositoryInterface
     */
    private $migrationMappingRepo;

    /**
     * @var string
     */
    private $profile;

    /**
     * @var array
     */
    private $writeMapping = [];

    /**
     * @var array
     */
    private $readMapping = [];

    public function __construct(RepositoryInterface $migrationMappingRepo)
    {
        $this->migrationMappingRepo = $migrationMappingRepo;
    }

    public function readExistingMappings(Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new TermQuery('profile', $this->profile));
        /** @var EntitySearchResult $result */
        $result = $this->migrationMappingRepo->search($criteria, $context);

        if ($result->getTotal() <= 0) {
            return;
        }

        /** @var ArrayStruct $mapping */
        foreach ($result->getEntities()->getElements() as $mapping) {
            $this->readMapping[$mapping->get('entity')][$mapping->get('oldIdentifier')] = $mapping->get('entityUuid');
        }
    }

    public function createNewUuid(string $entityName, string $oldId): string
    {
        if (isset($this->readMapping[$entityName][$oldId])) {
            return $this->readMapping[$entityName][$oldId];
        }

        if ($this->profile === null) {
            throw new ProfileForMappingMissingException('Profile for mapping is missing. Please set a profile first');
        }

        $uuid = Uuid::uuid4()->getHex();
        $this->readMapping[$entityName][$oldId] = $uuid;
        $this->writeMapping[] = [
            'profile' => $this->profile,
            'entity' => $entityName,
            'oldIdentifier' => $oldId,
            'entityUuid' => $uuid,
        ];

        return $uuid;
    }

    public function writeMapping(Context $context): void
    {
        if (empty($this->writeMapping)) {
            return;
        }

        $this->migrationMappingRepo->create($this->writeMapping, $context);
    }

    public function setProfile(string $profile): void
    {
        $this->profile = $profile;
    }
}
