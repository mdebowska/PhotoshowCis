<?php
/**
 * Tag type.
 */
namespace Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Validator\Constraints as CustomAssert;

/**
 * Class TagType.
 *
 * @package Form
 */
class TagType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'name',
            TextType::class,
            [
                'label' => 'label.name',
                'required'   => true,
                'attr' => [
                    'max_length' => 45,
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        ['groups' => ['tag-default']]
                    ),
                    new Assert\Length(
                        [
                            'groups' => ['tag-default'],
                            'min' => 3,
                            'max' => 45,
                        ]
                    ),
                    new CustomAssert\UniqueTag(
                        [
                            'groups' => ['tag-default'],
                            'repository' => isset($options['tag_repository']) ? $options['tag_repository'] : null,
                            'elementId' => isset($options['data']['id']) ? $options['data']['id'] : null,
                        ]
                    ),
                ],
            ]
        );
//            $builder->get('tags')->addModelTransformer(
//                new TagsDataTransformer($options['tag_repository'])
//            );
//        }
    }
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'validation_groups' => ['tag-default'],
                'tag_repository' => null,
            ]
        );
    }
    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'tag_type';
    }
}