<?php
/**
 * Photo type.
 */
namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\FileType;

/**
 * Class PhotoType.
 * @package Form
 */
class PhotoType extends AbstractType
{
    /**
     * Build Form
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'title',
            TextType::class,
            [
                'label' => 'label.title',
                'required' => true,
                'attr' => [
                    'max_length' => 45,
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        ['groups' => ['photo-default']]
                    ),
                    new Assert\Length(
                        [
                            'groups' => ['photo-default'],
                            'min' => 3,
                            'max' => 45,
                        ]
                    ),
                ],
            ]
        );
        $builder->add(
            'tags',
            ChoiceType::class,
            [
                'label' => 'label.tags',
                'required' => true,
                'placeholder' => 'label.none',
                'choices' => $this->prepareTagsForChoices($options['tag_repository']),
                'expanded' => true,
                'multiple' => true,
            ]
        );
        if (!isset($options['data']) || !isset($options['data']['id'])) {
            $builder->add(
                'source',
                FileType::class,
                [
                    'label' => 'label.source',
                    'required' => true,
                    'attr' => [
                        'readonly' => (isset($options['data']) && isset($options['data']['id'])),
                    ],
                    'constraints' => [
                        new Assert\NotBlank(
                            ['groups' => ['photo-default']]
                        ),
                        new Assert\Image(
                            [
                                'maxSize' => '1024k',
                                'mimeTypes' => [
                                    'image/png',
                                    'image/jpeg',
                                    'image/pjpeg',
                                    'image/jpeg',
                                    'image/pjpeg',
                                ],
                            ]
                        ),
                    ],
                ]
            );
        }
    }

    /**
     * Configure Options
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'validation_groups' => ['photo-default'],
                'tag_repository' => null,
            ]
        );
    }

    /**
     * Get Block Prefix
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'photo_type';
    }

    /**
     * Prepare Tags For Choices
     * @param $tagRepository
     * @return array
     */
    protected function prepareTagsForChoices($tagRepository)
    {
        $tags = $tagRepository->findAll();
        $choices = [];
        foreach ($tags as $tag) {
            $choices[$tag['name']] = $tag['id'];
        }

        return $choices;
    }
}
