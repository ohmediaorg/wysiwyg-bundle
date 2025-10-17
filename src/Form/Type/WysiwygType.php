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
        $data = $event->getData() ?? '';

        preg_match_all('/{{file_href\((.*)\)}}/', $data, $files, \PREG_SET_ORDER);

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

        preg_match_all('/{{image\((.*)\)}}/', $data, $images, \PREG_SET_ORDER);

        foreach ($images as $image) {
            $shortcode = $image[0];

            $args = explode(',', $image[1]);
            $argCount = count($args);

            $id = trim($args[0]);

            $width = isset($args[1]) ? trim($args[1]) : null;
            $height = isset($args[2]) ? trim($args[2]) : null;

            $attributes = [];

            if ($argCount > 3) {
                // the 4th argument could be JSON encoded attributes
                // which may have been incorrectly split by commas
                // so we will concat the remaining args into a JSON string
                $json = [];

                for ($i = 3; $i < $argCount; ++$i) {
                    $json[] = $args[$i];
                }

                $attributes = json_decode(implode(',', $json), true);
            }

            $width = 'null' === $width ? null : (int) $width;
            $height = 'null' === $height ? null : (int) $height;

            $class = $attributes['class'] ?? null;
            $style = $attributes['style'] ?? null;

            $image = $this->fileRepository->find($args[0]);

            if ($image) {
                $src = $this->fileManager->getWebPath($image);

                $attributeStrings = [
                    'src="'.$src.'"',
                ];

                if ($width) {
                    $attributeStrings[] = 'width="'.$width.'"';
                }

                if ($height) {
                    $attributeStrings[] = 'height="'.$height.'"';
                }

                if ($class) {
                    $attributeStrings[] = 'class="'.$class.'"';
                }

                if ($style) {
                    $attributeStrings[] = 'style="'.$style.'"';
                }

                $img = '<img '.implode(' ', $attributeStrings).'>';

                $data = str_replace($shortcode, $img, $data);
            }
        }

        $event->setData($data);
    }

    private function restoreShortcodes(string $data): string
    {
        preg_match_all('/<img[^>]*>/', $data, $images, \PREG_SET_ORDER);

        foreach ($images as $image) {
            preg_match('/src="\/f\/([^\/]*)\/[^"]*"/', $image[0], $src);

            if (!$src) {
                continue;
            }

            $token = $src[1];

            $file = $this->fileRepository->findOneByToken($token);

            if (!$file) {
                continue;
            }

            preg_match('/width="([^"]*)"/', $image[0], $width);
            preg_match('/height="([^"]*)"/', $image[0], $height);

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

            preg_match('/style="([^"]*)"/', $image[0], $style);
            preg_match('/class="([^"]*)"/', $image[0], $class);

            $attributes = [];

            if ($style) {
                $attributes['style'] = $style[1];
            }

            if ($class) {
                $attributes['class'] = $class[1];
            }

            if ($attributes) {
                if (!$width && !$height) {
                    $args[] = 'null';
                    $args[] = 'null';
                }

                $args[] = json_encode($attributes);
            }

            $shortcode = '{{image('.implode(',', $args).')}}';

            $data = str_replace($image[0], $shortcode, $data);
        }

        // find any remaining file URLs
        preg_match_all('/href="(\/f\/([^\/]*)\/[^"]*)"/', $data, $files, \PREG_SET_ORDER);

        foreach ($files as $f) {
            $token = $f[2];

            $file = $this->fileRepository->findOneByToken($token);

            if (!$file) {
                continue;
            }

            $shortcode = '{{file_href('.$file->getId().')}}';

            $data = str_replace($f[1], $shortcode, $data);
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
