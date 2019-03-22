<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 *
 * @license MIT
 */
namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Class StatsService
 */
class StatsService implements StatsServiceInterface
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return mixed
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getStats()
    {
        return $this->getStatsQuery($grouped = false)->getSingleResult();
    }

    public function getStatsByPromotion()
    {
        return $this->getStatsQuery($grouped = true)->getResult();
    }

    private function getStatsQuery($grouped = false)
    {
        $qb = $this->entityManager->createQueryBuilder();

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

        if($grouped) {
            $qb ->groupBy('v.promotion')
                ->orderBy('v.promotion');
        }

        return $qb->getQuery();
    }
}