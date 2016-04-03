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
use AppBundle\Event\VoteCast;
use AppBundle\Repository\VoterRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class VotingService
 * @package AppBundle\Service
 */
class VotingService implements VotingServiceInterface {
    /** @var EncryptionServiceInterface */
    private $encryptionService;

    /** @var VoterRepository  */
    private $voterRepository;

    /** @var EventDispatcherInterface */
    private $dispatcher;

    /**
     * VotingService constructor.
     * @param EncryptionServiceInterface $encryptionService
     * @param VoterRepository $voterRepository
     */
    public function __construct(
        EventDispatcherInterface   $dispatcher,
        EncryptionServiceInterface $encryptionService,
        VoterRepository            $voterRepository
    ) {
        $this->dispatcher        = $dispatcher;
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
        $candidateId = (string) $choosenCandidate->getId();

        $ballot = $this->encryptionService->encryptVote($candidateId);

        $voter->setBallot($ballot);

        $signature = $this->signBallot($candidateId, $ballot);

        $voter->setSignature($signature['encrypted']);

        $this->dispatcher->dispatch(VoteCast::NAME, new VoteCast($voter, $signature['private_key']));

        $this->voterRepository->save($voter);
    }

    private function signBallot($plaintext, $ballot) {
        $hash = hash('sha512', $ballot);

        $payload = [
            'plaintext'   => $plaintext,
            'ballot_hash' => $hash,
        ];

        $signature = json_encode($payload);

        return $this->encryptionService->encryptSignature($signature);
    }
}
