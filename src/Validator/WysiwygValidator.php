<?php

namespace OHMedia\WysiwygBundle\Validator;

use OHMedia\WysiwygBundle\Twig\Extension\AbstractWysiwygExtension;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ValidatorException;
use Twig\Environment as TwigEnvironment;
use Twig\Source as TwigSource;

class WysiwygValidator extends ConstraintValidator
{
    private $twig;

    public function __construct(TwigEnvironment $twig)
    {
        $this->twig = $twig;

        $this->functions = [];

        foreach ($twig->getExtensions() as $extension) {
            if (!$extension instanceof AbstractWysiwygExtension) {
                continue;
            }

            $functions = $extension->getFunctions();

            foreach ($functions as $function) {
                $this->functions[] = $function->getName();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        $source = new TwigSource($value, '');
        $tokens = $twig->tokenize($source);

        foreach ($tokens as $token) {

        }

        if (!$json->success) {
            $this->context->addViolation($constraint->message);
        }
    }
}
