<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 *
 * @license MIT
 */
namespace App\Service;

use App\Entity\Candidate;
use App\Entity\Voter;
use App\Event\VoteCastEvent;
use App\Repository\VoterRepository;
use Doctrine\ORM\ORMException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Class VotingService
 */
class VotingService implements VotingServiceInterface
{
    /**
     * @var EncryptionServiceInterface
     */
    private $encryptionService;

    /**
     * @var VoterRepository
     */
    private $voterRepository;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * VotingService constructor.
     *
     * @param EventDispatcherInterface $dispatcher
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
     * @param Candidate $chosenCandidate
     *
     * @throws ORMException
     */
    public function makeVoterVoteFor(Voter $voter, Candidate $chosenCandidate): void
    {
        $candidateId = (string) $chosenCandidate->getId();

        $ballot = $this->encryptionService->encryptVote($candidateId);
        $voter->setBallot($ballot);

        $signature = $this->signBallot($candidateId, $ballot);
        $voter->setSignature($signature['encrypted']);

        $this->voterRepository->save($voter);

        $this->dispatcher->dispatch(new VoteCastEvent($voter, $signature['private_key']), VoteCastEvent::NAME);
    }

    private function signBallot(string $plaintext, string $ballot): array
    {
        $hash = hash('sha512', $ballot);

        $payload = [
            'plaintext'   => $plaintext,
            'ballot_hash' => $hash,
        ];

        $signature = json_encode($payload);

        return $this->encryptionService->encryptSignature($signature);
    }
}
