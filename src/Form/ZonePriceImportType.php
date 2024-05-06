<?php

namespace App\Form;

use App\Entity\CompanyEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class ZonePriceImportType extends AbstractType
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

        $builder->add('csvFile', FileType::class, [
            'required' => true,
            'help' => 'Only csv file allowed',
            'constraints' => [
                new NotBlank(['message' => 'Please select csv file to upload.']),
//                new File(([
//                    'mimeTypes' => ['text/csv', 'text/plain'],
//                    'mimeTypesMessage' => 'Only CSV file allowed.',
//                ])),
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
