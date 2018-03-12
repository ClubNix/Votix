<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace App\Service;

/**
 * Interface TokenServiceInterface
 */
interface TokenServiceInterface
{
    public function verifyVoterToken($voter, $token);

    public function verifyVoterCode($voter, $code);

    public function getTokenForVoter($voter);

    public function getCodeForVoter($voter);

    public function getLinkForVoter($voter);
}