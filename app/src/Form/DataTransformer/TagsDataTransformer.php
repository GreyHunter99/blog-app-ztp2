<?php
/**
 * Tags data transformer.
 */

namespace App\Form\DataTransformer;

use App\Entity\Tag;
use App\Service\TagService;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Class TagsDataTransformer.
 */
class TagsDataTransformer implements DataTransformerInterface
{
    /**
     * Tag service.
     *
     * @var TagService
     */
    private $tagService;

    /**
     * TagsDataTransformer constructor.
     *
     * @param TagService $tagService Tag service
     */
    public function __construct(TagService $tagService)
    {
        $this->tagService = $tagService;
    }

    /**
     * Transform array of tags to string of names.
     *
     * @param Collection $tags Tags entity collection
     *
     * @return string Result
     */
    public function transform($tags): string
    {
        if (null === $tags) {
            return '';
        }

        $tagNames = [];

        foreach ($tags as $tag) {
            $tagNames[] = $tag->getName();
        }

        return implode(',', $tagNames);
    }

    /**
     * Transform string of tag names into array of Tag entities.
     *
     * @param string $value String of tag names
     *
     * @return array Result
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function reverseTransform($value): array
    {
        $tagNames = explode(',', $value);

        $tags = [];

        foreach ($tagNames as $tagName) {
            if ('' !== trim($tagName)) {
                $tag = $this->tagService->findOneByName(strtolower($tagName));
                if (null === $tag) {
                    $tag = new Tag();
                    $tag->setName($tagName);
                    $this->tagService->save($tag);
                }
                $tags[] = $tag;
            }
        }

        return $tags;
    }
}
