<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 *
 * @license MIT
 */
namespace App\Repository;

use App\Entity\Voter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query;

/**
 * Class VoterRepository
 */
class VoterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Voter::class);
    }

    /**
     * @return Voter[]
     */
    public function findAllSortedByPromotion(): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb ->select('v')
            ->from('App:Voter', 'v')
            ->orderBy('v.promotion');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Voter $voter
     *
     * @throws ORMException
     */
    public function save(Voter $voter): void
    {
        $this->_em->persist($voter);
        $this->_em->flush();
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function deleteAll(): void
    {
        $this->_em->createQuery('DELETE FROM App:Voter')->execute();
        $this->_em->flush();
    }

    /**
     * @return mixed
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getStats()
    {
        return $this->getStatsQuery($grouped = false)->getSingleResult();
    }

    public function getStatsByPromotion()
    {
        return $this->getStatsQuery($grouped = true)->getResult();
    }

    private function getStatsQuery($grouped = false): Query
    {
        $qb = $this->_em->createQueryBuilder();

        $qb->select(
            [
                $grouped ? 'v.promotion     AS promotion' : null,
                'count(v.ballot) AS nb_votants',
                'count(v.id)     AS nb_invites',
                '(count(v.ballot) * 100.0) / count(v.id) AS ratio_float',
                '(count(v.ballot) * 100)   / count(v.id) AS ratio_int'
            ]
        );

        $qb->from('App:Voter', 'v');

        if ($grouped) {
            $qb ->groupBy('v.promotion')
                ->orderBy('v.promotion');
        }

        return $qb->getQuery();
    }
}
