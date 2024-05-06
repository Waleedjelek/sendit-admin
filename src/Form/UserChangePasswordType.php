<?php

namespace App\Form;

use App\Entity\UserEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /**
         * @var $user UserEntity
         */
        $user = $options['user'];

        $builder->add('currentPassword', PasswordType::class, [
            'required' => true,
            'label' => 'Current Password',
            'constraints' => [
                new NotBlank(),
            ],
        ]);

        $builder->add('password', RepeatedType::class, [
            'type' => PasswordType::class,
            'invalid_message' => 'The password fields must match.',
            'options' => ['attr' => ['class' => 'password-field']],
            'required' => true,
            'error_bubbling' => true,
            'first_options' => ['label' => 'New Password'],
            'second_options' => ['label' => 'Repeat New Password'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'user' => UserEntity::class,
        ]);
    }
}
