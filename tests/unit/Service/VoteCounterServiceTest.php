<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace App\Tests\Unit\Service;

use App\Entity\Candidate;
use App\Entity\Voter;
use App\Repository\CandidateRepository;
use App\Repository\VoterRepository;
use App\Service\EncryptionServiceInterface;
use App\Service\VoteCounterService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Class VoteCounterServiceTest
 * @package Tests\AppBundle\Entity
 */
class VoteCounterServiceTest extends WebTestCase
{
    public function testCountEncryptedVotes()
    {
        $voteCounterService = $this->getVoteCounterService();
        $result = $voteCounterService->countEncryptedVotes("mockprivatekey");

        $this->assertCount(3, $result);
        $this->assertSame(1, $result[1]['candidate']->getId());
        $this->assertSame(2, $result[2]['candidate']->getId());
        $this->assertSame(3, $result[3]['candidate']->getId());
        $this->assertTrue($result[1]['count'] === 0);
        $this->assertTrue($result[2]['count'] === 2);
        $this->assertTrue($result[3]['count'] === 0);

    }
    public function testVoteCountingIsDeterministic()
    {
        $voteCounterService = $this->getVoteCounterService();

        [$candidate1, $candidate2, $candidate3] = $candidates = $this->getCandidatesMock();

        $fixtureResult1 = [
            ['candidate' => $candidate1, 'count' => 1],
            ['candidate' => $candidate2, 'count' => 2],
            ['candidate' => $candidate3, 'count' => 3],
        ];
        $fixtureResult1Shuffled = [
            ['candidate' => $candidate2, 'count' => 2],
            ['candidate' => $candidate1, 'count' => 1],
            ['candidate' => $candidate3, 'count' => 3],
        ];
        $fixtureResult2 = [
            ['candidate' => $candidate1, 'count' => 3],
            ['candidate' => $candidate2, 'count' => 2],
            ['candidate' => $candidate3, 'count' => 1],
        ];

        $hash1 = $voteCounterService->hashResults($fixtureResult1, 'secret');
        $hash1Shuffled = $voteCounterService->hashResults($fixtureResult1Shuffled, 'secret');
        $hash2 = $voteCounterService->hashResults($fixtureResult2, 'secret');

        $this->assertSame($hash1, $hash1Shuffled);
        $this->assertNotSame($hash1, $hash2);
    }

    public function testVerifyVoteCountingPassword()
    {
        $voteCounterService = $this->getVoteCounterService();
        $this->assertTrue($voteCounterService->verifyVoteCountingPassword("password"));
        $this->assertFalse($voteCounterService->verifyVoteCountingPassword("notPassword"));
    }

    private function getVoteCounterService(): VoteCounterService
    {
        $candidateRepository = $this->getCandidateRepositoryMock();
        $voterRepository = $this->getVoterRepositoryMock();
        $eventDispatcher = $this->getEventDispatcherMock();
        $encryptionService = $this->getEncryptionServiceMock();
        $password = 'password';

        return new VoteCounterService($candidateRepository, $voterRepository, $eventDispatcher, $encryptionService, $password);
    }

    private function getCandidatesMock(): array
    {
        $candidate1 = new Candidate();
        $candidate1->setId(1);
        $candidate1->setName("ONE");
        $candidate2 = new Candidate();
        $candidate2->setId(2);
        $candidate2->setName("TWO");
        $candidate3 = new Candidate();
        $candidate3->setId(3);
        $candidate3->setName("THREE");

        return [$candidate1, $candidate2, $candidate3];
    }

    private function getCandidateRepositoryMock()
    {
        $candidateRepository = $this
            ->getMockBuilder(CandidateRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $candidates = $this->getCandidatesMock();

        $candidateRepository
            ->method('findAll')
            ->willReturn($candidates);

        return $candidateRepository;
    }

    private function getVoterRepositoryMock()
    {
        $voterRepository = $this
            ->getMockBuilder(VoterRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $voter1 = new Voter();
        $voter2 = new Voter();
        $voter2->setBallot('ENCRYPTED2');
        $voter3 = new Voter();
        $voter3->setBallot('ENCRYPTED2');

        $voters = [$voter1, $voter2, $voter3];

        $voterRepository
            ->method('findAll')
            ->willReturn($voters);

        return $voterRepository;
    }

    private function getEventDispatcherMock()
    {
        return $this
            ->getMockBuilder(EventDispatcherInterface::class)
            ->getMock();
    }

    private function getEncryptionServiceMock()
    {
        $encryptionService = $this
            ->getMockBuilder(EncryptionServiceInterface::class)
            ->getMock();

        $encryptionService->method('decryptVote')
            ->willReturn("2");

        return $encryptionService;
    }
}
