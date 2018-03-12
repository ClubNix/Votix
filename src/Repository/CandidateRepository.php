<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace App\Repository;

use App\Entity\Candidate;
use Doctrine\ORM\EntityRepository;

/**
 * Class CandidateRepository
 */
class CandidateRepository extends EntityRepository {

    /**
     * @return Candidate[]
     */
    public function findAllShuffled()
    {
        $candidates = $this->findAll();

        shuffle($candidates);

        return $candidates;
    }

    /**
     * @param array $candidates [ ['name' => string, 'eligible' => true] ... ]
     */
    public function import($candidates)
    {
        $this->_em->createQuery('DELETE FROM App:Candidate')->execute();

        foreach($candidates as $info) {
            $candidate = new Candidate();
            $candidate->setName($info['name']);
            $candidate->setEligible($info['eligible']);
            $this->_em->persist($candidate);
        }

        $this->_em->flush();
    }
}