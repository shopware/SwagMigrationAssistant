<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\RepositoryInterface;
use SwagMigrationNext\Gateway\GatewayServiceInterface;
use SwagMigrationNext\Profile\ProfileRegistryInterface;

class MigrationService
{
    /**
     * @var ProfileRegistryInterface
     */
    private $profileRegistry;

    /**
     * @var GatewayServiceInterface
     */
    private $gatewayService;

    /**
     * @var RepositoryInterface
     */
    private $migrationDataRepo;

    public function __construct(ProfileRegistryInterface $profileRegistry, GatewayServiceInterface $gatewayService, RepositoryInterface $migrationDataRepo)
    {
        $this->profileRegistry = $profileRegistry;
        $this->gatewayService = $gatewayService;
        $this->migrationDataRepo = $migrationDataRepo;
    }

    public function migrate(MigrationContext $migrationContext): void
    {
        $profile = $this->profileRegistry->getProfile($migrationContext->getProfileName());

        $gateway = $this->gatewayService->getGateway($migrationContext);

        $data = $profile->getData($gateway, $migrationContext);
        $createData = [];
        foreach ($data as $item) {
            $createData[] = [
                'entityType' => $migrationContext->getEntityType(),
                'profile' => $migrationContext->getProfileName(),
                'raw' => $item,
            ];
        }

        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $this->migrationDataRepo->create($createData, $context);
    }
}
