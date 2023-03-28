<?php

namespace App\Controller;

use App\Form\EditPhotoType;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    /**
     * Contrôleur de la page d'accueil
     */
    #[Route('/', name: 'main_home')]
    public function home(): Response
    {
        return $this->render('main/home.html.twig');
    }

    /*
     * Contrôleur de la page de profil
     */
    #[Route('/mon-profil/', name: 'main_profile')]
    #[IsGranted('ROLE_USER')]
    public function profile(): Response
    {
        return $this->render('main/profile.html.twig');
    }

    /*
     * Contrôleur de la page de modification de photo de profil
     */
    #[Route('/modifier-photo-de-profil/', name: 'main_edit_photo')]
    #[IsGranted('ROLE_USER')]
    public function editPhoto(Request $request, ManagerRegistry $doctrine): Response
    {
        $form = $this->createForm(EditPhotoType::class);

        $form->handleRequest($request);

        // récupération des données du fichier si celui-ci a été envoyé valide et changement de nom pour éviter une injection de code
        If($form-> isSubmitted() && $form-> isValid()){

            $photo = $form->get('photo')->getData();

            $newFileName = 'user' . $this->getUser()->getId() . '.' . $photo->guessExtension();

            // Sauvegarde du nom de la photo envoyé par l'user
            $this->getUser()->setPhoto($newFileName);
            $em = $doctrine->getManager();
            $em->flush();

            // Sauvegarde de la photo envoyée par l'user
            $photo->move(
                $this->getParameter('app.user.photo.directory'),
                $newFileName
            );

            $this->addFlash('success', 'Photo de profil modifiée.');

            Return $this->redirectToRoute('main_profile');
        }

        return $this->render('main/edit_photo.html.twig', [
            'form' => $form->createView(),
        ]);
    }

}
