<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace AppBundle\Controller;

use AppBundle\Entity\Voter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Class VotingController
 * @package AppBundle\Controller
 */
class VotingController extends Controller
{
    /**
     * @Route("/vote/{login}/{receivedToken}", name="vote")
     * @ParamConverter("voter", class="AppBundle:Voter", options={"login" = "login"})
     *
     * @param Voter $voter
     * @param string $receivedToken
     * @param Request $request
     * @return Response
     */
    public function voteAction(Voter $voter, $receivedToken, Request $request)
    {
        $encryptionService = $this->get('votix.encryption');
        $statusService     = $this->get('votix.status');
        $tokenService      = $this->get('votix.token');

        if(!$statusService->isVoteOpen()) {
            $this->get('logger')->notice('Vote link accessed but vote is closed');

            return $this->render('default/bad-timing.html.twig');
        }

        if(!$tokenService->verifyVoterToken($voter, $receivedToken)) {
            throw new AccessDeniedHttpException('Token Invalide');
        }

        if(!$encryptionService->isArmed()) {
            $this->get('logger')->emergency('Votix is not armed');

            throw new AccessDeniedHttpException('Votix n\'est pas encore armé');
        }

        if ($voter->hasVoted()) {
            return $this->render('default/already-voted.html.twig', ['voter' => $voter]);
        }

        if(!$request->request->has('security') or !$request->request->has('choice')) {
            return $this->renderVotingPage($voter);
        }

        $security = $request->request->get('security');
        $choice   = $request->request->get('choice');

        if(!$tokenService->verifyVoterCode($voter, $security)) {
            throw new AccessDeniedHttpException('Le code de sécurité est incorrect');
        }

        $choosenCandidate = $this->get('votix.candidate_repository')->findOneBy(['id' => $choice]);

        if($choosenCandidate === null) {
            $this->get('logger')->warning('Hacking attempt');

            throw new AccessDeniedHttpException('Le candidat n\'existe pas');
        }

        if($choosenCandidate->getEligible() === false) {
            throw new AccessDeniedHttpException('Le candidat n\'est pas éligible');
        }

        $votingService = $this->get('votix.voting');

        $votingService->makeVoterVoteFor($voter, $choosenCandidate);

        return $this->render('default/thank-you.html.twig', ['voter' => $voter]);
    }

    /**
     * @param $voter
     * @return Response
     */
    private function renderVotingPage($voter) {
        $candidateRepository = $this->get('votix.candidate_repository');

        $candidates = $candidateRepository->findAllShuffled();

        return $this->render('default/vote.html.twig', [
            'voter'      => $voter,
            'candidates' => $candidates,
            'token'      => '', //TODO
        ]);
    }

}
