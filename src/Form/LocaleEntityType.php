<?php

namespace App\Form;

use App\Entity\LocaleEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class LocaleEntityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('code', TextType::class, [
            'label' => 'Code',
            'required' => true,
            'constraints' => [
                new NotBlank(),
            ],
        ]);

        $builder->add('localeText', TextareaType::class, [
            'label' => 'Text',
            'required' => true,
            'attr' => [
                'rows' => '10',
            ],
            'constraints' => [
                new NotBlank(),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => LocaleEntity::class,
        ]);
    }
}
