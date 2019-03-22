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
     * @throws \Doctrine\ORM\ORMException
     */
    public function save(Voter $voter): void
    {
        $this->_em->persist($voter);
        $this->_em->flush();
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function deleteAll(): void
    {
        $this->_em->createQuery('DELETE FROM App:Voter')->execute();
        $this->_em->flush();
    }
}