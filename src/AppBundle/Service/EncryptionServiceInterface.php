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
 * Interface EncryptionServiceInterface
 * @package AppBundle\Service
 */
interface EncryptionServiceInterface {
    public function isArmed();

    public function verifyKey($key);

    public function decryptVote($vote, $key);

    public function encryptVote($vote);

    public function generateKeys();

    public function getGeneratedKeyFilePath();

    public function encryptSignature($signature);
}