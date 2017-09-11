<?php
/**
 * Profile type.
 */
namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class ProfileType.
 * @package Form
 */
class ProfileType extends AbstractType
{
    /**
     * Build Form
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'login',
            TextType::class,
            [
                'label' => 'label.login',
                'required' => true,
                'attr' => [
                    'max_length' => 45,
                    'readonly' => (isset($options['data']) && isset($options['data']['id'])),
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        [
                            'groups' => ['profile-default'],
                        ]
                    ),
                    new Assert\Length(
                        [
                            'min' => 3,
                            'max' => 45,
                            'groups' => ['profile-default'],
                        ]
                    ),

                ],
            ]
        );
        $builder->add(
            'password',
            PasswordType::class,
            [
                'label' => 'label.password',
                'required' => true,
                'attr' => [
                    'max_length' => 225,
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        [
                            'groups' => ['profile-default'],
                        ]
                    ),
                    new Assert\Length(
                        [
                            'groups' => ['profile-default'],
                            'min' => 8,
                            'max' => 128,
                        ]
                    ),
                ],
            ]
        );
        $builder->add(
            'mail',
            EmailType::class,
            [
                'label' => 'label.mail',
                'required' => true,
                'attr' => [
                    'max_length' => 128,
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        [
                            'groups' => ['user-default'],
                        ]
                    ),
                    new Assert\Length(
                        [
                            'min' => 3,
                            'max' => 128,
                            'groups' => ['profile-default'],
                        ]
                    ),
                    new Assert\Email(),
                ],
            ]
        );
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
                    new Assert\NotBlank(
                        [
                            'groups' => ['profile-default'],
                        ]
                    ),
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
                    new Assert\NotBlank(
                        [
                            'groups' => ['profile-default'],
                        ]
                    ),
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
     * Configure Options
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'validation_groups' => ['profile-default'],
                'profile_repository' => null,
            ]
        );
    }

    /**
     * Get Block Prefix
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'profile_type';
    }
}
