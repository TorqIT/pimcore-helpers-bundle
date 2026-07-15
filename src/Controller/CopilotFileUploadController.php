<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Controller;

use Pimcore\Controller\UserAwareController;
use Pimcore\Logger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;
use Torq\PimcoreHelpersBundle\Repository\AssetRepository;
use Torq\PimcoreHelpersBundle\Repository\FolderRepository;

/**
 * Generic, reusable temp-upload endpoint for Copilot automation-action "file"
 * environment fields (see Copilot\AutomationAction\Environment\Type\FileUpload and
 * public/js/copilotEnvironmentFileUpload.js).
 *
 * Accepts a single uploaded file, stages it as a Pimcore asset in a (configurable)
 * folder, and returns the asset's path. The automation-action handler reads that path
 * from the job's environment data and hands it to its importer. Nothing here is specific
 * to any one import — any Copilot job that declares a `file` field can reuse this.
 *
 * The route sits under /admin, so Pimcore's admin firewall handles authentication and
 * CSRF (the client sends the token via the X-pimcore-csrf-token header).
 */
#[Route('/admin/torq-helpers/copilot')]
class CopilotFileUploadController extends UserAwareController
{
    /**
     * Asset folder used when the request does not specify one.
     */
    private const DEFAULT_UPLOAD_FOLDER = '/_copilot-uploads';

    public function __construct(
        private readonly AssetRepository $assetRepository,
        private readonly FolderRepository $folderRepository,
    ) {
    }

    #[Route('/file-upload', name: 'torq_helpers_copilot_file_upload', methods: ['POST'])]
    public function fileUpload(Request $request): JsonResponse
    {
        try {
            $upload = $request->files->get('file');
            if ($upload === null) {
                return new JsonResponse(['success' => false, 'message' => 'No file was uploaded.'], 400);
            }

            $folderPath = $this->sanitizeFolder((string) $request->query->get('folder', self::DEFAULT_UPLOAD_FOLDER));
            $parent = $this->folderRepository->getOrCreateAssetFolder($folderPath);

            $asset = $this->assetRepository->createAssetFromUploadedFile($upload, $parent, overwrite: false);

            return new JsonResponse([
                'success' => true,
                'assetId' => $asset->getId(),
                'assetPath' => $asset->getFullPath(),
                'filename' => $asset->getKey(),
            ]);
        } catch (Throwable $e) {
            Logger::error((string) $e);

            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Constrain staging to a plain absolute asset path — no traversal, no trailing slash.
     */
    private function sanitizeFolder(string $folder): string
    {
        $folder = str_replace(['..', '\\'], '', $folder);
        $folder = '/' . trim($folder, '/');

        return $folder === '/' ? self::DEFAULT_UPLOAD_FOLDER : $folder;
    }
}
