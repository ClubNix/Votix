<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 *
 * @license MIT
 */
namespace App\Repository;

use App\Entity\Candidate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Class CandidateRepository
 */
class CandidateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Candidate::class);
    }

    /**
     * @return Candidate[]
     */
    public function findAllShuffled(): array
    {
        $candidates = $this->findAll();

        shuffle($candidates);

        return $candidates;
    }

    /**
     * @param array $candidates [ ['name' => string, 'eligible' => true] ... ]
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function import($candidates): void
    {
        $this->_em->createQuery('DELETE FROM App:Candidate')->execute();

        foreach ($candidates as $info) {
            $candidate = new Candidate();
            $candidate->setName($info['name']);
            $candidate->setEligible($info['eligible']);

            $this->_em->persist($candidate);
        }

        $this->_em->flush();
    }
}