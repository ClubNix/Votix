<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace AppBundle\Service;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Class StatsService
 * @package AppBundle\Service
 */
class StatsService implements StatsServiceInterface
{

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getStats()
    {
        return $this->getStatsQuery($grouped = false)->getSingleResult();
    }

    public function getStatsByPromotion()
    {
        return $this->getStatsQuery($grouped = true)->getResult();
    }

    private function getStatsQuery($grouped = false) {
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

        $qb ->from('AppBundle:Voter', 'v');

        if($grouped) {
            $qb ->groupBy('v.promotion')
                ->orderBy('v.promotion');
        }

        return $qb->getQuery();
    }
}