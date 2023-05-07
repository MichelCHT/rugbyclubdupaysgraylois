<?php

namespace App\Form;

use App\Entity\Publication;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class NewPublicationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('title', TextType::class, [
                'label' => 'Titre',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir un titre.',
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 150,
                        'minMessage' => 'Le titre doit avoir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le titre ne peut avoir au maximum que {{ limit }} caractères.',
                    ]),
                ],
            ])

            ->add('content', CKEditorType::class, [
                'label' => 'Contenu',
                'attr' => [
                    'class' => 'd-none',
                ],
                'purify_html' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir un contenu.',
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 50000,
                        'minMessage' => 'Le contenu doit avoir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le contenu ne peut avoir au maximum que {{ limit }} caractères.',
                    ]),
                ],
            ])

            ->add('picture', FileType::class, [
                'label' => 'Veuillez choisir une photo (optionnel).',
                'attr' => [
                    'accept' => 'image/jpeg, image/png',
                ],
                'required' => false,
                'constraints' => [
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

            ->add('save', SubmitType::class, [
                'label' => 'Publier',
                'attr' => [
                    'class' => 'btn btn-outline-secondary w-100 my-4'
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Publication::class, null,
        ]);
    }
}
