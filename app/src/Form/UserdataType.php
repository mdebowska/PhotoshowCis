<?php
/**
 * Userdata type.
 */
namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
//use Validator\Constraints as CustomAssert;

/**
 * Class UserdataType.
 *
 * @package Form
 */
class UserdataType extends AbstractType
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
                'required' => false,
                'attr' => [
                    'max_length' => 45,
                ],
                'constraints' => [
//                    new Assert\NotBlank(
//                        [
//                            'groups' => ['profile-default']
//                        ]
//                    ),
                    new Assert\Length(
                        [
                            'max' => 45,
                            'groups' => ['profile-default']
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
//                    new Assert\NotBlank(
//                        [
//                            'groups' => ['profile-default']
//                        ]
//                    ),
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'profile_type';
    }
}