<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace AppBundle\Service;

use AppBundle\Entity\Voter;

/**
 * Class TokenService
 * @package AppBundle\Service
 */
class TokenService implements TokenServiceInterface {
    private $salt;
    private $linkBase;

    /**
     * TokenService constructor.
     * @param $salt
     * @param $linkBase
     */
    public function __construct($salt, $linkBase)
    {
        $this->salt     = $salt;
        $this->linkBase = $linkBase;
    }

    /**
     * Verify if the token provided is matching the one from the voter.
     * Uses hash_equals to prevent timing attacks.
     *
     * @param Voter $voter
     * @param string $token
     * @return bool true if the token is matching else false
     */
    public function verifyVoterToken($voter, $token) {
        $expectedToken = $this->getTokenForVoter($voter);

        return hash_equals($expectedToken, $token);
    }

    /**
     * Verify if the code provided is matching the one from the voter.
     * Uses hash_equals to prevent timing attacks.
     *
     * @param Voter $voter
     * @param string $code
     * @return bool true if the code is matching else false
     */
    public function verifyVoterCode($voter, $code) {
        $expectedCode = $this->getCodeForVoter($voter);

        return hash_equals($expectedCode, $code);
    }

    /**
     * Get the token for a voter.
     *
     * @param Voter $voter
     * @return string
     */
    public function getTokenForVoter($voter) {
        return hash('sha256', 'votix-' . $voter->getEmail() . '-' . $this->salt);
    }

    /**
     * Get the code for a voter.
     *
     * @param Voter $voter
     * @return string Number composed of 4 digits.
     */
    public function getCodeForVoter($voter) {
        $code = strval(crc32('votix-' . $voter->getEmail() . '-' . $this->salt));
        return substr($code, 0, 4);
    }

    /**
     * Get the secret voting link for a voter.
     *
     * @param Voter $voter
     * @return string
     */
    public function getLinkForVoter($voter) {
        $token = $this->getTokenForVoter($voter);
        return $this->linkBase . $voter->getLogin() . '/' . $token;
    }
}