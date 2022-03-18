<?php

namespace OHMedia\WysiwygBundle\Validator\Constraints;

use OHMedia\WysiwygBundle\Validator\WysiwygValidator;
use Symfony\Component\Validator\Constraint;

#[\Attribute]
class Wysiwyg extends Constraint
{
    public $message = 'Wysiwyg verification was unsuccessful.';

    public function validatedBy()
    {
        return WysiwygValidator::class;
    }
}
