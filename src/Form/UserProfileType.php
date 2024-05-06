<?php

namespace App\Form;

use App\Entity\UserEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /**
         * @var $user UserEntity
         */
        $user = $options['user'];

        $builder->add('firstName', TextType::class, [
            'required' => true,
            'data' => $user->getFirstName(),
            'constraints' => [
                new NotBlank(),
            ],
        ]);

        $builder->add('lastName', TextType::class, [
            'required' => true,
            'data' => $user->getLastName(),
            'constraints' => [
                new NotBlank(),
            ],
        ]);

        $builder->add('email', TextType::class, [
            'required' => true,
            'disabled' => true,
            'data' => $user->getEmail(),
            'constraints' => [
                new NotBlank(),
            ],
        ]);

        $builder->add('countryCode', TextType::class, [
            'required' => true,
            'disabled' => true,
            'data' => $user->getCountryCode(),
            'constraints' => [
                new NotBlank(),
            ],
        ]);

        $builder->add('mobile', TextType::class, [
            'required' => true,
            'data' => $user->getMobileNumber(),
            'constraints' => [
                new NotBlank(),
            ],
        ]);

        $builder->add('profileImage', FileType::class,
            [
                'required' => false,
                'constraints' => [
                    new Image([
                        'minWidth' => 100,
                        'minHeight' => 100,
                        'maxWidth' => 2048,
                        'maxHeight' => 2048,
                    ]),
                ],
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'user' => UserEntity::class,
        ]);
    }
}
