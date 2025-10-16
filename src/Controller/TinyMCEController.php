<?php

namespace OHMedia\WysiwygBundle\Controller;

use OHMedia\FileBundle\Entity\File;
use OHMedia\FileBundle\Entity\FileFolder;
use OHMedia\FileBundle\Repository\FileFolderRepository;
use OHMedia\FileBundle\Repository\FileRepository;
use OHMedia\FileBundle\Service\FileBrowser;
use OHMedia\FileBundle\Service\FileManager;
use OHMedia\FileBundle\Service\ImageManager;
use OHMedia\WysiwygBundle\ContentLinks\ContentLinkManager;
use OHMedia\WysiwygBundle\Shortcodes\ShortcodeManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/oh-media-wysiwyg/tinymce')]
class TinyMCEController extends AbstractController
{
    public function __construct(
        private FileBrowser $fileBrowser,
        private FileFolderRepository $fileFolderRepository,
        private FileManager $fileManager,
        private ImageManager $imageManager,
    ) {
    }

    #[Route('/image-upload', name: 'tinymce_image_upload')]
    public function imageUpload(
        FileRepository $fileRepository,
        Request $request,
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        if (!$request->files->has('file')) {
            throw $this->createAccessDeniedException('No file found.');
        }

        $file = new File();
        $file->setFile($request->files->get('file'));
        $file->setBrowser(true);
        $file->setImage(true);

        $fileRepository->save($file, true);

        return new JsonResponse([
            'location' => $this->fileManager->getWebPath($file),
        ]);
    }

    #[Route('/shortcodes', name: 'tinymce_shortcodes')]
    public function shortcodes(ShortcodeManager $shortcodeManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        return new JsonResponse($shortcodeManager->getShortcodes());
    }

    #[Route('/contentlinks', name: 'tinymce_contentlinks')]
    public function contentLinks(ContentLinkManager $contentLinkManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        return new JsonResponse($contentLinkManager->getContentLinks());
    }

    /**
     * If you alter this URL, you'll need to invalidate
     * the localstorage in filebrowser.js.
     */
    #[Route('/filebrowser/{id}', name: 'tinymce_filebrowser')]
    public function files(?int $id = null): Response
    {
        return $this->getFileBrowserResponse($id, true, true);
    }

    /**
     * If you alter this URL, you'll need to invalidate
     * the localstorage in imagebrowser.js.
     */
    #[Route('/imagebrowser/{id}', name: 'tinymce_imagebrowser')]
    public function images(?int $id = null): Response
    {
        return $this->getFileBrowserResponse($id, true, false);
    }

    private function getFileBrowserResponse(
        ?int $id = null,
        bool $includeImages = true,
        bool $includeFiles = true,
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        if (!$this->fileBrowser->isEnabled()) {
            return new JsonResponse([]);
        }

        $fileFolder = $id ? $this->fileFolderRepository->find($id) : null;

        $listingItems = $this->fileBrowser->getListing(
            $fileFolder,
            $includeImages,
            $includeFiles,
        );

        $items = [];

        foreach ($listingItems as $listingItem) {
            $id = $listingItem->getId();

            if ($listingItem instanceof FileFolder) {
                $items[] = [
                    'type' => 'folder',
                    'name' => (string) $listingItem,
                    'url' => $this->generateUrl('tinymce_filebrowser', [
                        'id' => $id,
                    ]),
                    'locked' => $listingItem->isLocked(),
                ];
            } elseif ($listingItem instanceof File) {
                $item = [
                    'name' => (string) $listingItem,
                    'id' => (string) $id,
                    'path' => $this->fileManager->getWebPath($listingItem),
                    'locked' => $listingItem->isLocked(),
                ];

                if ($listingItem->isImage()) {
                    $item['type'] = 'image';
                    $item['image'] = $this->imageManager->render($listingItem, [
                        'width' => 40,
                        'height' => 40,
                        'style' => 'height:40px;display:block',
                    ]);

                    list(
                        $item['width'],
                        $item['height'],
                    ) = $this->imageManager->constrainWidthAndHeight(
                        $listingItem,
                        $listingItem->getWidth(),
                        $listingItem->getHeight(),
                    );
                } else {
                    $item['type'] = 'file';
                }

                $items[] = $item;
            }
        }

        $backPath = null;

        if ($fileFolder) {
            $parent = $fileFolder->getFolder();

            $backPath = $this->generateUrl('tinymce_filebrowser', [
                'id' => $parent ? $parent->getId() : null,
            ]);
        }

        return new JsonResponse([
            'back_path' => $backPath,
            'items' => $items,
        ]);
    }
}
