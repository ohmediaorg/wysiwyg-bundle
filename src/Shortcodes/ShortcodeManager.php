<?php

namespace OHMedia\WysiwygBundle\Shortcodes;

class ShortcodeManager
{
    private array $shortcodeProviders = [];

    public function addShortcodeProvider(AbstractShortcodeProvider $shortcodeProvider): self
    {
        $this->shortcodeProviders[] = $shortcodeProvider;

        return $this;
    }

    public function getShortcodes()
    {
        usort($this->shortcodeProviders, function (
            AbstractShortcodeProvider $a,
            AbstractShortcodeProvider $b
        ) {
            return $a->getTitle() <=> $b->getTitle();
        });

        $tabs = [];

        $i = 0;
        foreach ($this->shortcodeProviders as $shortcodeProvider) {
            $shortcodes = $shortcodeProvider->getShortcodes();

            if (!$shortcodes) {
                continue;
            }

            $name = 'tab_'.$i++;

            $selectbox = [
                'type' => 'selectbox',
                'name' => $name.'_shortcode',
                'label' => 'Shortcode',
                'items' => [],
            ];

            foreach ($shortcodes as $shortcode) {
                $selectbox['items'][] = [
                    'value' => trim($shortcode->shortcode, '{} '),
                    'text' => $shortcode->label,
                ];
            }

            $tabs[] = [
                'name' => $name,
                'title' => $shortcodeProvider->getTitle(),
                'items' => [$selectbox],
            ];
        }

        return $tabs;
    }
}
