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
 * Interface VoteCounterServiceInterface
 */
interface VoteCounterServiceInterface
{
    public function countEncryptedVotes($privateKey);

    public function verifyPassword($password);
}