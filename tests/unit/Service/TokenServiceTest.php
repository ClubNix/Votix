<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace App\Tests\Unit\Service;

use App\Entity\Voter;
use App\Service\TokenService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class TokenServiceTest
 */
class TokenServiceTest extends WebTestCase
{
    private const REGEX_FOUR_DIGITS = '/^[0-9]{4}$/';
    private const REGEX_BASE64URL = '/^[-_0-9a-zA-Z]+$/';

    public function testGetCodeForVoter(): string
    {
        $tokenService = $this->getTokenService();
        $voter = $this->getVoter();

        $code = $tokenService->getCodeForVoter($voter);

        $this->assertTrue(strlen($code) === 4);
        $this->assertMatchesRegularExpression(self::REGEX_FOUR_DIGITS, $code);

        return $code;
    }

    public function testGetTokenForVoter(): string
    {
        $tokenService = $this->getTokenService();
        $voter = $this->getVoter();

        $token = $tokenService->getTokenForVoter($voter);

        $this->assertMatchesRegularExpression(self::REGEX_BASE64URL, $token);

        return $token;
    }

    /**
     * @depends testGetTokenForVoter
     *
     * @param string $token
     */
    public function testGetLinkForVoter(string $token): void
    {
        $tokenService = $this->getTokenService();
        $voter = $this->getVoter();

        $link = $tokenService->getLinkForVoter($voter);

        $this->assertStringContainsString('https://example.com/vote/', $link);
        $this->assertStringContainsString($token, $link);
    }

    /**
     * @depends testGetCodeForVoter
     *
     * @param string $code
     */
    public function testVerifyVoterCode(string $code): void
    {
        $tokenService = $this->getTokenService();
        $voter = $this->getVoter();

        // hopefully it's not the right one
        $wrongCode = '0000';

        $this->assertTrue($tokenService->verifyVoterCode($voter, $code));
        $this->assertFalse($tokenService->verifyVoterCode($voter, $wrongCode));
    }

    /**
     * @depends testGetTokenForVoter
     *
     * @param string $token
     */
    public function testVerifyVoterToken(string $token): void
    {
        $tokenService = $this->getTokenService();
        $voter = $this->getVoter();

        $wrongToken = $token;
        $wrongToken[0] = 'x';

        $this->assertTrue($tokenService->verifyVoterToken($voter, $token));
        $this->assertFalse($tokenService->verifyVoterCode($voter, $wrongToken));
    }

    private function getVoter(): Voter
    {
        $voter = new Voter();
        $voter->setLogin('voter');
        $voter->setEmail('voter@example.com');

        return $voter;
    }

    private function getTokenService(): TokenService
    {
        return new TokenService("pepper", "https://example.com/vote/");
    }
}