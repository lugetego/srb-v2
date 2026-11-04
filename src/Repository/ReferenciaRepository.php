<?php

namespace App\Repository;

use App\Entity\Referencia;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class ReferenciaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Referencia::class);
    }

    public function buildSearchQuery(array $criteria): QueryBuilder
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.authors', 'a')
            ->addSelect('a');

        if (!empty($criteria['title'])) {
            $qb->andWhere('LOWER(r.title) LIKE :title')
               ->setParameter('title', '%' . strtolower($criteria['title']) . '%');
        }

        if (!empty($criteria['type']) && $criteria['type'] !== 'all') {
            $qb->andWhere('LOWER(r.type) = :type')
               ->setParameter('type', strtolower($criteria['type']));
        }

        if (!empty($criteria['author'])) {
            $qb->andWhere('LOWER(r.author) LIKE :author')
               ->setParameter('author', '%' . strtolower($criteria['author']) . '%');
        }

        if (!empty($criteria['keyword'])) {
            $qb->andWhere('LOWER(r.title) LIKE :keyword OR LOWER(r.author) LIKE :keyword OR LOWER(r.abst) LIKE :keyword OR LOWER(r.keywords) LIKE :keyword')
               ->setParameter('keyword', '%' . strtolower($criteria['keyword']) . '%');
        }

        if (empty($criteria['allYears'])) {
            $yearField = ($criteria['type'] ?? '') === 'preprint' ? 'r.yearPreprint' : 'r.yearPub';
            if (!empty($criteria['yearStart'])) {
                $qb->andWhere($yearField . ' >= :yearStart')
                   ->setParameter('yearStart', $criteria['yearStart']);
            }
            if (!empty($criteria['yearEnd'])) {
                $qb->andWhere($yearField . ' <= :yearEnd')
                   ->setParameter('yearEnd', $criteria['yearEnd']);
            }
        }

        return $qb;
    }

    public function getDataTableData(array $params, array $criteria = []): array
    {
        $qb = $this->buildSearchQuery($criteria);

        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(DISTINCT r.id)')->getQuery()->getSingleScalarResult();

        $search = $params['search']['value'] ?? '';
        if ($search !== '') {
            $qb->andWhere(
                'LOWER(r.title) LIKE :search OR LOWER(r.author) LIKE :search OR LOWER(r.journal) LIKE :search'
            )->setParameter('search', '%' . strtolower($search) . '%');
        }

        $filteredCountQb = clone $qb;
        $filtered = (int) $filteredCountQb->select('COUNT(DISTINCT r.id)')->getQuery()->getSingleScalarResult();

        // columns: 0=title, 1=author, 2=type, 3=year
        $columnMap = ['r.title', 'r.author', 'r.type', 'r.yearPub'];
        $orderCol = (int) ($params['order'][0]['column'] ?? 3);
        $orderDir = strtoupper($params['order'][0]['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        $orderField = $columnMap[$orderCol] ?? 'r.yearPub';

        $qb->select('DISTINCT r')
           ->orderBy($orderField, $orderDir)
           ->addOrderBy('r.yearPreprint', $orderDir)
           ->setFirstResult((int) ($params['start'] ?? 0))
           ->setMaxResults((int) ($params['length'] ?? 25));

        $results = $qb->getQuery()->getResult();

        return ['total' => $total, 'filtered' => $filtered, 'data' => $results];
    }

    public function getTopKeywords(int $limit = 30): array
    {
        $rows = $this->createQueryBuilder('r')
            ->select('r.keywords')
            ->where('r.keywords IS NOT NULL')
            ->andWhere("r.keywords != ''")
            ->getQuery()
            ->getScalarResult();

        $counts = [];
        foreach ($rows as $row) {
            $parts = preg_split('/[;,]/', $row['keywords'] ?? '');
            foreach ($parts as $kw) {
                $kw = trim($kw);
                if ($kw === '') continue;
                if (str_word_count($kw) > 4 || mb_strlen($kw) > 45) continue;
                $key = mb_strtolower($kw);
                if (!isset($counts[$key])) {
                    $counts[$key] = ['label' => $kw, 'count' => 0];
                }
                $counts[$key]['count']++;
            }
        }

        usort($counts, fn($a, $b) => $b['count'] - $a['count']);

        return array_slice($counts, 0, $limit);
    }

    public function getNextPreprintNumber(int $year): string
    {
        $conn = $this->getEntityManager()->getConnection();
        $max  = (int) $conn->fetchOne(
            "SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(report_number, '-', -1) AS UNSIGNED)), 0)
               FROM referencia
              WHERE report_number LIKE :pattern",
            ['pattern' => 'UNAM-CCM-' . $year . '-%']
        );

        return 'UNAM-CCM-' . $year . '-' . ($max + 1);
    }
}
