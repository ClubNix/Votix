<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 *
 * @license MIT
 */
namespace App\Service;

use App\Entity\Voter;

/**
 * Interface TokenServiceInterface
 */
interface TokenServiceInterface
{
    public function verifyVoterToken(Voter $voter, string $token): bool;

    public function verifyVoterCode(Voter $voter, string $code): bool;

    public function getTokenForVoter(Voter $voter): string;

    public function getCodeForVoter(Voter $voter): string;

    public function getLinkForVoter(Voter $voter): string;
}