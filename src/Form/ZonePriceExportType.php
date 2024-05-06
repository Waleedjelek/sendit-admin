<?php

namespace App\Form;

use App\Entity\CompanyEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ZonePriceExportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var CompanyEntity $companyEntity */
        $companyEntity = $options['company'];

        if ('dom' == $companyEntity->getType()) {
            $builder->add('type', ChoiceType::class, [
                'label' => 'Method',
                'required' => true,
                'expanded' => false,
                'multiple' => false,
                'choices' => [
                    'Local' => 'local',
                ],
            ]);
        } else {
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
        }

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
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'company' => CompanyEntity::class,
        ]);
    }
}
