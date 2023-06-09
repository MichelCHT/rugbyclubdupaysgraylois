<?php

namespace App\Controller;

use App\Form\ResetPasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use App\Repository\UserRepository;
use App\Service\SendMailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /*
     * Contrôleur de la page de connexion
     */
    #[Route(path: '/connexion/', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
         // Redirection de l'utilisateur déjà connecté, sur la page d'accueil
        if ($this->getUser()) {
             return $this->redirectToRoute('main_home');
         }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error
        ]);
    }

    /*
     * Contrôleur de la page de déconnexion
     */
    #[Route(path: '/deconnexion/', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /*
     * Contrôleur de la page de mot de passe oublié
     */
    #[Route(path: '/oubli-pass/', name: 'forgotten_password')]
    public function forgottenPassword(Request $request, UserRepository $userRepository, TokenGeneratorInterface $tokenGeneratorInterface, EntityManagerInterface $entityManager, SendMailService $mail): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            // Recherche de l'utilisateur par son email
            $user = $userRepository->findOneByEmail($form->get('email')->getData());

            // Vérification d'avoir trouvé un utilisateur
            if($user){

                // Génération d'un token de réinitialisation
                $token = $tokenGeneratorInterface->generateToken();
                $user->setResetToken($token);
                $entityManager->persist($user);
                $entityManager->flush();

                // Génération d'un lien de réinitialisation du mot de passe
                $url = $this->generateUrl('reset_pass', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

                // Création des données du mail
                $context = compact('url', 'user');

                // Envoi du mail
                $mail->send(
                    'nepasrepondre@rugbyclubdupaysgraylois.fr',
                    $user->getEmail(),
                    'Réinitialisation de votre mot de passe sur le site du Rugby Club du Pays Graylois',
                    'password_reset',
                    $context
                );

                // Message flash confirmant l'envoi du mail
                $this->addFlash('success', 'Email envoyé, veuillez vérifier votre boîte mail.');
                return $this->redirectToRoute('app_login');

            }

            // Si user est null
            $this->addFlash('error', 'Veuillez vérifier la saisie de votre adresse mail.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password_request.html.twig', [
            'requestPassForm' => $form->createView()
        ]);
    }

    #[Route('/oubli-pass/{token}', name:'reset_pass')]
    public function resetPass(string $token, Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        // Vérification de la présence du token en base de données
        $user = $userRepository->findOneByResetToken($token);

        if($user){
            $form = $this->createForm(ResetPasswordFormType::class);

            $form->handleRequest($request);

            if($form->isSubmitted() && $form->isValid()){

                // "Effaçage" du token
                $user->setResetToken('');
                $user->setPassword(
                    $passwordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );

                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', 'Mot de passe enregistré.');
                return $this->redirectToRoute('app_login');
            }

            return $this->render('security/reset_password.html.twig', [
                'passForm' => $form->createView()
            ]);
        }

        // Message flash en cas de token non-trouvé en bdd
        $this->addFlash('error', 'Vérification invalide.');
        return $this->redirectToRoute('app_login');

    }

}
