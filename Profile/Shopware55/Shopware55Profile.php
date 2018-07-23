<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\RepositoryInterface;
use SwagMigrationNext\Gateway\GatewayInterface;
use SwagMigrationNext\Migration\EntityRelationMapping;
use SwagMigrationNext\Profile\ProfileInterface;
use SwagMigrationNext\Profile\Shopware55\Converter\ConverterRegistryInterface;

class Shopware55Profile implements ProfileInterface
{
    public const PROFILE_NAME = 'shopware55';

    /**
     * @var RepositoryInterface
     */
    private $migrationDataRepo;

    /**
     * @var ConverterRegistryInterface
     */
    private $converterRegistry;

    public function __construct(RepositoryInterface $migrationDataRepo, ConverterRegistryInterface $converterRegistry)
    {
        $this->migrationDataRepo = $migrationDataRepo;
        $this->converterRegistry = $converterRegistry;
    }

    public function getName(): string
    {
        return self::PROFILE_NAME;
    }

    public function collectData(GatewayInterface $gateway, string $entityName, Context $context): void
    {
        /** @var array[] $data */
        $data = $gateway->read($entityName);

        $additionalRelationData = [];
        $createData = [];
        foreach (EntityRelationMapping::getMapping($entityName) as $key => $entity) {
            $currentEntityName = $entity['entity'];
            $currentEntityRelation = $entity['relation'];

            $converter = $this->converterRegistry->getConverter($currentEntityName);

            if ($currentEntityRelation === EntityRelationMapping::MANYTOONE || $currentEntityRelation === EntityRelationMapping::MAIN) {
                foreach ($data as $id => $item) {
                    $item = $item[$currentEntityName];

                    $convertStruct = $converter->convert($item, $additionalRelationData);

                    if (!isset($additionalRelationData[$currentEntityName][$convertStruct->getOldId()])) {
                        $createData[] = [
                            'entityName' => $currentEntityName,
                            'profile' => $this->getName(),
                            'raw' => $item,
                            'converted' => $convertStruct->getConverted(),
                            'unmapped' => $convertStruct->getUnmapped(),
                            'oldIdentifier' => $convertStruct->getOldId(),
                            'entityUuid' => $convertStruct->getUuid(),
                        ];

                        $additionalRelationData[$currentEntityName][$convertStruct->getOldId()] = $convertStruct->getUuid();
                    }
                }
            }

            if ($currentEntityRelation === EntityRelationMapping::ONETOMANY) {
                foreach ($data as $row) {
                    $row = $row[$currentEntityName];
                    foreach ($row as $item) {
                        $convertStruct = $converter->convert($item, $additionalRelationData);

                        if (!isset($additionalRelationData[$currentEntityName][$convertStruct->getOldId()])) {
                            $createData[] = [
                                'entityName' => $currentEntityName,
                                'profile' => $this->getName(),
                                'raw' => $item,
                                'converted' => $convertStruct->getConverted(),
                                'unmapped' => $convertStruct->getUnmapped(),
                                'oldIdentifier' => $convertStruct->getOldId(),
                                'entityUuid' => $convertStruct->getUuid(),
                            ];

                            $additionalRelationData[$currentEntityName][$convertStruct->getOldId()] = $convertStruct->getUuid();
                        }
                    }
                }
            }
        }

        $this->migrationDataRepo->upsert($createData, $context);
    }
}
