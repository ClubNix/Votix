<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 *
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
    public function makeVoterVoteFor(Voter $voter, Candidate $chosenCandidate): void;
}