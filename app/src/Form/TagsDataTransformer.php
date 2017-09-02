<?php
/**
 * Tags data transformer.
 */
namespace Form;

use Repository\TagRepository;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Class TagsDataTransformer
 *
 * @package Form
 */
class TagsDataTransformer implements DataTransformerInterface
{
    /**
     * Tag repository.
     *
     * @var TagRepository|null $tagRepository
     */
    protected $tagRepository = null;

    /**
     * TagsDataTransformer constructor.
     *
     * @param TagRepository $tagRepository Tag repository
     */
    public function __construct(TagRepository $tagRepository)
    {
        $this->tagRepository = $tagRepository;
    }


    /**
     * Transform array of tags Ids to string of names.
     *
     * @param array $tags Tags ids
     *
     * @return string Result
     */
    public function transform($tags)
    {
        if (null == $tags) {
            return '';
        }

        $tagNames = [];

        foreach ($tags as $tag) {
            $tagNames[] = $tag['name'];
        }

        return implode(',', $tagNames);
    }



    /**
     * Transform string of tag names into array of Tags Ids.
     *
     * @param string $string String of tag names
     *
     * @return array Result
     */
    public function reverseTransform($string)
    {
        $tagNames = explode(',', $string);

        $tags = [];
        foreach ($tagNames as $tagName) {
            if (trim($tagName) !== '') {
                $tag = $this->tagRepository->findOneByName($tagName);
                if (null === $tag || !count($tag)) {
                    $tag = [];
                    $tag['name'] = $tagName;
                    $tag = $this->tagRepository->save($tag);
                }
                $tags[] = $tag;
            }
        }

        return $tags;
    }
}