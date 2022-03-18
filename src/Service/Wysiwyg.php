<?php

namespace OHMedia\WysiwygBundle\Service;

use Doctrine\ORM\EntityManager;
use OHMedia\WysiwygBundle\Entity\Wysiwyg;
use OHMedia\WysiwygBundle\Interfaces\TransformerInterface;

class Wysiwyg
{
    private $em;
    private $transformers;
    private $wysiwyg;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->transformers = [];
        $this->wysiwyg = [];
    }

    public function set(string $id, $value): self
    {
        if ('' === $id) {
            return $this;
        }

        $wysiwyg = $this->em->getRepository(Wysiwyg::class)->find($id);

        if (!$wysiwyg) {
            $wysiwyg = new Wysiwyg();
            $wysiwyg->setId($id);

            $this->em->persist($wysiwyg);
        }

        $string = array_key_exists($id, $this->transformers)
            ? $this->transformers[$id]->transform($value)
            : $value;

        $wysiwyg->setValue($string);

        $this->em->flush();

        $this->wysiwyg[$id] = $value;

        return $this;
    }

    public function get(string $id): mixed
    {
        if ('' === $id) {
            return null;
        }

        if (!array_key_exists($id, $this->wysiwyg)) {
            $wysiwyg = $this->em->getRepository(Wysiwyg::class)->find($id);

            $string = $wysiwyg ? $wysiwyg->getValue() : null;

            $value = array_key_exists($id, $this->transformers)
                ? $this->transformers[$id]->reverseTransform($string)
                : $string;

            $this->wysiwyg[$id] = $value;
        }

        return $this->wysiwyg[$id];
    }

    public function addTransformer(TransformerInterface $transformer): self
    {
        $this->transformers[$transformer->getId()] = $transformer;

        return $this;
    }
}
