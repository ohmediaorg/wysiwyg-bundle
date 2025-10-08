<?php

namespace OHMedia\WysiwygBundle\TinyMCE;

class TreeItemBuilder
{
    private int $id = 0;

    public function getTreeItems(TreeItem ...$treeItems)
    {
        $items = [];

        foreach ($treeItems as $treeItem) {
            $title = $treeItem->getTitle();

            if ($treeItem->hasChildren()) {
                $children = $this->getTreeItems(...$treeItem->getChildren());

                if ($children) {
                    $items[] = [
                        'type' => 'directory',
                        'id' => 'directory_'.$this->id++,
                        'title' => $title,
                        'children' => $children,
                    ];
                }
            } else {
                $items[] = [
                    'type' => 'leaf',
                    'title' => $title,
                    'id' => $treeItem->getId(),
                ];
            }
        }

        return $items;
    }
}
