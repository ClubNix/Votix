<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace AppBundle\Service;

/**
 * Interface VoteCounterServiceInterface
 * @package AppBundle\Service
 */
interface VoteCounterServiceInterface
{
    public function countEncryptedVotes($privateKey);

    public function verifyPassword($password);
}