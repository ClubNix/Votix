<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace App\Tests\Unit\Service;

use App\Entity\Voter;
use App\Service\EmailBuilderService;
use App\Service\TokenServiceInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Twig\Environment;

/**
 * Class EmailBuilderServiceTest
 */
class EmailBuilderServiceTest extends WebTestCase
{
    public function testGetTemplatedEmail(): void
    {
        $tokenService = $this->getTokenServiceMock();
        $templating = $this->getTemplating();
        $voter = $this->getVoter();

        $mailBuilderService = new EmailBuilderService($tokenService, $templating, 'FromName <from@domain.tld>', 'replyTo@domain.tld', 'returnPath@domain.tld');

        $email = $mailBuilderService->getTemplatedEmail($voter, 'mocked_template');
        $this->assertSame('FromName', $email->getFrom()[0]->getName());
        $this->assertSame('from@domain.tld', $email->getFrom()[0]->getAddress());
        $this->assertSame('replyTo@domain.tld', $email->getReplyTo()[0]->getAddress());
        $this->assertSame('returnPath@domain.tld', $email->getReturnPath()->getAddress());
        $this->assertStringContainsString('test_content', $email->getHtmlBody());

    }

    private function getTokenServiceMock(): TokenServiceInterface
    {
        $tokenService = $this
            ->getMockBuilder(TokenServiceInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $tokenService
            ->method('getLinkForVoter')
            ->willReturn('http://votelink');

        $tokenService
            ->method('getCodeForVoter')
            ->willReturn('4242');

        return $tokenService;
    }

    private function getTemplating()
    {
        $tokenService = $this
            ->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $tokenService
            ->method('render')
            ->willReturn('test_content');

        return $tokenService;
    }

    private function getVoter(): Voter
    {
        $voter = new Voter();
        $voter->setFirstname('Firstname');
        $voter->setLastname('Lastname');
        $voter->setLogin('voter');
        $voter->setEmail('voter@example.com');

        return $voter;
    }
}