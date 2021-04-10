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
 * Interface EncryptionServiceInterface
 */
interface EncryptionServiceInterface
{
    public function isArmed(): bool;

    public function verifyKey(string $key): array;

    public function decryptVote(string $vote, string $key): string;

    public function encryptVote(string $vote): string;

    public function generateKeys(): void;

    public function getGeneratedKeyFilePath(): string;

    public function encryptSignature(string $signature): array;
}