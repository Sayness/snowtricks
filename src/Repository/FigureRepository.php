<?php

// src/Repository/FigureRepository.php

namespace App\Repository;

use App\Entity\Figure;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\EntityRepository;

class FigureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Figure::class);
    }

    public function save(Figure $figure, bool $flush = true): void
    {
        $this->getEntityManager()->persist($figure); 

        if ($flush) {
            $this->getEntityManager()->flush(); 
        }
    }

    public function findFigureWithMedia($slug): ?Figure
    {
        return $this->createQueryBuilder('f')
            ->leftJoin('f.mediaFiles', 'm') 
            ->addSelect('m') 
            ->where('f.slug = :slug') 
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
