<?php
/**
 * Photo type.
 */
namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

/**
 * Class PhotoType.
 *
 * @package Form
 */
class RatingType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if(!isset($options['data']) || !isset($options['data']['id'])) { //zeby kazdy mogl tylko raz ocenic zdjecie
            $builder->add(
                'value',
                ChoiceType::class,
                [
                    'label' => 'label.value ',
                    'required' => true,
                    'attr' => array('class' => 'form-control'),
                    'placeholder' => 'choose_rating',
//                    'choices' => [1, 2, 3, 4, 5],
                    'choices'=>$this->prepareValuesForChoices(),
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'rating_type';
    }

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