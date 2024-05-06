<?php

namespace App\Form;

use App\Entity\ZoneEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class ZoneEntityType extends AbstractType
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
            'label' => 'Import Column Code',
            'constraints' => [
                new NotBlank(),
            ],
        ]);

        $countries = $options['countries'];
        $countryChoices = [];
        if (!empty($countries)) {
            foreach ($countries as $countryEntity) {
                $countryChoices[$countryEntity->getName().' ('.$countryEntity->getCode().')'] = $countryEntity->getCode();
            }
        }

        $selectedCountries = $options['data']->getCountries();
        $selectedCountryList = [];
        foreach ($selectedCountries as $countryEntity) {
            $selectedCountryList[] = $countryEntity->getCode();
        }

        $builder->add('zoneCountries', ChoiceType::class, [
            'label' => 'Countries',
            'mapped' => false,
            'required' => true,
            'data' => $selectedCountryList,
            'expanded' => false,
            'attr' => [
                'class' => 'select2',
            ],
            'multiple' => true,
            'choices' => $countryChoices,
        ]);

        $builder->add('minDays', NumberType::class, [
            'label' => 'Minimum days to ship Order',
            'required' => true,
            'constraints' => [
                new NotBlank(),
                new Range(['min' => 0, 'max' => 999]),
            ],
        ]);

        $builder->add('maxDays', NumberType::class, [
            'label' => 'Maximum days to ship Order',
            'required' => true,
            'constraints' => [
                new NotBlank(),
                new Range(['min' => 0, 'max' => 999]),
            ],
        ]);

        $builder->add('active');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ZoneEntity::class,
            'countries' => [],
        ]);
    }
}
