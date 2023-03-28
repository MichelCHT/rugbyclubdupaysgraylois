<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class EditPhotoType extends AbstractType
{
    /*
     * Formulaire permettant de modifier la photo de profil
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('photo', FileType::class, [
                'label' => 'Veuillez choisir une photo',
                'attr' => [
                    'accept' => 'image/jpeg, image/png',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez choisir un fichier.',
                    ]),
                    new File([
                        'maxSize' => '5M',
                        'maxSizeMessage' => 'Fichier trop volumineux.',
                        'mimeTypes' => [
                                'image/jpeg',
                                'image/png',
                        ],
                        'mimeTypesMessage' => 'Veuillez choisir un fichier jpg ou png.',
                    ]),
                ],
            ])

            // Bouton de validation
            ->add('save', SubmitType::class, [
                'label' => 'Changer la photo',
                'attr' => [
                    'class' => 'btn btn-outline-secondary w-100'
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
