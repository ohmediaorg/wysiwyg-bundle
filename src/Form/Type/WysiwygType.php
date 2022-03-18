<?php

namespace OHMedia\WysiwygBundle\Form\Type;

use OHMedia\WysiwygBundle\Validator\Constraints\Wysiwyg;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WysiwygType extends AbstractType
{
    private $allowedTags;

    public function __construct(array $allowedTags)
    {
        $this->allowedTags = $allowedTags;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'allowed_tags' => $this->allowedTags
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $allowed = [];

        $this->addModelTransformer(new CallbackTransformer(
            function ($value) {
                // don't need to do anything here
                return $value;
            },
            function ($value) use ($options) {
                return $this->getFilteredValue($value, $options);
            }
        ));
    }

    private function getFilteredValue($value, array $options)
    {
        return strip_tags($value, $options['allowed_tags']);
    }

    public function getParent()
    {
        return TextareaType::class;
    }
}
