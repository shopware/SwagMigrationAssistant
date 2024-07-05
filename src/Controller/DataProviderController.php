<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Controller;

use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToGenerateTemporaryUrl;
use Psr\Http\Message\StreamInterface;
use Shopware\Core\Checkout\Document\Service\DocumentGenerator;
use Shopware\Core\Content\Media\MediaCollection;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Media\MediaException;
use Shopware\Core\Content\Media\MediaService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\RoutingException;
use SwagMigrationAssistant\DataProvider\Provider\ProviderRegistryInterface;
use SwagMigrationAssistant\DataProvider\Service\EnvironmentServiceInterface;
use SwagMigrationAssistant\Exception\MigrationException;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
#[Package('services-settings')]
final class DataProviderController
{
    /**
     * @param EntityRepository<MediaCollection> $mediaRepository
     */
    public function __construct(
        private readonly ProviderRegistryInterface $providerRegistry,
        private readonly EnvironmentServiceInterface $environmentService,
        private readonly DocumentGenerator $documentGenerator,
        private readonly EntityRepository $mediaRepository,
        private readonly MediaService $mediaService,
        private readonly FilesystemOperator $privateFilesystem,
    ) {
    }

    #[Route(
        path: '/api/_action/data-provider/get-environment',
        name: 'api.admin.data-provider.get-environment',
        defaults: ['_acl' => ['admin']],
        methods: [Request::METHOD_GET]
    )]
    public function getEnvironment(Context $context): JsonResponse
    {
        $data = $this->environmentService->getEnvironmentData($context);

        return new JsonResponse($data);
    }

    #[Route(
        path: '/api/_action/data-provider/get-data',
        name: 'api.admin.data-provider.get-data',
        defaults: ['_acl' => ['admin']],
        methods: [Request::METHOD_GET]
    )]
    public function getData(Request $request, Context $context): JsonResponse
    {
        $identifier = (string) $request->query->get('identifier');
        $limit = (int) $request->query->get('limit', '250');
        $offset = (int) $request->query->get('offset', '0');

        if ($identifier === '') {
            throw RoutingException::missingRequestParameter('identifier');
        }

        $provider = $this->providerRegistry->getDataProvider($identifier);
        $data = $provider->getProvidedData($limit, $offset, $context);

        return new JsonResponse($data);
    }

    #[Route(
        path: '/api/_action/data-provider/get-total',
        name: 'api.admin.data-provider.get-total',
        defaults: ['_acl' => ['admin']],
        methods: [Request::METHOD_GET]
    )]
    public function getTotal(Request $request, Context $context): JsonResponse
    {
        $providerArray = $this->providerRegistry->getAllDataProviders();

        $totals = [];
        foreach ($providerArray as $identifier => $provider) {
            $totals[$identifier] = $provider->getProvidedTotal($context);
        }

        return new JsonResponse($totals);
    }

    #[Route(
        path: '/api/_action/data-provider/get-table',
        name: 'api.admin.data-provider.get-table',
        defaults: ['_acl' => ['admin']],
        methods: [Request::METHOD_GET]
    )]
    public function getTable(Request $request, Context $context): JsonResponse
    {
        $identifier = (string) $request->query->get('identifier');

        if ($identifier === '') {
            throw RoutingException::missingRequestParameter('identifier');
        }

        $provider = $this->providerRegistry->getDataProvider($identifier);
        $data = $provider->getProvidedTable($context);

        return new JsonResponse($data);
    }

    #[Route(
        path: '/api/_action/data-provider/generate-document',
        name: 'api.admin.data-provider.generate-document',
        defaults: ['_acl' => ['admin']],
        methods: [Request::METHOD_GET]
    )]
    public function generateDocument(Request $request, Context $context): JsonResponse
    {
        $identifier = (string) $request->query->get('identifier');

        if ($identifier === '') {
            throw RoutingException::missingRequestParameter('identifier');
        }

        $generatedDocument = $this->documentGenerator->readDocument($identifier, $context);

        if ($generatedDocument === null) {
            throw MigrationException::couldNotGenerateDocument();
        }

        return new JsonResponse([
            'file_blob' => \base64_encode($generatedDocument->getContent()),
            'file_name' => $generatedDocument->getName(),
            'file_content_type' => $generatedDocument->getContentType(),
        ]);
    }

    #[Route(
        path: '/api/_action/data-provider/download-private-file/{file}',
        name: 'api.admin.data-provider.download-private-file',
        defaults: ['_acl' => ['admin']],
        methods: [Request::METHOD_GET]
    )]
    public function downloadPrivateFile(Request $request, Context $context): StreamedResponse|RedirectResponse
    {
        $identifier = (string) $request->query->get('identifier');

        if ($identifier === '') {
            throw RoutingException::missingRequestParameter('identifier');
        }

        // requires system_scope because the media entity is flagged as private
        $media = $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($identifier): MediaEntity|null {
            return $this->mediaRepository->search(new Criteria([$identifier]), $context)->getEntities()->first();
        });

        if ($media === null || !$media->isPrivate()) {
            throw RoutingException::invalidRequestParameter('identifier');
        }

        try {
            $url = $this->privateFilesystem->temporaryUrl($media->getPath(), (new \DateTime())->modify('+120 minutes'));

            return new RedirectResponse($url);
        } catch (UnableToGenerateTemporaryUrl) {
        }

        $stream = $context->scope(
            Context::SYSTEM_SCOPE,
            fn (Context $context): StreamInterface => $this->mediaService->loadFileStream($media->getId(), $context)
        );

        if (!$stream instanceof StreamInterface) {
            throw MediaException::fileNotFound($media->getFileName() . '.' . $media->getFileExtension());
        }

        $stream = $stream->detach();

        if (!\is_resource($stream)) {
            throw MediaException::fileNotFound($media->getFileName() . '.' . $media->getFileExtension());
        }

        return new StreamedResponse(function () use ($stream): void {
            \fpassthru($stream);
        }, Response::HTTP_OK, $this->getStreamHeaders($media));
    }

    /**
     * @return array{Content-Disposition: string, Content-Length: int, Content-Type: string}
     */
    private function getStreamHeaders(MediaEntity $media): array
    {
        $filename = $media->getFileName() . '.' . $media->getFileExtension();

        return [
            'Content-Disposition' => HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_ATTACHMENT,
                $filename,
                // only printable ascii
                \preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $filename) ?? ''
            ),
            'Content-Length' => $media->getFileSize() ?? 0,
            'Content-Type' => 'application/octet-stream',
        ];
    }
}
