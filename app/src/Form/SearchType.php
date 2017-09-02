<?php
/**
 * Search type.
 */
namespace Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

/**
 * Class SearchType.
 *
 * @package Form
 */
class SearchType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'category',
            ChoiceType::class,
            [
                'label' => 'label.category ',
                'required' => true,
                'attr' => array('class' => 'form-control'),
                'choices'=>$this->prepareCategoriesForChoices(),
                'constraints' => [
                    new Assert\NotBlank(
                        ['groups' => ['search-default']]
                    ),
                ],
            ]
        );
        $builder->add(
            'value',
            TextType::class,
            [
                'label' => 'label.value',
                'required'   => false,
                'attr' => [
                ],
                'constraints' => [
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
                'validation_groups' => ['search-default'],
            ]
        );
    }
    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'search_type';
    }


    protected function prepareCategoriesForChoices()
    {
        $categories = ['photo', 'user'];
        $choices = [];

        foreach ($categories as $category) {
            $choices[$category] = $category;
        }

        return $choices;
    }

}