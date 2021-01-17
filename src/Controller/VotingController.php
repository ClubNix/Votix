<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 *
 * @license MIT
 */
namespace App\Controller;

use App\Entity\Candidate;
use App\Entity\Voter;
use App\Repository\CandidateRepository;
use App\Service\EncryptionServiceInterface;
use App\Service\StatusService;
use App\Service\TokenServiceInterface;
use App\Service\VotingService;
use Doctrine\ORM\ORMException;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class VotingController
 */
class VotingController extends AbstractController
{
    private $candidateRepository;

    public function __construct(CandidateRepository $candidateRepository)
    {
        $this->candidateRepository = $candidateRepository;
    }

    /**
     * @Route("/vote/{login}/{receivedToken}", name="vote")
     * @ParamConverter("voter", class="App:Voter", options={"mapping": {"login" = "login"}})
     *
     * @param Voter $voter
     * @param string $receivedToken
     * @param LoggerInterface $logger
     * @param Request $request
     * @param EncryptionServiceInterface $encryptionService
     * @param StatusService $statusService
     * @param TokenServiceInterface $tokenService
     * @param VotingService $votingService
     *
     * @return Response
     *
     * @throws ORMException
     */
    public function vote(
        Voter $voter,
        string $receivedToken,
        LoggerInterface $logger,
        Request $request,
        EncryptionServiceInterface $encryptionService,
        StatusService $statusService,
        TokenServiceInterface $tokenService,
        VotingService $votingService
    ): Response {
        if (!$statusService->isVoteOpen()) {
            $logger->notice('Vote link accessed but vote is closed');

            return $this->render('default/bad-timing.html.twig');
        }

        if (!$tokenService->verifyVoterToken($voter, $receivedToken)) {
            throw new AccessDeniedHttpException('Token Invalide');
        }

        if (!$encryptionService->isArmed()) {
            $logger->emergency('Votix is not armed');

            throw new AccessDeniedHttpException('Votix n\'est pas encore armé');
        }

        if ($voter->hasVoted()) {
            return $this->render('default/already-voted.html.twig', ['voter' => $voter]);
        }

        if (!$request->request->has('security') || !$request->request->has('choice')) {
            return $this->renderVotingPage($voter);
        }

        $security = $request->request->get('security');

        if (!$tokenService->verifyVoterCode($voter, $security)) {
            throw new AccessDeniedHttpException('Le code de sécurité est incorrect');
        }

        $choice = $request->request->get('choice');

        /** @var Candidate|null $chosenCandidate */
        $chosenCandidate = $this->candidateRepository->findOneBy(['id' => $choice]);

        if ($chosenCandidate === null) {
            $logger->warning('Hacking attempt');

            throw new AccessDeniedHttpException('Le candidat n\'existe pas');
        }

        if ($chosenCandidate->getEligible() === false) {
            throw new AccessDeniedHttpException('Le candidat n\'est pas éligible');
        }

        $votingService->makeVoterVoteFor($voter, $chosenCandidate);

        return $this->render('default/thank-you.html.twig', ['voter' => $voter]);
    }

    /**
     * @param Voter $voter
     *
     * @return Response
     */
    private function renderVotingPage(Voter $voter): Response
    {
        $candidates = $this->candidateRepository->findAllShuffled();

        return $this->render('default/vote.html.twig', [
            'voter'      => $voter,
            'candidates' => $candidates,
            'token'      => '', //TODO
        ]);
    }

}
