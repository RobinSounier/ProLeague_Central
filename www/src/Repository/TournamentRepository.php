<?php

namespace App\Repository;

use App\Entity\Tournament;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tournament>
 */
class TournamentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tournament::class);
    }

    public function findAllWithFilters(int $gameID = 0, string $sortBy = 'recent'): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.game', 'cat')
            ->leftJoin('c.teams', 'u')
            ->leftJoin('c.votes', 'v')
            ->leftJoin('c.medias', 'm')
            ->where('c.isActive = :isActive')
            ->setParameter('isActive', true)
            ->groupBy('c.id');

        //Filtre par categorie
        if ($gameID > 0) {
            $qb->andWhere('cat.id = :gameID')
                ->setParameter('gameID', $gameID);
        }


        //filtre de tri
        switch ($sortBy) {
            case 'popular':
                $qb->addSelect('COUNT(v.id) as HIDDEN voteCount')
                    ->OrderBy('voteCount', 'DESC');
                break;
            case 'oldest':
                $qb->OrderBy('c.createdAt', 'ASC');
                break;
            case 'recent':
            default:
                $qb->OrderBy('c.createdAt', 'DESC');
                break;

        }

        return $qb->getQuery()->getResult();


    }
}
