<?php

namespace App\Form;

use App\Entity\ZonePriceEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ZonePriceEntityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('type', ChoiceType::class, [
            'label' => 'Method',
            'required' => true,
            'expanded' => false,
            'multiple' => false,
            'choices' => [
                'Export' => 'export',
                'Import' => 'import',
            ],
        ]);

        $builder->add('for', ChoiceType::class, [
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
            'label' => 'Weight (kg)',
            'required' => true,
            'constraints' => [
                new NotBlank(),
            ],
        ]);

        $builder->add('price', TextType::class, [
            'label' => 'Price (AED)',
            'required' => true,
            'constraints' => [
                new NotBlank(),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ZonePriceEntity::class,
        ]);
    }
}
