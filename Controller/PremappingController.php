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
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationContextFactoryInterface;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationAssistant\Migration\Service\PremappingServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
#[Package('services-settings')]
class PremappingController extends AbstractController
{
    public function __construct(
        private readonly PremappingServiceInterface $premappingService,
        private readonly EntityRepository $migrationRunRepo,
        private readonly MigrationContextFactoryInterface $migrationContextFactory
    ) {
    }

    #[Route(path: '/api/_action/migration/generate-premapping', name: 'api.admin.migration.generate-premapping', methods: ['POST'], defaults: ['_acl' => ['admin']])]
    public function generatePremapping(Request $request, Context $context): JsonResponse
    {
        $runUuid = $request->request->getAlnum('runUuid');

        if ($runUuid === '') {
            throw new MigrationContextPropertyMissingException('runUuid');
        }

        /** @var SwagMigrationRunEntity|null $run */
        $run = $this->migrationRunRepo->search(new Criteria([$runUuid]), $context)->first();

        if ($run === null) {
            throw new EntityNotExistsException(SwagMigrationRunEntity::class, $runUuid);
        }

        $migrationContext = $this->migrationContextFactory->create($run);

        if ($migrationContext === null) {
            throw new EntityNotExistsException(MigrationContext::class, $runUuid);
        }

        return new JsonResponse($this->premappingService->generatePremapping($context, $migrationContext, $run));
    }

    #[Route(path: '/api/_action/migration/write-premapping', name: 'api.admin.migration.write-premapping', methods: ['POST'], defaults: ['_acl' => ['admin']])]
    public function writePremapping(Request $request, Context $context): JsonResponse
    {
        $runUuid = $request->request->getAlnum('runUuid');

        /** @var array|mixed $premapping */
        $premapping = $request->request->all('premapping');

        if ($runUuid === '') {
            throw new MigrationContextPropertyMissingException('runUuid');
        }

        if (empty($premapping)) {
            throw new MigrationContextPropertyMissingException('premapping');
        }

        /** @var SwagMigrationRunEntity|null $run */
        $run = $this->migrationRunRepo->search(new Criteria([$runUuid]), $context)->first();

        if ($run === null) {
            throw new EntityNotExistsException(SwagMigrationRunEntity::class, $runUuid);
        }

        $migrationContext = $this->migrationContextFactory->create($run);

        if ($migrationContext === null) {
            throw new EntityNotExistsException(MigrationContext::class, $runUuid);
        }

        $this->premappingService->writePremapping($context, $migrationContext, $premapping);

        return new JsonResponse();
    }
}
