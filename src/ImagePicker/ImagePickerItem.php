<?php

namespace OHMedia\WysiwygBundle\ImagePicker;

use OHMedia\WysiwygBundle\TinyMCE\TreeItem;

class ImagePickerItem extends TreeItem
{
    private string $path = '';
    private int $width = 0;
    private int $height = 0;

    public function setImage(string $webPath, int $width, int $height): static
    {
        $this->webPath = $webPath;
        $this->width = $width;
        $this->height = $height;
        $this->children = [];

        return $this;
    }

    public function getId(): string
    {
        return json_encode([
            'value' => $this->webPath,
            'meta' => [
                'width' => $this->width,
                'height' => $this->height,
            ],
        ]);
    }
}
