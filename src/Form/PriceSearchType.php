<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class PriceSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $countries = $options['countries'];
        $countryChoices = [];
        if (!empty($countries)) {
            foreach ($countries as $countryEntity) {
                $countryChoices[$countryEntity->getName()] = $countryEntity->getCode();
            }
        }

        $builder->add('countryFrom', ChoiceType::class, [
            'label' => 'From',
            'required' => true,
            'data' => 'AE',
            'expanded' => false,
            'attr' => [
                'class' => 'select2',
            ],
            'multiple' => false,
            'choices' => $countryChoices,
        ]);

        $builder->add('countryTo', ChoiceType::class, [
            'label' => 'To',
            'required' => true,
            'data' => 'IN',
            'expanded' => false,
            'attr' => [
                'class' => 'select2',
            ],
            'multiple' => false,
            'choices' => $countryChoices,
        ]);

        $builder->add('type', ChoiceType::class, [
            'label' => 'For',
            'required' => true,
            'expanded' => false,
            'multiple' => false,
            'choices' => [
                'Package' => 'package',
                'Document' => 'document',
            ],
        ]);

        $builder->add('weight', TextType::class, [
            'required' => true,
            'data' => '1.0',
            'label' => 'Weight (kg)',
            'constraints' => [
                new NotBlank(),
            ],
        ]);

        $builder->add('length', TextType::class, [
            'required' => true,
            'data' => '10',
            'label' => 'Length (cm)',
            'constraints' => [
                new NotBlank(),
            ],
        ]);

        $builder->add('width', TextType::class, [
            'required' => true,
            'data' => '10',
            'label' => 'Width (cm)',
            'constraints' => [
                new NotBlank(),
            ],
        ]);

        $builder->add('height', TextType::class, [
            'required' => true,
            'data' => '10',
            'label' => 'Height (cm)',
            'constraints' => [
                new NotBlank(),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'countries' => [],
        ]);
    }
}
