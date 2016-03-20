<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace AppBundle\Service;

use AppBundle\Entity\Candidate;
use AppBundle\Entity\Voter;

/**
 * Interface VotingServiceInterface
 * @package AppBundle\Service
 */
interface VotingServiceInterface {
    public function makeVoterVoteFor(Voter $voter, Candidate $choosenCandidate);
}