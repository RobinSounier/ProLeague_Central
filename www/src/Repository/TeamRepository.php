<?php

namespace App\Repository;

use App\Entity\Team;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Team>
 */
class TeamRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Team::class);
    }

    //    /**
    //     * @return Team[] Returns an array of Team objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Team
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }


    public function findActive(int $id): ?Team
    {
        $qb = $this->createQueryBuilder('c')
            // Charger les relations d'un coup (éviter N+1 queries)
            ->leftJoin('c.team', 'cat')
            ->leftJoin('c.user', 'u')
            // Filtre : actif ET ID correct
            ->where('c.isActive = :isActive')
            ->andWhere('c.id = :id')
            ->setParameter('isActive', true)
            ->setParameter('id', $id);

        return $qb->getQuery()->getOneOrNullResult();
    }


    public function findAllWithFilters(int $gameID = 0): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.game', 'cat')
            ->where('c.isActive = :isActive')
            ->setParameter('isActive', true)
            ->groupBy('c.id');

        //Filtre par categorie
        if ($gameID > 0) {
            $qb->andWhere('cat.id = :gameID')
                ->setParameter('gameID', $gameID);
        }

        return $qb->getQuery()->getResult();


    }
}
