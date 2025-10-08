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
use OHMedia\WysiwygBundle\ImagePicker\ImagePickerItem;
use OHMedia\WysiwygBundle\Shortcodes\ShortcodeManager;
use OHMedia\WysiwygBundle\TinyMCE\TreeItemBuilder;
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
                    'path' => $this->fileManager->getWebPath($listingItem),
                    'locked' => $listingItem->isLocked(),
                ];

                if ($listingItem->isImage()) {
                    $item['type'] = 'image';
                    $item['image'] = $imageManager->render($listingItem, [
                        'width' => 40,
                        'height' => 40,
                        'style' => 'height:40px;display:block',
                    ]);

                    list($item['width'], $item['height'])
                        = $imageManager->constrainWidthAndHeight($listingItem->getWidth(), $listingItem->getHeight());
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

    #[Route('/imagepicker', name: 'tinymce_imagepicker')]
    public function imagepicker(
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

        $treeItems = [];

        foreach ($topLevelImages as $image) {
            $treeItems[] = $this->populateImage($image);
        }

        foreach ($topLevelFolders as $folder) {
            $item = $this->populateMenu($folder);

            if (!$item->getChildren()) {
                continue;
            }

            $treeItems[] = $item;
        }

        $this->sortItems($treeItems);

        $treeItemBuilder = new TreeItemBuilder();

        return new JsonResponse($treeItemBuilder->getTreeItems(...$treeItems));
    }

    private function populateImage(File $file): ImagePickerItem
    {
        $item = new ImagePickerItem($file->getFileName());
        $item->setWebPath($this->fileManager->getWebPath($file));

        return $item;
    }

    private function populateMenu(FileFolder $folder): ImagePickerItem
    {
        $item = new ImagePickerItem($folder->getName());

        $children = [];

        foreach ($folder->getFiles() as $file) {
            if (!$file->isImage()) {
                continue;
            }

            $children[] = $this->populateImage($file);
        }

        foreach ($folder->getFolders() as $folder) {
            $subitem = $this->populateMenu($folder);

            if (!$subitem->getChildren()) {
                continue;
            }

            $children[] = $subitem;
        }

        $this->sortItems($children);

        $item->setChildren(...$children);

        return $item;
    }

    private function sortItems(array &$items): array
    {
        usort($items, function ($a, $b) {
            return strtolower($a->getText()) <=> strtolower($b->getText());
        });

        return $items;
    }
}
