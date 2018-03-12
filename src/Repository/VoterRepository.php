<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace App\Repository;

use App\Entity\Voter;
use Doctrine\ORM\EntityRepository;

/**
 * Class VoterRepository
 */
class VoterRepository extends EntityRepository
{

    /**
     * @return Voter[]
     */
    public function findAllSortedByPromotion()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb ->select('v')
            ->from('App:Voter', 'v')
            ->orderBy('v.promotion');

        return $voters = $qb->getQuery()->getResult();
    }

    /**
     * @param Voter $voter
     */
    public function save($voter)
    {
        $this->_em->persist($voter);
        $this->_em->flush();
    }

    /**
     *
     */
    public function deleteAll()
    {
        $this->_em->createQuery('DELETE FROM App:Voter')->execute();
        $this->_em->flush();
    }
}