<?php

namespace OHMedia\WysiwygBundle\ImagePicker;

use OHMedia\WysiwygBundle\TinyMCE\TreeItem;

class ImagePickerItem extends TreeItem
{
    private string $path = '';
    private int $width = 0;
    private int $height = 0;

    public function setWebPath(string $webPath): static
    {
        $this->webPath = $webPath;
        $this->children = [];

        return $this;
    }

    public function getId(): string
    {
        return $this->webPath;
    }
}
