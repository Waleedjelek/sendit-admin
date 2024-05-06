<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class QuoteAddNoteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('noteDescription', TextareaType::class, [
            'label' => 'Note',
            'required' => true,
            'constraints' => [
                new NotBlank(),
            ],
        ]);

        $builder->add('changeStatus', ChoiceType::class, [
            'label' => 'Change Status',
            'placeholder' => '',
            'required' => false,
            'expanded' => false,
            'multiple' => false,
            'choices' => [
                'New' => 'New',
                'Spam' => 'Spam',
                'Processing' => 'Processing',
                'Completed' => 'Completed',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
        ]);
    }
}
