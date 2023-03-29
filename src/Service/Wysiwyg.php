<?php

namespace OHMedia\WysiwygBundle\Service;

use Exception;
use OHMedia\WysiwygBundle\Twig\Extension\AbstractWysiwygExtension;
use Twig\Environment;
use Twig\Source;
use Twig\Token;
use Twig\TokenStream;

class Wysiwyg
{
    private $algo;
    private $allowedTags;
    private $functions;
    private $twig;

    public function __construct(Environment $twig, array $allowedTags)
    {
        $this->algo = \PHP_VERSION_ID < 80100 ? 'sha256' : 'xxh128';

        $this->allowedTags = $allowedTags;

        $this->twig = $twig;

        $this->functions = [];
    }

    public function addExtension(AbstractWysiwygExtension $extension): self
    {
        foreach ($extension->getFunctions() as $function) {
            $name = $function->getName();

            $this->functions[] = $name;
        }

        return $this;
    }

    public function isValid(string $wysiwyg): bool
    {
        try {
            $this->twig->createTemplate($wysiwyg);

            return true;
        }
        catch(Exception $e) {
            return false;
        }
    }

    public function render(string $wysiwyg, ?array $allowedTags = null): string
    {
        if (!$this->isValid($wysiwyg)) {
            // Invalid Twig Syntax
            // just return the string without the allowed HTML tags
            return $this->filterHtml($wysiwyg, $allowedTags);
        }

        $wysiwyg = $this->filter($wysiwyg, $allowedTags);

        $template = $this->twig->createTemplate($wysiwyg);

        return $this->twig->render($template);
    }

    public function filter(string $wysiwyg, ?array $allowedTags = null): string
    {
        $wysiwyg = $this->filterTwig($wysiwyg);

        $wysiwyg = $this->filterHtml($wysiwyg, $allowedTags);

        return $wysiwyg;
    }

    public function filterTwig(string $wysiwyg): string
    {
        $wysiwyg = $this->preserveTwigSyntax($wysiwyg);

        $wysiwyg = $this->stripTwigSyntax($wysiwyg);

        $wysiwyg = $this->restoreTwigSyntax($wysiwyg);

        return $wysiwyg;
    }

    public function filterHtml(string $wysiwyg, ?array $allowedTags = null): string
    {
        if (null === $allowedTags) {
            $allowedTags = $this->allowedTags;
        }

        return strip_tags($wysiwyg, $allowedTags);
    }

    private function preserveTwigSyntax(string $wysiwyg): string
    {
        $this->hashMap = [];

        foreach ($this->functions as $name) {
            // we allow {{ allowed_function_name }}
            // or {{ allowed_function_name() }}
            // or {{ allowed_function_name(1234) }}
            $regex = preg_quote('{{') .
                '\s*' .
                preg_quote($name) .
                '\s*' .
                '(\(([^\)]*)\))?' . // optional brackets with optional whitespace between
                '\s*' .
                preg_quote('}}');

            preg_match('/' . $regex . '/', $wysiwyg, $matches);

            if ($matches) {
                $find = $matches[0];
                $arg = intval(trim($matches[2]));

                $hash = hash($this->algo, $find);

                $wysiwyg = str_replace($find, $hash, $wysiwyg);

                $this->hashMap[$hash] = [
                    'name' => $name,
                    'arg' => $arg ?: '',
                ];
            }
        }

        return $wysiwyg;
    }

    private function restoreTwigSyntax(string $wysiwyg): string
    {
        foreach ($this->hashMap as $hash => $func) {
            // restore the allowed twig syntax
            $replace = sprintf('{{ %s(%s) }}', $func['name'], $func['arg']);

            $wysiwyg = str_replace($hash, $replace, $wysiwyg);
        }

        return $wysiwyg;
    }

    private function stripTwigSyntax(string $wysiwyg): string
    {
        $source = new Source($wysiwyg, '');
        $tokenStream = $this->twig->tokenize($source);

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
                $wysiwyg = preg_replace('/' . $regex . '/', '', $wysiwyg);
            }
        }

        return $wysiwyg;
    }

    private function buildBlockRegex(TokenStream $tokenStream): string
    {
        return $this->buildRegex(
            $tokenStream,
            Token::BLOCK_END_TYPE,
            '{%',
            '%}'
        );
    }

    private function buildVariableRegex(TokenStream $tokenStream): string
    {
        return $this->buildRegex(
            $tokenStream,
            Token::VAR_END_TYPE,
            '{{',
            '}}'
        );
    }

    private function buildRegex(
        TokenStream $tokenStream,
        int $end,
        string $open,
        string $close
    ): string
    {
        $tokens = $this->getTokens(
            $tokenStream,
            $end
        );

        $regex = [preg_quote($open) . '(-|~)?'];

        foreach ($tokens as $token) {
            if ($token->test(Token::STRING_TYPE)) {
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

        $regex[] = '(-|~)?' . preg_quote($close);

        return implode('\s*', $regex);
    }

    private function getTokens(TokenStream $tokenStream, int $end): array
    {
        $tokens = [];

        do {
            $tokens[] = $tokenStream->next();
        } while(!$tokenStream->test($end));

        return $tokens;
    }
}
