<?php

namespace App\Form;

use App\Entity\CompanyEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class CompanyEntityType extends AbstractType
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
            'constraints' => [
                new NotBlank(),
            ],
        ]);

        $carriers = $options['carriers'];

        $builder->add('carrierCode', ChoiceType::class, [
            'label' => 'Carrier Code',
            'placeholder' => 'Choose an option',
            'required' => false,
            'expanded' => false,
            'attr' => [
                'class' => 'select2',
            ],
            'multiple' => false,
            'choices' => $carriers,
        ]);

        $builder->add('noteTitle', TextType::class, [
            'label' => 'Shipping Note Title',
            'required' => false,
            'constraints' => [
            ],
        ]);

        $builder->add('noteSummary', TextareaType::class, [
            'label' => 'Shipping Note Summary',
            'required' => false,
            'constraints' => [
            ],
        ]);

        $builder->add('type', ChoiceType::class, [
            'label' => 'Type',
            'required' => true,
            'expanded' => false,
            'multiple' => false,
            'choices' => [
                'Domestic' => 'dom',
                'International' => 'int',
            ],
        ]);

        $builder->add('boeThreshold', NumberType::class, [
            'label' => 'Bill of entry Threshold (AED)',
            'help' => 'To disable bill of amount make it 0 AED',
            'required' => true,
            'constraints' => [
                new NotBlank(),
                new Range(['min' => 0, 'max' => 9999]),
            ],
        ]);

        $builder->add('boeAmount', NumberType::class, [
            'label' => 'Bill of entry Amount (AED)',
            'required' => true,
            'constraints' => [
                new NotBlank(),
                new Range(['min' => 0, 'max' => 9999]),
            ],
        ]);

        /** @var CompanyEntity $companyEntity */
        $companyEntity = $builder->getData();

        if (is_null($companyEntity->getLogoImage())) {
            $builder->add('logoImageFile', FileType::class,
                [
                    'required' => true,
                    'help' => 'Only svg image allowed',
                    'mapped' => false,
                    'constraints' => [
                        new NotBlank(),
                        new File(([
                            'mimeTypes' => ['image/svg+xml'],
                            'mimeTypesMessage' => 'Only SVG file allowed.',
                        ])),
                    ],
                ]
            );
        } else {
            $builder->add('logoImageFile', FileType::class,
                [
                    'required' => false,
                    'help' => 'Only svg image allowed. Select a new image to replace the existing one.',
                    'mapped' => false,
                    'constraints' => [
                        new File(([
                            'mimeTypes' => ['image/svg+xml'],
                            'mimeTypesMessage' => 'Only SVG file allowed.',
                        ])),
                    ],
                ]
            );
        }

        $builder->add('logoWidth', NumberType::class, [
            'label' => 'Logo Width (px)',
            'required' => true,
            'constraints' => [
                new NotBlank(),
                new Range(['min' => 16, 'max' => 256]),
            ],
        ]);

        $builder->add('active');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CompanyEntity::class,
            'carriers' => [],
        ]);
    }
}
