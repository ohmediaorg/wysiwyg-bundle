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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/oh-media-wysiwyg/tinymce')]
class TinyMCEController extends AbstractController
{
    public function __construct(
        private FileBrowser $fileBrowser,
        private FileFolderRepository $fileFolderRepository,
        private FileManager $fileManager,
    ) {
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
    public function files(
        ImageManager $imageManager,
        ?int $id = null
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        if (!$this->fileBrowser->isEnabled()) {
            return new JsonResponse([]);
        }

        $fileFolder = $id ? $this->fileFolderRepository->find($id) : null;

        $listingItems = $this->fileBrowser->getListing($fileFolder);

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
                    'locked' => $listingItem->isLocked(),
                ];

                if ($listingItem->isImage()) {
                    $item['type'] = 'image';
                    $item['image'] = $imageManager->render($listingItem, [
                        'width' => 40,
                        'height' => 40,
                        'style' => 'height:40px;display:block',
                    ]);
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

    #[Route('/image-list', name: 'tinymce_image_list')]
    public function imageList(
        FileRepository $fileRepository,
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        if (!$this->fileBrowser->isEnabled()) {
            return new JsonResponse([]);
        }

        $topLevelImages = $fileRepository->createQueryBuilder('i')
            ->where('i.browser = 1')
            ->andWhere('i.image = 1')
            ->andWhere('IDENTITY(i.resize_parent) IS NULL')
            ->andWhere('IDENTITY(i.folder) IS NULL')
            ->orderBy('i.name', 'ASC')
            ->getQuery()
            ->getResult();

        $topLevelFolders = $this->fileFolderRepository->createQueryBuilder('ff')
            ->where('ff.browser = 1')
            ->andWhere('IDENTITY(ff.folder) IS NULL')
            ->orderBy('ff.name', 'ASC')
            ->getQuery()
            ->getResult();

        $items = [];

        foreach ($topLevelImages as $image) {
            $items[] = $this->populateImage($image);
        }

        foreach ($topLevelFolders as $folder) {
            $item = $this->populateMenu($folder);

            if (!$item['menu']) {
                continue;
            }

            $items[] = $item;
        }

        $this->sortItems($items);

        return new JsonResponse($items);
    }

    private function populateImage(File $file): array
    {
        return [
            'title' => $file->getFileName(),
            'value' => $this->fileManager->getWebPath($file),
        ];
    }

    private function populateMenu(FileFolder $folder): array
    {
        $item = [
            'title' => $folder->getName(),
            'menu' => [],
        ];

        foreach ($folder->getFiles() as $file) {
            if (!$file->isImage()) {
                continue;
            }

            $item['menu'][] = $this->populateImage($file);
        }

        foreach ($folder->getFolders() as $folder) {
            $subitem = $this->populateMenu($folder);

            if (!$subitem['menu']) {
                continue;
            }

            $item['menu'][] = $subitem;
        }

        $this->sortItems($item['menu']);

        return $item;
    }

    private function sortItems(array &$items): array
    {
        usort($items, function ($a, $b) {
            return strtolower($a['title']) <=> strtolower($b['title']);
        });

        return $items;
    }
}
