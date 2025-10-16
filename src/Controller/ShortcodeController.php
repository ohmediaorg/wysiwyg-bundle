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
    #[Route('/oh-media-wysiwyg/shortcode-placements', name: 'shortcode_placements')]
    public function placements(
        Wysiwyg $wysiwyg,
        Request $request,
    ): Response {
        $shortcodes = $request->query->all('shortcodes', []);

        $placements = $shortcodes
            ? $wysiwyg->shortcodePlacements(...$shortcodes)
            : [];

        return new JsonResponse([
            'placements' => $placements,
        ]);
    }
}
