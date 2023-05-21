<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Recaptcha\RecaptchaValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class RegistrationController extends AbstractController
{
    /*
     * Contrôleur de la page d'inscription
     */
    #[Route('/creer-un-compte/', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, RecaptchaValidator $recaptcha): Response
    {

        // Redirection de l'utilisateur déjà connecté, sur la page d'accueil
        if ($this->getUser()) {
            return $this->redirectToRoute('main_home');
        }

        // Création d'un nouvel objet utilisateur
        $user = new User();

        // Création du formulaire de création de compte hydratant $user
        $form = $this->createForm(RegistrationFormType::class, $user);

        // Remplissage du formulaire (via données POST situées dans $request)
        $form->handleRequest($request);

        // Condition si le formulaire a été envoyé
        if ($form->isSubmitted()) {

            // Récupération de la valeur du captcha
            $captchaResponse = $request->request->get('g-recaptcha-response', null);

            // Récupération de l'adresse IP de l'utilisateur
            $ip = $request->server->get('REMOTE_ADDR');

            // Ajout d'une erreur dans le formulaire en cas de valeur null du captcha
            if($captchaResponse == null || !$recaptcha->verify( $captchaResponse, $ip) ){

                $form->addError( new FormError('Veuillez remplir le captcha de sécurité') );
            }

            if($form->isValid()){
                // Hashage du mot de passe
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );

                // Hydratation de la date d'inscription de l'utilisateur
                $user->setRegistrationDate( new \DateTime() );

                // Enregistrement de l'utilisateur dans la bdd
                $entityManager->persist($user);
                $entityManager->flush();

                // Message flash de succès
                $this->addFlash('success', 'Votre compte à bien été créé.');

                // Redirection de l'utilisateur vers la page de connexion après le succès de la création de son compte
                return $this->redirectToRoute('app_login');
            }

        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
