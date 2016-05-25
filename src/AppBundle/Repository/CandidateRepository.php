<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace AppBundle\Repository;

use AppBundle\Entity\Candidate;
use Doctrine\ORM\EntityRepository;

/**
 * Class CandidateRepository
 * @package AppBundle\Repository
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
     * @param array $candidates
     */
    public function import($candidates)
    {
        $this->_em->createQuery('DELETE FROM AppBundle:Candidate')->execute();

        foreach($candidates as $info) {
            $candidate = new Candidate();
            $candidate->setName($info['name']);
            $candidate->setEligible($info['eligible']);
            $this->_em->persist($candidate);
        }

        $this->_em->flush();
    }
}