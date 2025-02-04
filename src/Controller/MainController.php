<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    #[Route('/{vueRouting}', name: 'app_vue', requirements: ['vueRouting' => '^(?!api).*$'], defaults: ['vueRouting' => null])]
    public function index(): Response
    {
        return $this->render('vue/index.html.twig');
    }
}
