<?php

namespace App\Controller;

use App\Form\SearchType;
use App\Repository\ReferenciaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SearchController extends AbstractController
{
    #[Route('/busqueda', name: 'app_search', methods: ['GET', 'POST'])]
    public function search(Request $request, ReferenciaRepository $repo): Response
    {
        $criteria = [];
        $form = $this->createForm(SearchType::class, $criteria, ['method' => 'GET']);
        $form->handleRequest($request);

        $results = null;
        $total = 0;

        if ($form->isSubmitted() && $form->isValid()) {
            $criteria = $form->getData();
            $qb = $repo->buildSearchQuery($criteria);
            $results = $qb->getQuery()->getResult();
            $total = count($results);
        }

        return $this->render('search/index.html.twig', [
            'form'     => $form,
            'results'  => $results,
            'total'    => $total,
            'criteria' => $criteria,
        ]);
    }

}
