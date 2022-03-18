<?php

namespace OHMedia\WysiwygBundle\Validator\Constraints;

use OHMedia\WysiwygBundle\Validator\WysiwygValidator;
use Symfony\Component\Validator\Constraint;

class Wysiwyg extends Constraint
{
    public function validatedBy()
    {
        return WysiwygValidator::class;
    }
}
