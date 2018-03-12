<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace App\Service;

use App\Entity\Candidate;
use App\Entity\Voter;

/**
 * Interface VotingServiceInterface
 */
interface VotingServiceInterface
{
    public function makeVoterVoteFor(Voter $voter, Candidate $choosenCandidate);
}