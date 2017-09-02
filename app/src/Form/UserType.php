<?php
/**
 * User type.
 */
namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Validator\Constraints as CustomAssert;


/**
 * Class UserType.
 *
 * @package Form
 */
class UserType extends AbstractType
{
    /**
     * {@inheritdoc}
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
                        ['groups' => ['user-default']]
                    ),
                    new Assert\Length(
                        [
                            'groups' => ['user-default'],
                            'min' => 3,
                            'max' => 45,
                        ]
                    ),
                    new CustomAssert\UniqueLogin(
                        [
                            'groups' => ['user-default'],
                            'repository' => isset($options['user_repository']) ? $options['user_repository'] : null,
                            'elementId' => isset($options['data']['id']) ? $options['data']['id'] : null,
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
                            'groups' => ['user-default']
                        ]
                    ),
                    new Assert\Length(
                        [
                            'groups' => ['user-default'],
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
                            'groups' => ['user-default']
                        ]
                    ),
                    new Assert\Length(
                        [
                            'groups' => ['user-default'],
                            'min' => 3,
                            'max' => 128,
                        ]
                    ),
                    new Assert\Email(
                        [
                            'groups' => ['user-default']
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
                'validation_groups' => ['user-default'],
                'user_repository' => null,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'user_type';
    }
}