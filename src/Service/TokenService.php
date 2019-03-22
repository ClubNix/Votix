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
    private $salt;

    private $linkBase;

    /**
     * TokenService constructor.
     *
     * @param string $salt
     * @param string $linkBase
     */
    public function __construct(string $salt, string $linkBase)
    {
        $this->salt     = $salt;
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
        return hash('sha256', 'votix-' . $voter->getEmail() . '-' . $this->salt);
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
        $code = (string) crc32('votix-' . $voter->getEmail() . '-' . $this->salt);

        return substr($code, 0, 4);
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
}
