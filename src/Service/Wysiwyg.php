<?php

namespace OHMedia\WysiwygBundle\Service;

use OHMedia\WysiwygBundle\Repository\WysiwygRepositoryInterface;
use OHMedia\WysiwygBundle\Shortcodes\Shortcode;
use OHMedia\WysiwygBundle\Twig\AbstractWysiwygExtension;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\Source;
use Twig\Token;
use Twig\TokenStream;

class Wysiwyg
{
    private array $functions;
    private array $repositories;

    public function __construct(
        private Environment $twig,
        private UrlGeneratorInterface $urlGenerator,
        private array $allowedTags,
    ) {
        $this->functions = [];
        $this->repositories = [];
    }

    public function addExtension(AbstractWysiwygExtension $extension): self
    {
        foreach ($extension->getFunctions() as $function) {
            $name = $function->getName();

            $this->functions[] = $name;
        }

        return $this;
    }

    public function addRepository(WysiwygRepositoryInterface $repository): self
    {
        $this->repositories[] = $repository;

        return $this;
    }

    public function shortcodesInUse(string ...$shortcodes): bool
    {
        foreach ($shortcodes as $i => $shortcode) {
            $shortcodes[$i] = Shortcode::format($shortcode);
        }

        foreach ($this->repositories as $repository) {
            foreach ($shortcodes as $shortcode) {
                if ($this->repositoryContainsShortcode($repository, $shortcode)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function repositoryContainsShortcode(
        WysiwygRepositoryInterface $repository,
        string $shortcode,
    ): bool {
        $qb = $repository->getShortcodeQueryBuilder($shortcode);

        $aliases = $qb->getRootAliases();

        if (!isset($aliases[0])) {
            throw new \RuntimeException('No alias was set before invoking getShortcodeQueryBuilder().');
        }

        $select = sprintf('COUNT(%s.id)', $aliases[0]);

        return (clone $qb)
            ->select($select)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    public function shortcodePlacements(string $shortcode): array
    {
        $shortcode = Shortcode::format($shortcode);

        $placements = [];

        foreach ($this->repositories as $repository) {
            $entities = $repository->getShortcodeQueryBuilder($shortcode)
                ->getQuery()
                ->getResult();

            // determine if we need to search for {{image(ID, ...)}}
            preg_match('/{{image\((\d+)\)}}/', $shortcode, $matches);

            if ($matches) {
                $otherShortcode = '{{image('.$matches[1].',%)}}';
                $otherEntities = $repository->getShortcodeQueryBuilder($otherShortcode)
                    ->getQuery()
                    ->getResult();

                $entities = array_merge($entities, $otherEntities);
            }

            $links = [];

            foreach ($entities as $entity) {
                $route = $repository->getShortcodeRoute();
                $params = $repository->getShortcodeRouteParams($entity);

                $href = $this->urlGenerator->generate($route, $params);

                $text = $repository->getShortcodeLinkText($entity);

                $links[$entity->getId()] = [
                    'href' => $href,
                    'text' => $text,
                ];
            }

            if ($links) {
                $heading = $repository->getShortcodeHeading();

                if (!isset($placements[$heading])) {
                    $placements[$heading] = [
                        'heading' => $repository->getShortcodeHeading(),
                        'links' => [],
                    ];
                }

                $placements[$heading]['links'] += array_values($links);
            }
        }

        usort($placements, function ($a, $b) {
            return $a['heading'] <=> $b['heading'];
        });

        return array_values($placements);
    }

    public function isValid(string $wysiwyg): bool
    {
        try {
            $this->twig->createTemplate($wysiwyg);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function render(string $wysiwyg, ?array $allowedTags = null, bool $allowShortcodes = true): string
    {
        if (!$this->isValid($wysiwyg)) {
            // Invalid Twig Syntax
            // just return the string without the allowed HTML tags
            return $this->filterHtml($wysiwyg, $allowedTags);
        }

        $wysiwyg = $this->filter($wysiwyg, $allowedTags, $allowShortcodes);

        $template = $this->twig->createTemplate($wysiwyg);

        return $this->twig->render($template);
    }

    public function filter(string $wysiwyg, ?array $allowedTags = null, bool $allowShortcodes = true): string
    {
        $wysiwyg = $this->filterTwig($wysiwyg, $allowShortcodes);

        $wysiwyg = $this->filterHtml($wysiwyg, $allowedTags);

        return $wysiwyg;
    }

    public function filterTwig(string $wysiwyg, bool $allowShortcodes = true): string
    {
        $source = new Source($wysiwyg, '');
        $tokenStream = $this->twig->tokenize($source);

        while (!$tokenStream->isEOF()) {
            $token = $tokenStream->next();

            if ($token->test(Token::BLOCK_START_TYPE)) {
                $regex = $this->buildBlockRegex($tokenStream);

                $wysiwyg = preg_replace('/'.$regex.'/', '', $wysiwyg);
            } elseif ($token->test(Token::VAR_START_TYPE)) {
                $regex = $this->buildVariableRegex($tokenStream);

                preg_match('/'.$regex.'/', $wysiwyg, $matches);

                if (!$matches) {
                    continue;
                }

                $replace = $allowShortcodes
                    ? $this->getVariableRegexReplacement($matches)
                    : '';

                $wysiwyg = preg_replace('/'.$regex.'/', $replace, $wysiwyg);
            }
        }

        return $wysiwyg;
    }

    public function filterHtml(string $wysiwyg, ?array $allowedTags = null): string
    {
        if (null === $allowedTags) {
            $allowedTags = $this->allowedTags;
        }

        return strip_tags($wysiwyg, $allowedTags);
    }

    private function getVariableRegexReplacement(array $matches): string
    {
        if (!in_array($matches[2], $this->functions)) {
            return '';
        }

        $replace = '{{'.$matches[2].'(';

        $sq = "'";
        $dq = '"';

        $last = count($matches) - 1;

        if (isset($matches[3]) && '(' === $matches[3]) {
            $args = array_slice($matches, 4, $last - 4);

            if ($args) {
                $args = implode('', $args);

                $args = explode(',', $args);
            }

            foreach ($args as $i => $arg) {
                $arg = trim($arg);

                if (str_starts_with($arg, $sq) && str_ends_with($arg, $sq)) {
                    // string surrounded by single-quotes
                    // give back a string escaped and surrounded by double-quotes
                    $arg = $dq.addslashes(trim($arg, $sq)).$dq;
                } elseif (str_starts_with($arg, $dq) && str_ends_with($arg, $dq)) {
                    // string surrounded by double-quotes
                    // give back a string escaped and surrounded by double-quotes
                    $arg = $dq.addslashes(trim($arg, $dq)).$dq;
                } elseif ('null' === $arg) {
                    // leave it
                } else {
                    // not a string - force int
                    $arg = abs(intval($arg));
                }

                $args[$i] = $arg;
            }

            $args = implode(', ', $args);

            $replace .= $args;
        }

        $replace .= ')}}';

        return $replace;
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
    ): string {
        $tokens = $this->getTokens(
            $tokenStream,
            $end
        );

        $regex = [preg_quote($open).'(-|~)?'];

        foreach ($tokens as $token) {
            if ($token->test(Token::STRING_TYPE)) {
                $r = preg_quote($token->getValue());

                // look for the string value surrounded
                // by either single or double quotes
                $regex[] = '('."'".$r."'".'|"'.$r.'")';
            } elseif ($token->test(Token::INTERPOLATION_START_TYPE)) {
                $regex[] = preg_quote('#{');
            } elseif ($token->test(Token::INTERPOLATION_END_TYPE)) {
                $regex[] = preg_quote('}');
            } else {
                $regex[] = '('.preg_quote($token->getValue()).')';
            }
        }

        $regex[] = '(-|~)?'.preg_quote($close);

        return implode('\s*', $regex);
    }

    private function getTokens(TokenStream $tokenStream, int $end): array
    {
        $tokens = [];

        do {
            $tokens[] = $tokenStream->next();
        } while (!$tokenStream->test($end));

        return $tokens;
    }
}
