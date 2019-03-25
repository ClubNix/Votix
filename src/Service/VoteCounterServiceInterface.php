<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 *
 * @license MIT
 */
namespace App\Service;

/**
 * Interface VoteCounterServiceInterface
 */
interface VoteCounterServiceInterface
{
    public function countEncryptedVotes(string $privateKey): array;

    public function verifyVoteCountingPassword(string $password): bool;

    public function hashResults(array $results, string $secret): string;
}