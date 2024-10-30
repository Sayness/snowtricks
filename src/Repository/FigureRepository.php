<?php

// src/Repository/FigureRepository.php

namespace App\Repository;

use App\Entity\Figure;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
}
