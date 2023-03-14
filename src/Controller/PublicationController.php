<?php

namespace App\Controller;

use App\Entity\Publication;
use App\Form\NewPublicationFormType;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PublicationController extends AbstractController
{
    /*
     * Contrôleur de la page de création d'une nouvelle publication
     */
    #[Route('/nouvelle-publication', name: 'new_publication')]
    #[IsGranted('ROLE_ADMIN')]
    public function newPublication(Request $request, ManagerRegistry $doctrine): Response
    {
        // Création d'une publication vide
        $newPublication = new Publication ();

        // Création du formulaire vide lié à la publication vide
        $form = $this->createForm(NewPublicationFormType::class, $newPublication);

        // Liaison de données POST
        $form->handleRequest($request);

        // Condition d'envoi du formulaire sans erreurs
        If($form->isSubmitted() && $form->isValid()){

            // Hydratation de la date et de l'auteur
            $newPublication
                ->setPublicationDate( new \DateTime() )
                ->setAuthor( $this->getUser() )
            ;

            // Sauvegarde en BDD
            $em = $doctrine->getManager();
            $em->persist( $newPublication );
            $em->flush();

            // Message de succès
            $this->addFlash('success', 'Publication éditée avec succès.');

            // Redirection sur la page de l'article créé
            Return $this->redirectToRoute('publication_view', [
                'slug' => $newPublication->getSlug(),
            ]);

        }
        return $this->render('publication/new_publication.html.twig', [
            'new_publication_form' => $form->createView(),
        ]);
    }

    /*
     * Contrôleur de la page listant toutes les publications
     */
    #[Route('/publications/liste/', name: 'publication_list')]
    Public function publicationList(ManagerRegistry $doctrine): response
    {
        $publicationRepo = $doctrine->getRepository( Publication::class );
        $publications = $publicationRepo->findAll();
        Return $this->render('publication/publication_list.html.twig', [
            'publications' => $publications,
        ]);
    }

    /*
     * Contrôleur de la page pour lire un article
     */
    #[Route('/publication/{slug}/', name: 'publication_view')]
    Public function publicationView(Publication $publication): response
    {
        Return $this->render('publication/publication_view.html.twig', [
            'publication' => $publication,
        ]);
    }

}
