<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Controller;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Exception\EntityNotExistsException;
use SwagMigrationAssistant\Exception\MigrationContextPropertyMissingException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionCollection;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationContextFactoryInterface;
use SwagMigrationAssistant\Migration\Service\PremappingServiceInterface;
use SwagMigrationAssistant\Migration\Setting\GeneralSettingCollection;
use SwagMigrationAssistant\Migration\Setting\GeneralSettingEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
#[Package('services-settings')]
class PremappingController extends AbstractController
{
    /**
     * @param EntityRepository<GeneralSettingCollection>          $generalSettingRepository
     * @param EntityRepository<SwagMigrationConnectionCollection> $migrationConnectionRepository
     */
    public function __construct(
        private readonly PremappingServiceInterface $premappingService,
        private readonly MigrationContextFactoryInterface $migrationContextFactory,
        private readonly EntityRepository $generalSettingRepository,
        private readonly EntityRepository $migrationConnectionRepository,
    ) {
    }

    #[Route(path: '/api/_action/migration/generate-premapping', name: 'api.admin.migration.generate-premapping', methods: ['POST'], defaults: ['_acl' => ['admin']])]
    public function generatePremapping(Request $request, Context $context): JsonResponse
    {
        $dataSelectionIds = $request->request->all('dataSelectionIds');
        if (empty($dataSelectionIds)) {
            throw new MigrationContextPropertyMissingException('dataSelectionIds');
        }

        $migrationContext = $this->constructMigrationContextByActiveConnection($context);

        return new JsonResponse($this->premappingService->generatePremapping($context, $migrationContext, $dataSelectionIds));
    }

    #[Route(path: '/api/_action/migration/write-premapping', name: 'api.admin.migration.write-premapping', methods: ['POST'], defaults: ['_acl' => ['admin']])]
    public function writePremapping(Request $request, Context $context): Response
    {
        $premapping = $request->request->all('premapping');

        if (empty($premapping)) {
            throw new MigrationContextPropertyMissingException('premapping');
        }

        $migrationContext = $this->constructMigrationContextByActiveConnection($context);

        $this->premappingService->writePremapping($context, $migrationContext, $premapping);

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    private function constructMigrationContextByActiveConnection(Context $context): MigrationContext
    {
        $settings = $this->generalSettingRepository->search(new Criteria(), $context)->first();
        if (!$settings instanceof GeneralSettingEntity) {
            throw new EntityNotExistsException(GeneralSettingEntity::class, 'Default');
        }

        $connection = $this->migrationConnectionRepository->search(new Criteria([$settings->getSelectedConnectionId()]), $context)->first();
        if (!$connection instanceof SwagMigrationConnectionEntity) {
            throw new EntityNotExistsException(SwagMigrationConnectionEntity::class, $settings->getSelectedConnectionId());
        }

        return $this->migrationContextFactory->createByConnection($connection);
    }
}
