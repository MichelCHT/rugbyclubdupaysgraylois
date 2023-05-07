<?php

namespace App\Controller;

use App\Entity\Publication;
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
    public function home(ManagerRegistry $doctrine): Response
    {
        // Récupération et affichage des dernières publications
        $publicationRepo = $doctrine->getRepository(Publication::class);

        $publications = $publicationRepo->findBy([], ['publicationDate' => 'DESC'], $this->getParameter('app.publication.number_of_latest_publications_on_home'));
        return $this->render('main/home.html.twig',[
            'publications' => $publications,
        ]);
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

            // Si présence d'une photo utilisateur, suppression de cette dernière
            if(
                $this->getUser()->getPhoto() != null &&
                file_exists( $this->getParameter('app.user.photo.directory') . $this->getUser()->getPhoto())
            ){

                // Suppression de l'ancienne photo
                unlink( $this->getParameter('app.user.photo.directory') . $this->getUser()->getPhoto() );
            }

            // Création d'un nouveau nom de fichier pour la nouvelle photo
            do{
                $newFileName = md5( random_bytes(100) ) . '.' . $photo->guessExtension();
            } while(file_exists( $this->getParameter('app.user.photo.directory') . $newFileName ));

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

    /**
     * Contrôleur de la page des mentions légales
     */
    #[Route('/mentions-legales/', name: 'legal_notice')]
    public function legalNotice(): Response
    {
        return $this->render('main/legal_notice.html.twig');
    }

    /**
     * Contrôleur de la page des conditions générales d'utilisation
     */
    #[Route('/conditions-generales-d-utilisation/', name: 'terms_of_service')]
    public function termsOfService(): Response
    {
        return $this->render('main/terms_of_service.html.twig');
    }
}
