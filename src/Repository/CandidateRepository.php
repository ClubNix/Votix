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
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

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

        // Shuffle the candidates except "blank" that stays at the end
        $blank = array_pop($candidates);

        shuffle($candidates);

        $candidates[] = $blank;

        return $candidates;
    }

    /**
     * @param array $candidates [ ['name' => string, 'eligible' => true] ... ]
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function import(array $candidates): void
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
