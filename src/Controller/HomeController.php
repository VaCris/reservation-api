<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'api_version' => '1.0.0',
            'endpoints_count' => 15,
            'uptime' => round((time() - strtotime('2025-11-01')) / 86400),
        ]);
    }
}