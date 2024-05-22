<?php

namespace App\Form;

use App\Entity\CouponEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class CouponEntityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('coupon', TextType::class, [
            'required' => true,
            'constraints' => [
                new NotBlank(),
            ],
        ]);
        $builder->add('discount', NumberType::class, [
            'required' => true,
            'attr' => [
                'min' => 0,
                'max' => 100,
            ],
            'constraints' => [
                new NotBlank(),
                new Range([
                    'min' => 0,
                    'max' => 100,
                    'notInRangeMessage' => 'The discount must be between {{ min }} and {{ max }}.',
                ]),
            ],
        ]);
      
        $builder->add('active');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CouponEntity::class,
            'carriers' => [],
        ]);
    }
}
