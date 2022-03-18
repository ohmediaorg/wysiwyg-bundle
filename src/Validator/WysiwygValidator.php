<?php

namespace OHMedia\WysiwygBundle\Validator;

use OHMedia\WysiwygBundle\Service\Wysiwyg;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ValidatorException;
use Twig\Environment as TwigEnvironment;
use Twig\Source as TwigSource;

class WysiwygValidator extends ConstraintValidator
{
    private $twig;

    public function __construct(Wysiwyg $wysiwyg)
    {
        $this->wysiwyg = $wysiwyg;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        $error = $this->wysiwyg->validate($value);

        if ($error) {
            $this->context->addViolation($error);
        }
    }
}
