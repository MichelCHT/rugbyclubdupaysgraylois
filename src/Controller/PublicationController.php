<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Publication;
use App\Form\NewPublicationFormType;
use App\Form\CommentFormType;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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

            // Récupération de l'image envoyée
            $image = $form->get('picture')->getData();

            // Vérification que l'image a été envoyée
            if($image){
                // Génération d'un nom d'image unique
                $filename = md5(uniqid()) . '.' . $image->guessExtension();

                // Déplacement de l'image dans le répertoire configuré
                $image->move(
                    $this->getParameter('app.publication.picture.directory'),
                    $filename
                );

                // Mise à jour de l'entité Publication avec le nom de l'image
                $newPublication->setPicture($filename);
            }

            // Sauvegarde en BDD
            $em = $doctrine->getManager();
            $em->persist( $newPublication );
            $em->flush();

            // Message de succès
            $this->addFlash('success', 'Publication éditée avec succès.');

            // Redirection sur la page de la publication créée
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
    Public function publicationList(ManagerRegistry $doctrine, Request $request, PaginatorInterface $paginator): response
    {

        // Récupération du numéro de page demandé en URL
        $requestedPage = $request->query->getInt('page', 1);

        // Vérification de numéro positif
        if($requestedPage < 1){
            throw new NotFoundHttpException();
        }

        $em = $doctrine->getManager();

        // Requête DQL pour récupérer les publications
        $query = $em->createQuery('SELECT a FROM App\Entity\Publication a ORDER BY a.publicationDate DESC');

        // Récupération des publications
        $publications = $paginator->paginate(

            // Requête créée précedemment
            $query,

            // Numéro de la page demandée
            $requestedPage,

            // Nombre de publications affichées par page
            12
        );

        Return $this->render('publication/publication_list.html.twig', [
            'publications' => $publications,
        ]);
    }

    /*
     * Contrôleur de la page pour lire une publication
     */
    #[Route('/publication/{slug}/', name: 'publication_view')]
    Public function publicationView(Publication $publication, Request $request, ManagerRegistry $doctrine): response
    {
        // Si l'utilisateur n'est pas connecté, appel direct de la vue en lui envoyant la publication à afficher
        if(!$this->getUser()){

            return $this->render('publication/publication_view.html.twig', [
                'publication' => $publication,
            ]);
        }

        // Création d'un commentaire vide
        $comment = new Comment();

        // Création d'un formulaire de création de commentaire, lié au commentaire vide
        $form = $this->createForm(CommentFormType::class, $comment);

        // Liaison des données de requête (POST) avec le formulaire
        $form->handleRequest($request);

        // Si le formulaire est envoyé et n'a pas d'erreur
        if($form->isSubmitted() && $form->isValid()){

            // Hydratation du commentaire
            $comment

                // L'auteur est l'utilisateur connecté
                ->setAuthor($this->getUser())

                // Date actuelle liée à la publication affichée sur la page
                ->setPublicationDate(new DateTime())
                ->setPublication($publication)
            ;

            // Sauvegarde du commentaire en base de données via le manager général des entités
            $em = $doctrine->getManager();
            $em->persist($comment);
            $em->flush();

            // Message flash de succès
            $this->addFlash('success', 'Votre commentaire a été publié.');

            // Suppression des deux variables contenant le formulaire validé et le commentaire nouvellement créé (pour éviter que le nouveau formulaire soit rempli avec)
            unset($comment);
            unset($form);

            // Création d'un nouveau commentaire vide et de son formulaire lié
            $comment = new Comment();
            $form = $this->createForm(CommentFormType::class, $comment);
        }

        // Appel de la vue en lui envoyant la publication et le formulaire à afficher

        Return $this->render('publication/publication_view.html.twig', [
            'publication' => $publication,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Contrôleur de la page servant à supprimer une publication via un compte admin
     */
    #[Route('/publication/suppression/{id}/', name: 'publication_delete', priority: 10)]
    #[IsGranted('ROLE_ADMIN')]

    /**
     * @ParamConverter("publication", class="App\Entity\Publication")
     */
    public function publicationDelete(Publication $publication, Request $request, ManagerRegistry $doctrine): Response
    {

        // Si le token CSRF passé dans l'url n'est pas le token valide, message d'erreur
        if(!$this->isCsrfTokenValid('publication_delete_' . $publication->getId(), $request->query->get('csrf_token'))){

            // Message flash d'erreur
            $this->addFlash('error', 'Token sécurité invalide, veuillez ré-essayer.');
        } else {

            // Suppression de la publication via le manager général des entités
            $em = $doctrine->getManager();
            $em->remove($publication);
            $em->flush();

            // Message flash de succès
            $this->addFlash('success', 'La publication a été supprimée.');
        }

        // Redirection de l'utilisateur sur la liste des publications
        return $this->redirectToRoute('publication_list');

    }

    /**
     * Contrôleur de la page permettant de modifier une publication existante via son id passé dans l'url
     *
     * Accès réservé aux administrateurs (ROLE_ADMIN)
     */
    #[Route('/publication/modifier/{id}/', name: 'publication_edit', priority: 10)]
    #[IsGranted('ROLE_ADMIN')]
    public function publicationEdit(Publication $publication, Request $request, ManagerRegistry $doctrine): Response
    {

        // Création du formulaire de modification de publication (c'est le même que le formulaire permettant de créer une nouvelle publication, sauf qu'il sera déjà rempli avec les données de la publication existante "$publication")
        $form = $this->createForm(NewPublicationFormType::class, $publication);

        // Liaison des données de requête (POST) avec le formulaire
        $form->handleRequest($request);

        // Si le formulaire est envoyé et n'a pas d'erreur
        if($form->isSubmitted() && $form->isValid()){

            // Sauvegarde des changements faits dans la publication via le manager général des entités
            $em = $doctrine->getManager();
            $em->flush();

            // Message flash de succès
            $this->addFlash('success', 'Publication modifiée avec succès !');

            // Redirection vers la page de la publication modifiée
            return $this->redirectToRoute('publication_view', [
                'slug' => $publication->getSlug(),
            ]);

        }

        // Appel de la vue en lui envoyant le formulaire à afficher
        return $this->render('publication/publication_edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Contrôleur de la page permettant à un admin ou à l'auteur d'un commentaire de supprimer ce dernier
     */
    #[Route('/commentaire/suppression/{id}/', name: 'comment_delete')]
    public function commentDelete(Comment $comment, Request $request, ManagerRegistry $doctrine): Response
    {

        // Vérification que l'utilisateur est soit un admin, soit l'auteur du commentaire
        if ( !$this->isGranted('ROLE_ADMIN') && $comment->getAuthor() !== $this->getUser() ) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas effectuer cette action.');
        }

        // Si le token CSRF passé dans l'URL n'est pas valide
        if(!$this->isCsrfTokenValid('comment_delete' . $comment->getId(), $request->query->get('csrf_token'))){
            $this->addFlash('error', 'Token sécurité invalide.');
        } else {

            // Suppression du commentaire via le manager général des entités
            $em = $doctrine->getManager();
            $em->remove( $comment );
            $em->flush();

            // Message flash de succès
            $this->addFlash('success', 'Le commentaire a été supprimé.');

        }

        // Redirection de l'utilisateur sur la page détaillée de la publication
        return $this->redirectToRoute('publication_view', [
            'slug' => $comment->getPublication()->getSlug(),
        ]);

    }

}