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
 * Class TokenService
 */
class TokenService implements TokenServiceInterface
{
    /**
     * @var string
     */
    private $pepper;

    /**
     * @var string
     */
    private $linkBase;

    /**
     * TokenService constructor.
     *
     * @param string $pepper
     * @param string $linkBase
     */
    public function __construct(string $pepper, string $linkBase)
    {
        $this->pepper   = hash('sha256', $pepper);
        $this->linkBase = $linkBase;
    }

    /**
     * Verify if the token provided is matching the one from the voter.
     *
     * @param Voter $voter
     * @param string $token
     *
     * @return bool true if the token is matching else false
     */
    public function verifyVoterToken(Voter $voter, string $token): bool
    {
        $expectedToken = $this->getTokenForVoter($voter);

        // Uses hash_equals to prevent timing attacks.
        return hash_equals($expectedToken, $token);
    }

    /**
     * Verify if the code provided is matching the one from the voter.
     *
     * @param Voter $voter
     * @param string $code
     *
     * @return bool true if the code is matching else false
     */
    public function verifyVoterCode(Voter $voter, string $code): bool
    {
        $expectedCode = $this->getCodeForVoter($voter);

        // Uses hash_equals to prevent timing attacks.
        return hash_equals($expectedCode, $code);
    }

    /**
     * Get the token for a voter.
     *
     * @param Voter $voter
     *
     * @return string
     */
    public function getTokenForVoter(Voter $voter): string
    {
        return $this->base64UrlEncode(hash('sha256', 'votix-' . hash('sha384', $voter->getEmail() . '-' . $this->pepper), true));
    }

    /**
     * Get the code for a voter.
     *
     * @param Voter $voter
     *
     * @return string Number composed of 4 digits.
     */
    public function getCodeForVoter(Voter $voter): string
    {
        $code = (string) crc32('votix-' . hash('sha384', $voter->getEmail() . '-' . $this->pepper));

        return str_pad(substr($code, 0, 4), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get the secret voting link for a voter.
     *
     * @param Voter $voter
     *
     * @return string
     */
    public function getLinkForVoter(Voter $voter): string
    {
        $token = $this->getTokenForVoter($voter);

        return $this->linkBase . $voter->getLogin() . '/' . $token;
    }

    /**
     * @param string $rawBytes
     *
     * @return string
     */
    private function base64UrlEncode(string $rawBytes): string
    {
        return str_replace(['+','/','='], ['-','_',''], base64_encode($rawBytes));
    }
}
