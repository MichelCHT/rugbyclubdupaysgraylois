<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Recaptcha\RecaptchaValidator;
use App\Repository\UserRepository;
use App\Service\JWTService;
use App\Service\SendMailService;
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
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, RecaptchaValidator $recaptcha, SendMailService $mail, JWTService $jwt): Response
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

                // Génération d'un token
                // Header :
                $header = [
                  'typ' => 'JWT',
                  'alg' => 'HS256'
                ];

                // Payload :
                $payload = [
                  'user_id' => $user->getId()
                ];

                // Token :
                $token = $jwt->generate($header, $payload, $this->getParameter('app.jwtsecret'));

                // Message flash de succès
                $this->addFlash('success', 'Votre compte à bien été créé.');

                // Envoi d'un mail à valider
                $mail->send(
                    'nepasrepondre@rugbyclubdupaysgraylois.fr',
                    $user->getEmail(),
                    'Activation de votre compte sur le site du Rugby Club du Pays Graylois',
                    'register',
                    compact('user', 'token')
                );

                // Redirection de l'utilisateur vers la page de connexion après le succès de la création de son compte
                return $this->redirectToRoute('app_login');
            }

        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verif/{token}', name: 'verify_user')]
    public function verifyUser($token, JWTService $jwt, UserRepository $userRepository, EntityManagerInterface $em): Response
    {

        // Vérification de la validité, de la non-expiration et de la non-modification du token
        if($jwt->isValid($token) && !$jwt->isExpired($token) && $jwt->check($token, $this->getParameter('app.jwtsecret'))){

            // Récupération du payload
            $payload = $jwt->getPayload($token);

            // Récupération du user du token
            $user = $userRepository->find($payload['user_id']);

            // Vérification de l'existence de l'utilisateur et de la non-activation de son compte
            if($user && !$user->getIsVerified()){
                $user->setIsVerified(true);
                $em->flush($user);

                // Message flash de succès
                $this->addFlash('success', 'Compte vérifié.');

                // Redirection vers la page de profil
                return $this->redirectToRoute('main_profile');
            }

        }

        // Message flash en cas de token non-valide ou expiré
        $this->addFlash('danger', 'Lien expiré ou non-valide.');

        // Redirection vers la page de connexion
        return $this->redirectToRoute('app_login');

    }

    #[Route('/renvoiverif', name: 'resend_verif')]
    public function resendVerif(JWTService $jwt, SendMailService $mail, UserRepository $userRepository): Response
    {
        $user = $this->getUser();

        if(!$user){
            $this->addFlash('danger', 'Vous devez être connecté pour accéder à cette page.');
            return $this->redirectToRoute('app_login');
        }

        if($user->getIsVerified()){
            $this->addFlash('warning', 'Votre compte est déjà activé.');
            return $this->redirectToRoute('main_profile');
        }

        // Génération d'un token
        // Header :
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];

        // Payload :
        $payload = [
            'user_id' => $user->getId()
        ];

        // Token :
        $token = $jwt->generate($header, $payload, $this->getParameter('app.jwtsecret'));

        // Message flash de succès
        $this->addFlash('success', 'Votre compte à bien été créé.');

        // Envoi d'un mail à valider
        $mail->send(
            'nepasrepondre@rugbyclubdupaysgraylois.fr',
            $user->getEmail(),
            'Activation de votre compte sur le site du Rugby Club du Pays Graylois',
            'register',
            compact('user', 'token')
        );

        // Message flash de renvoi du lien de vérification
        $this->addFlash('success', 'Email de vérification envoyé');

        // Redirection vers la page de profil
        return $this->redirectToRoute('main_profile');
    }

}
