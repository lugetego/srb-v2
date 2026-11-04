<?php

namespace App\Controller;

use App\Repository\AuthorRepository;
use App\Repository\ReferenciaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ReferenciaRepository $refs, AuthorRepository $authors): Response
    {
        $stats = [
            'total'       => $refs->count([]),
            'authors'     => $authors->count([]),
            'byType'      => $refs->createQueryBuilder('r')
                ->select('r.type, COUNT(r.id) as cnt')
                ->groupBy('r.type')
                ->orderBy('cnt', 'DESC')
                ->getQuery()
                ->getResult(),
            'topKeywords' => $refs->getTopKeywords(30),
        ];

        return $this->render('home/index.html.twig', ['stats' => $stats]);
    }
}
