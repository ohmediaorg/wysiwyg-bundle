<?php

namespace OHMedia\WysiwygBundle;

use OHMedia\WysiwygBundle\Twig\Extension\AbstractWysiwygExtension;
use Twig\Environment;
use Twig\Source;
use Twig\Token;
use Twig\TokenStream;

class Wysiwyg
{
    private $extensions;
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;

        $this->functions = [];
    }

    public function addExtension(AbstractWysiwygExtension $extension)
    {
        foreach ($extension->getFunctions() as $function) {
            $this->functions[] = $function->getName();
        }

        return $this;
    }

    public function render(string $wysiwyg)
    {
        $wysiwyg = $this->filter($wysiwyg);

        $template = $this->twig->createTemplate($wysiwyg);

        return $this->twig->render($template);
    }

    public function filter(string $wysiwyg): string
    {
        $source = new Source($wysiwyg, '');
        $tokenStream = $this->twig->tokenize($source);

        $regexToReplace = [];

        while (!$tokenStream->isEOF()) {
            $token = $tokenStream->next();

            if ($token->test(Token::BLOCK_START_TYPE)) {
                $regex = $this->buildBlockRegex($tokenStream);
            }
            else if ($token->test(Token::VAR_START_TYPE)) {
                $regex = $this->buildVariableRegex($tokenStream);
            }
            else {
                $regex = null;
            }

            if ($regex) {
                $regexToReplace[] = $regex;
            }
        }

        foreach ($regexToReplace as $r) {
            $wysiwyg = preg_replace('/' . $r . '/', '', $wysiwyg);
        }

        return $wysiwyg;
    }

    private function buildBlockRegex(TokenStream $tokenStream)
    {
        $tokens = $this->getTokens(
            $tokenStream,
            Token::BLOCK_START_TYPE,
            Token::BLOCK_END_TYPE
        );

        $regex = $this->buildRegex($tokens);

        array_unshift($regex, preg_quote('{{') . '(-|~)?');

        $regex[] = '(-|~)?' . preg_quote('}}');

        return $regex;
    }

    private function buildVariableRegex(TokenStream $tokenStream)
    {
        $tokens = $this->getTokens(
            $tokenStream,
            Token::VAR_START_TYPE,
            Token::VAR_END_TYPE
        );

        // We need to find the first variable name and check if it's a function.
        // If it's a function we allow, the only things that can be found
        // between the brackets are integers, strings, and true|false.
        // Otherwise, the regex will be built to remove this syntax.

        $nameFound = false;
        foreach ($tokens as $token) {

        }

        $regex = $this->buildRegex($tokens);

        array_unshift($regex, preg_quote('{{') . '(-|~)?');

        $regex[] = '(-|~)?' . preg_quote('}}');

        return $regex;
    }

    private function getTokens(TokenStream $tokenStream, int $start, int $end): array
    {
        $tokens = [];

        do {
            $tokens[] = $tokenStream->next();
        } while(!$tokenStream->test(Token::BLOCK_END_TYPE));

        return $tokens;
    }

    private function buildRegex(...Token $tokens): array
    {
        $regex = [];

        foreach ($tokens as $token) {
            if ($current->test(Token::STRING_TYPE)) {
                $r = preg_quote($token->getValue());

                // look for the string value surrounded
                // by either single or double quotes
                $regex[] = '(' . "'" . $r . "'" . '|' . '"' . $r . '"' . ')';
            }
            else if ($token->test(Token::INTERPOLATION_START_TYPE)) {
                $regex[] = preg_quote('#{');
            }
            else if ($token->test(Token::INTERPOLATION_END_TYPE)) {
                $regex[] = preg_quote('}');
            }
            else {
                $regex[] = preg_quote($token->getValue());
            }
        }

        return $regex;
    }
}
