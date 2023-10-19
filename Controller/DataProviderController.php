<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Controller;

use Shopware\Core\Checkout\Document\Service\DocumentGenerator;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\Exception\MissingRequestParameterException;
use SwagMigrationAssistant\DataProvider\Provider\ProviderRegistryInterface;
use SwagMigrationAssistant\DataProvider\Service\EnvironmentServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
#[Package('services-settings')]
class DataProviderController
{
    public function __construct(
        private readonly ProviderRegistryInterface $providerRegistry,
        private readonly EnvironmentServiceInterface $environmentService,
        private readonly DocumentGenerator $documentGenerator
    ) {
    }

    #[Route(path: '/api/_action/data-provider/get-environment', name: 'api.admin.data-provider.get-environment', methods: ['GET'], defaults: ['_acl' => ['admin']])]
    public function getEnvironment(Context $context): Response
    {
        $data = $this->environmentService->getEnvironmentData($context);

        return new JsonResponse($data);
    }

    #[Route(path: '/api/_action/data-provider/get-data', name: 'api.admin.data-provider.get-data', methods: ['GET'], defaults: ['_acl' => ['admin']])]
    public function getData(Request $request, Context $context): Response
    {
        $identifier = (string) $request->query->get('identifier');
        $limit = (int) $request->query->get('limit', '250');
        $offset = (int) $request->query->get('offset', '0');

        if ($identifier === '') {
            throw new MissingRequestParameterException('identifier');
        }

        $provider = $this->providerRegistry->getDataProvider($identifier);
        $data = $provider->getProvidedData($limit, $offset, $context);

        return new JsonResponse($data);
    }

    #[Route(path: '/api/_action/data-provider/get-total', name: 'api.admin.data-provider.get-total', methods: ['GET'], defaults: ['_acl' => ['admin']])]
    public function getTotal(Request $request, Context $context): Response
    {
        $providerArray = $this->providerRegistry->getAllDataProviders();

        $totals = [];
        foreach ($providerArray as $identifier => $provider) {
            $totals[$identifier] = $provider->getProvidedTotal($context);
        }

        return new JsonResponse($totals);
    }

    #[Route(path: '/api/_action/data-provider/get-table', name: 'api.admin.data-provider.get-table', methods: ['GET'], defaults: ['_acl' => ['admin']])]
    public function getTable(Request $request, Context $context): Response
    {
        $identifier = (string) $request->query->get('identifier');

        if ($identifier === '') {
            throw new MissingRequestParameterException('identifier');
        }

        $provider = $this->providerRegistry->getDataProvider($identifier);
        $data = $provider->getProvidedTable($context);

        return new JsonResponse($data);
    }

    #[Route(path: '/api/_action/data-provider/generate-document', name: 'api.admin.data-provider.generate-document', methods: ['GET'], defaults: ['_acl' => ['admin']])]
    public function generateDocument(Request $request, Context $context): JsonResponse
    {
        $identifier = (string) $request->query->get('identifier');

        if ($identifier === '') {
            throw new MissingRequestParameterException('identifier');
        }

        $generatedDocument = $this->documentGenerator->readDocument($identifier, $context);

        if ($generatedDocument === null) {
            throw new \Exception('Document could not be generated.');
        }

        return new JsonResponse([
            'file_blob' => \base64_encode($generatedDocument->getContent()),
            'file_name' => $generatedDocument->getName(),
            'file_content_type' => $generatedDocument->getContentType(),
        ]);
    }
}
