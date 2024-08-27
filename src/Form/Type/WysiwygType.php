<?php

namespace OHMedia\WysiwygBundle\Form\Type;

use OHMedia\WysiwygBundle\Service\Wysiwyg;
use OHMedia\WysiwygBundle\Util\HtmlTags;
use Symfony\Component\Form\AbstractType;
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
    public function __construct(private Wysiwyg $wysiwyg)
    {
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
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($options) {
                $data = $event->getData();

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
