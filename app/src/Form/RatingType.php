<?php
/**
 * Rating type.
 */
namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Class RatingType.
 * @package Form
 */
class RatingType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!isset($options['data']) || !isset($options['data']['id'])) {
            $builder->add(
                'value',
                ChoiceType::class,
                [
                    'label' => 'label.value ',
                    'required' => true,
                    'attr' => array('class' => 'form-control'),
                    'placeholder' => 'choose_rating',
                    'choices' => $this->prepareValuesForChoices(),
                    'constraints' => [
                        new Assert\NotBlank(
                            ['groups' => ['rating-default']]
                        ),
                    ],
                ]
            );
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'validation_groups' => ['rating-default'],
                'rating_repository' => null,
            ]
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'rating_type';
    }

    /**
     * @return array
     */
    protected function prepareValuesForChoices()
    {
        $values = ['1', '2', '3', '4', '5'];
        $choices = [];

        foreach ($values as $value) {
            $choices[$value] = $value;
        }

        return $choices;
    }
}
