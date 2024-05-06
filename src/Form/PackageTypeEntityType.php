<?php

namespace App\Form;

use App\Entity\PackageTypeEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class PackageTypeEntityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', TextType::class, [
            'required' => true,
            'constraints' => [
                new NotBlank(),
            ],
        ]);

        $builder->add('type', ChoiceType::class, [
            'label' => 'Type',
            'required' => true,
            'expanded' => false,
            'multiple' => false,
            'choices' => [
                'Package' => 'package',
                'Document' => 'document',
            ],
        ]);

        /** @var PackageTypeEntity $packageTypeEntity */
        $packageTypeEntity = $builder->getData();
        $editMode = $builder->getOption('edit_mode', false);

        $builder->add('code', TextType::class, [
            'required' => true,
            'attr' => [
                'readonly' => $editMode,
            ],
            'constraints' => [
                new NotBlank(),
            ],
        ]);

        $builder->get('code')->addModelTransformer(new CallbackTransformer(
            function ($value) {
                if (is_null($value)) {
                    return null;
                }
                $slugger = new AsciiSlugger();

                return $slugger->slug($value);
            },
            function ($value) {
                if (is_null($value)) {
                    return null;
                }
                $slugger = new AsciiSlugger();

                return $slugger->slug($value);
            }
        ));

        $builder->add('description', TextareaType::class, [
            'required' => false,
            'constraints' => [
            ],
        ]);

        if (is_null($packageTypeEntity->getPackageImage())) {
            $builder->add('packageImageFile', FileType::class,
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
            $builder->add('packageImageFile', FileType::class,
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

        if (is_null($packageTypeEntity->getIconImage())) {
            $builder->add('iconImageFile', FileType::class,
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
            $builder->add('iconImageFile', FileType::class,
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

        $builder->add('weight', NumberType::class, [
            'label' => 'Weight (kg)',
            'required' => false,
            'constraints' => [
                new Range(['min' => 0, 'max' => 1000]),
            ],
        ]);

        $builder->add('maxWeight', NumberType::class, [
            'label' => 'Max Weight (kg)',
            'required' => true,
            'constraints' => [
                new NotBlank(),
                new Range(['min' => 1, 'max' => 10000]),
            ],
        ]);

        $builder->add('length', NumberType::class, [
            'label' => 'Length (cm)',
            'required' => false,
            'constraints' => [
                new Range(['min' => 0, 'max' => 5000]),
            ],
        ]);

        $builder->add('width', NumberType::class, [
            'label' => 'Width (cm)',
            'required' => false,
            'constraints' => [
                new Range(['min' => 0, 'max' => 5000]),
            ],
        ]);

        $builder->add('height', NumberType::class, [
            'label' => 'Height (cm)',
            'required' => false,
            'constraints' => [
                new Range(['min' => 0, 'max' => 5000]),
            ],
        ]);

        $builder->add('valueRequired', CheckboxType::class, [
            'label' => 'Value Required',
            'required' => false,
        ]);

        $builder->add('sortOrder', NumberType::class, [
            'label' => 'Sort Order',
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
            'edit_mode' => false,
            'data_class' => PackageTypeEntity::class,
        ]);
    }
}
