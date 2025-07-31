<?php

namespace OHMedia\WysiwygBundle\Controller;

use OHMedia\WysiwygBundle\Services\Wysiwyg;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ShortcodeController extends AbstractController
{
    #[Route('/oh-media-wysiwyg/shortcode-links', name: 'shortcode_links')]
    public function links(
        Wysiwyg $wysiwyg,
        string $shortcode
    ): Response {
        $links = $wysiwyg->shortcodeLinks($shortcode);

        return new JsonResponse([
            'links' => $links,
        ]);
    }
}
