<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace AppBundle\Service;


use AppBundle\Entity\Candidate;
use AppBundle\Entity\Voter;
use AppBundle\Repository\VoterRepository;

/**
 * Class VotingService
 * @package AppBundle\Service
 */
class VotingService implements VotingServiceInterface {
    /** @var EncryptionServiceInterface */
    private $encryptionService;

    /** @var VoterRepository  */
    private $voterRepository;

    /**
     * VotingService constructor.
     * @param EncryptionServiceInterface $encryptionService
     * @param VoterRepository $voterRepository
     */
    public function __construct(
        EncryptionServiceInterface $encryptionService,
        VoterRepository            $voterRepository
    ) {
        $this->encryptionService = $encryptionService;
        $this->voterRepository   = $voterRepository;
    }

    /**
     * Make a Voter vote for a Candidate.
     *
     * @param Voter $voter
     * @param Candidate $choosenCandidate
     */
    public function makeVoterVoteFor(Voter $voter, Candidate $choosenCandidate) {
        $candidateId = $choosenCandidate->getId();

        $ballot = $this->encryptionService->encryptVote($candidateId);

        $voter->setBallot($ballot);

        $this->voterRepository->save($voter);
    }
}
