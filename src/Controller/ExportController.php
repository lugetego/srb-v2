<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * API de exportación para el sistema externo. Protegida por el firewall "export_api"
 * (token + IP opcional, ver ExportApiTokenAuthenticator) — nunca por sesión/login.
 */
#[Route('/api/export')]
#[IsGranted('ROLE_API_EXPORT')]
class ExportController extends AbstractController
{
    #[Route('/{max<\d+>}', name: 'api_export_all', methods: ['GET'])]
    public function all(int $max, EntityManagerInterface $em): JsonResponse
    {
        $rows = $em->createQuery(
            'SELECT r FROM App\Entity\Referencia r ORDER BY r.id DESC'
        )
            ->setMaxResults(min($max, 5000))
            ->getArrayResult();

        return $this->jsonUnescaped($rows);
    }

    #[Route('/by-author/{author}/{year<\d{4}>}', name: 'api_export_by_author_year', methods: ['GET'])]
    public function byAuthorYear(string $author, string $year, EntityManagerInterface $em): JsonResponse
    {
        $rows = $em->createQuery(
            'SELECT r.type AS tipo,
                    r.title AS titulo,
                    r.author AS autores,
                    r.yearPub AS year,
                    r.pages AS pags,
                    r.volume AS volumen,
                    r.issue AS numero,
                    r.issn,
                    r.journal AS revista
             FROM App\Entity\Referencia r
             WHERE r.author LIKE :author AND r.yearPub = :year
             ORDER BY r.id DESC'
        )
            ->setParameter('author', '%' . $author . '%')
            ->setParameter('year', $year)
            ->getArrayResult();

        return $this->jsonUnescaped($rows);
    }

    private function jsonUnescaped(array $data): JsonResponse
    {
        $response = new JsonResponse($data);
        $response->setEncodingOptions($response->getEncodingOptions() | JSON_UNESCAPED_UNICODE);

        return $response;
    }
}
