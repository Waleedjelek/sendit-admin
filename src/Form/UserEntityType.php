<?php

namespace App\Form;

use App\Entity\UserEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserEntityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('firstName', TextType::class, [
            'required' => true,
            'constraints' => [
                new NotBlank(),
            ],
        ]);

        $builder->add('lastName', TextType::class, [
            'required' => true,
            'constraints' => [
                new NotBlank(),
            ],
        ]);

        $builder->add('email', EmailType::class, [
            'required' => true,
            'constraints' => [
                new NotBlank(),
            ],
        ]);

        $builder->add('role', ChoiceType::class, [
            'label' => 'Role',
            'required' => true,
            'expanded' => false,
            'multiple' => false,
            'choices' => $options['roles'],
        ]);

        $builder->add('active');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => UserEntity::class,
            'roles' => [],
        ]);
    }
}
