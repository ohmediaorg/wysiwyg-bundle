<?php

namespace OHMedia\WysiwygBundle\Form\Type;

use OHMedia\FileBundle\Repository\FileRepository;
use OHMedia\FileBundle\Service\FileManager;
use OHMedia\WysiwygBundle\Service\Wysiwyg;
use OHMedia\WysiwygBundle\Util\HtmlTags;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WysiwygType extends AbstractType
{
    public function __construct(
        private FileRepository $fileRepository,
        private FileManager $fileManager,
        private Wysiwyg $wysiwyg,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'allowed_tags' => null,
            'allow_shortcodes' => true,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            [$this, 'replaceShortcodes']
        );

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($options) {
                $data = $event->getData();

                $data = $this->restoreShortcodes($data);

                if ($this->wysiwyg->isValid($data)) {
                    $filtered = $this->wysiwyg->filter(
                        $data,
                        $options['allowed_tags'],
                        $options['allow_shortcodes']
                    );

                    $event->setData($filtered);
                } else {
                    $error = new FormError('Malformed shortcode syntax');

                    $event->getForm()->addError($error);
                }
            }
        );
    }

    public function replaceShortcodes(PreSetDataEvent $event): void
    {
        $data = $event->getData();

        preg_match_all('/{{file_href\(([^(]*)\)}}/', $data, $files, \PREG_SET_ORDER);

        foreach ($files as $file) {
            $shortcode = $file[0];
            $id = $file[1];

            $file = $this->fileRepository->find($id);

            if ($file) {
                $data = str_replace(
                    $shortcode,
                    $this->fileManager->getWebPath($file),
                    $data
                );
            }
        }

        preg_match_all('/{{image\(([^(]*)\)}}/', $data, $images, \PREG_SET_ORDER);

        foreach ($images as $image) {
            $shortcode = $image[0];
            $args = explode(',', $image[1]);
            $id = trim($args[0]);
            $width = isset($args[1]) ? trim($args[1]) : null;
            $height = isset($args[2]) ? trim($args[2]) : null;

            $width = 'null' === $width ? null : (int) $width;
            $height = 'null' === $height ? null : (int) $height;

            $image = $this->fileRepository->find($args[0]);

            if ($image) {
                $src = $this->fileManager->getWebPath($image);

                $attributes = [
                    'src="'.$src.'"',
                ];

                if ($width) {
                    $attributes[] = 'width="'.$width.'"';
                }

                if ($height) {
                    $attributes[] = 'height="'.$height.'"';
                }

                $img = '<img '.implode(' ', $attributes).'>';

                $data = str_replace($shortcode, $img, $data);
            }
        }

        $event->setData($data);
    }

    private function restoreShortcodes(string $data): string
    {
        preg_match_all('/<img[^>]*>/', $data, $images);

        foreach ($images as $image) {
            preg_match('/src="\/f\/([^\/]*)\/[^"]*"/', $image[0], $src);
            preg_match('/width="([^"]*)"/', $image[0], $width);
            preg_match('/height="([^"]*)"/', $image[0], $height);

            if (!$src) {
                continue;
            }

            $token = $src[1];

            $file = $this->fileRepository->findOneByToken($token);

            if (!$file) {
                continue;
            }

            $args = [$file->getId()];

            if ($width && $height) {
                $args[] = $width[1];
                $args[] = $height[1];
            } elseif ($width) {
                $args[] = $width[1];
            } elseif ($height) {
                $args[] = 'null';
                $args[] = $height[1];
            }

            $shortcode = '{{image('.implode(',', $args).')}}';

            $data = str_replace($image[0], $shortcode, $data);
        }

        return $data;
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if (!isset($view->vars['attr'])) {
            $view->vars['attr'] = [];
        }

        if (!isset($view->vars['attr']['class'])) {
            $view->vars['attr']['class'] = 'tinymce';
        }

        if (null !== $options['allowed_tags']) {
            $view->vars['attr']['data-tinymce-valid-elements'] = HtmlTags::htmlTagsToTinymceElements(...$options['allowed_tags']);
        }

        $view->vars['attr']['data-tinymce-allow-shortcodes'] = $options['allow_shortcodes'] ? 'true' : 'false';
    }

    public function getParent(): ?string
    {
        return TextareaType::class;
    }
}
