<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            // Champs email
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
                'attr' => [
                    'placeholder' => "Adresse email valide",
                ],
                'constraints' => [
                    New NotBlank([
                        'message' => 'Merci de renseigner une adresse mail.',
                    ]),
                    New Email([
                        'message' => 'L\'adresse email {{ value }} n\'est pas valide.',
                    ]),
                ]
            ])

            // champs mot de passe
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Confirmation du mot de passe incorrecte.',
                'first_options' => [
                    'label' => 'Mot de passe',
                    'attr' => [
                        'placeholder' => "12 caractères dont au moins une minuscule, une majuscule, un chiffre et un caractère spécial",
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirmation du mot de passe',
                    'attr' => [
                        'placeholder' => "Identique au mot de passe",
                    ],
                ],
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir un mot de passe.',
                    ]),
                    new Length([
                        'min' => 12,

                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                        'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Votre mot de passe doit contenir au maximum {{ limit }} caractères.',
                    ]),
                    new Regex([
                        'pattern' => "/^(?=.*[0-9])(?=.*[a-z])(?=.*[A-Z])(?=.*[ !\"#\$%&\'()*+,\-.\/:;<=>?@[\\\\\]\^_`{\|}~]).{8,4096}$/u",
                        'message' => 'Votre mot de passe doit être composé d\'au moins une minuscule, une majuscule, un chiffre et un caractère spécial.',
                    ]),
                ],
            ])

            ->add('pseudonym', TextType::class, [
                'label' => 'Pseudonyme',
                'attr' => [
                    'placeholder' => "Ex : Fanny"
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir un mot de passe.',
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 40,
                        'minMessage' => 'Votre pseudonyme doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Votre pseudonyme doit contenir au maximum {{ limit }} caractères.',
                    ]),
                ],
            ])

            // Bouton de validation
            ->add('save', SubmitType::class,[
                'label' => 'Créer un compte',
                'attr' => [
                    'class' => 'btn btn-outline-secondary w-100 my-4',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
