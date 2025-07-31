<?php

namespace OHMedia\WysiwygBundle\Controller;

use OHMedia\WysiwygBundle\Service\Wysiwyg;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ShortcodeController extends AbstractController
{
    #[Route('/oh-media-wysiwyg/shortcode-links', name: 'shortcode_links')]
    public function links(
        Wysiwyg $wysiwyg,
        Request $request,
    ): Response {
        $shortcode = $request->query->get('shortcode', null);

        $links = $shortcode ? $wysiwyg->shortcodeLinks($shortcode) : [];

        return new JsonResponse([
            'links' => $links,
        ]);
    }
}
