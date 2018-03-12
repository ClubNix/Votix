<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace App\Service;

use App\Entity\Candidate;
use App\Entity\Voter;
use App\Event\VotesCountedEvent;
use App\Repository\CandidateRepository;
use App\Repository\VoterRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class VoteCounterService
 */
class VoteCounterService implements VoteCounterServiceInterface
{

    /** @var CandidateRepository  */
    private $candidateRepository;

    /** @var VoterRepository  */
    private $voterRepository;

    /** @var EventDispatcherInterface  */
    private $eventDispatcher;

    /** @var EncryptionServiceInterface */
    private $encryption;

    /** @var string */
    private $password;

    /**
     * VoteCounterService constructor.
     * @param CandidateRepository $candidateRepository
     * @param VoterRepository $voterRepository
     * @param EventDispatcherInterface $eventDispatcher
     * @param EncryptionServiceInterface $encryption
     * @param string $password Password required to authorize vote counting.
     */
    public function __construct(
        CandidateRepository        $candidateRepository,
        VoterRepository            $voterRepository,
        EventDispatcherInterface   $eventDispatcher,
        EncryptionServiceInterface $encryption,
        $password)
    {
        $this->candidateRepository = $candidateRepository;
        $this->voterRepository     = $voterRepository;
        $this->eventDispatcher     = $eventDispatcher;
        $this->encryption          = $encryption;
        $this->password            = $password;
    }

    /**
     * Proceed to vote counting.
     *
     * Dispatches an App\Event\VotesCountedEvent after counting.
     *
     * @see App\Event\VotesCountedEvent
     * @see verifyPassword
     *
     * @param $privateKey
     * @return array List of ['candidate' => Candidate, 'count' => int ]
     */
    public function countEncryptedVotes($privateKey)
    {
        /** @var Candidate[] $candidates */
        $candidates = $this->candidateRepository->findAll();
        /** @var Voter[] $voters */
        $voters     = $this->voterRepository->findAll();

        $results = [];
        foreach($candidates as $candidate) {
            $results[$candidate->getId()] = [
                'candidate' => $candidate,
                'count'     => 0,
            ];
        }

        foreach($voters as $voter) {
            if(!$voter->hasVoted()) continue;

            $id = $this->encryption->decryptVote($voter->getBallot(), $privateKey);

            $results[$id]['count']++;
        }

        $this->eventDispatcher->dispatch(VotesCountedEvent::NAME, new VotesCountedEvent($results));

        return $results;
    }

    /**
     * Verify if the password provided is equals to the password required to do the vote count.
     *
     * Uses hash_equals to prevent timing attacks.
     *
     * @param $password
     * @return bool
     */
    public function verifyPassword($password)
    {
        return hash_equals($this->password, $password);
    }
}