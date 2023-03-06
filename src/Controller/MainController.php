<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
}
