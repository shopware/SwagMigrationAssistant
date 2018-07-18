<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\RepositoryInterface;
use SwagMigrationNext\Gateway\GatewayInterface;
use SwagMigrationNext\Migration\MigrationContext;
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

    public function collectData(GatewayInterface $gateway, MigrationContext $migrationContext, Context $context): void
    {
        $entityName = $migrationContext->getEntityName();
        $converter = $this->converterRegistry->getConverter($entityName);
        /** @var array[] $data */
        $data = $gateway->read($entityName);

        if (!array_key_exists('data', $data)) {
            return;
        }

        $createData = [];
        foreach ($data['data'] as $item) {
            $convertStruct = $converter->convert($item);
            $createData[] = [
                'entityName' => $entityName,
                'profile' => $this->getName(),
                'raw' => $item,
                'converted' => $convertStruct->getEntity(),
                'unmapped' => $convertStruct->getUnmapped(),
            ];
        }

        $this->migrationDataRepo->create($createData, $context);
    }
}
