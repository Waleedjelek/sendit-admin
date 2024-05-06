<?php

namespace App\Form;

use App\Entity\CountryEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class CountryEntityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', TextType::class, [
            'required' => true,
            'constraints' => [
                new NotBlank(),
            ],
        ]);

        $builder->add('code', TextType::class, [
            'required' => true,
            'constraints' => [
                new NotBlank(),
            ],
        ]);

        $builder->add('dialCode', TextType::class, [
            'required' => true,
            'constraints' => [
                new NotBlank(),
            ],
        ]);

        $builder->add('sortOrder', ChoiceType::class, [
            'label' => 'Sort Order',
            'required' => true,
            'expanded' => false,
            'multiple' => false,
            'choices' => [
                'Bottom' => 0,
                '1' => 10,
                '2' => 20,
                '3' => 30,
                '4' => 40,
                '5' => 50,
                'Top' => 100,
            ],
        ]);

        $builder->add('active');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CountryEntity::class,
        ]);
    }
}
