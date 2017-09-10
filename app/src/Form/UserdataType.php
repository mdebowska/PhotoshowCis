<?php
/**
 * Userdata type.
 */
namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class UserdataType.
 *
 * @package Form
 */
class UserdataType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'name',
            TextType::class,
            [
                'label' => 'label.name',
                'required' => false,
                'attr' => [
                    'max_length' => 45,
                ],
                'constraints' => [
                    new Assert\Length(
                        [
                            'max' => 45,
                            'groups' => ['profile-default'],
                        ]
                    ),
                ],
            ]
        );
        $builder->add(
            'surname',
            TextType::class,
            [
                'label' => 'label.surname',
                'required' => false,
                'attr' => [
                    'max_length' => 45,
                ],
                'constraints' => [
                    new Assert\Length(
                        [
                            'groups' => ['profile-default'],
                            'max' => 45,
                        ]
                    ),
                ],
            ]
        );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'validation_groups' => ['userdata-default'],
                'userdata_repository' => null,
            ]
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'profile_type';
    }
}
