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
    private $sitekey;
    private $theme;
    private $size;

    public function __construct(TwigEnvironment $twig)
    {
        $this->twig = $twig;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'allowed_tags' => [
                'a', 'abbr', 'address', 'article', 'aside',
                'b', 'blockquote', 'br', 'button',
                'caption', 'cite', 'code', 'col', 'colgroup',
                'dd', 'dfn', 'div', 'dl', 'dt',
                'em', 'embed',
                'font', 'figcaption', 'figure',
                'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr',
                'i', 'iframe', 'img',
                'kbd',
                'li',
                'ol',
                'p', 'picture', 'pre',
                'q',
                'section', 'small', 'span', 'strong', 'sub', 'sup', 'svg',
                'table', 'tbody', 'td', 'tfoot', 'th', 'thead', 'time', 'tr',
                'u', 'ul',
            ]
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
        // filter out tags
        $value = strip_tags($value, $options['allowed_tags']);

        // filter out twig that's not allowed
        $source = new TwigSource($value, '');
        $tokens = $twig->tokenize($source);

        foreach ($tokens as $token) {

        }

        return explode(', ', $tagsAsString);
    }

    public function getParent()
    {
        return TextareaType::class;
    }
}
