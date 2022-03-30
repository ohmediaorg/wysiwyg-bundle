<?php

namespace OHMedia\WysiwygBundle\Form\Type;

use OHMedia\WysiwygBundle\Service\Wysiwyg;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WysiwygType extends AbstractType
{
    private $wysiwyg;

    public function __construct(Wysiwyg $wysiwyg)
    {
        $this->wysiwyg = $wysiwyg;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'allowed_tags' => null
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $allowed = [];

        $builder->addModelTransformer(new CallbackTransformer(
            function ($value) {
                // don't need to do anything here
                return $value;
            },
            function ($value) use ($options) {
                return $this->wysiwyg->filter($value, $options['allowed_tags']);
            }
        ));
    }

    public function getParent(): ?string
    {
        return TextareaType::class;
    }
}
