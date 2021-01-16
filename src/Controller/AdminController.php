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
use App\Service\VoteCounterServiceInterface;
use App\Repository\CandidateRepository;
use App\Repository\VoterRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AdminController
 */
class AdminController extends AbstractController
{
    private $secret;

    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    /**
     * @Route("/admin/voters-list", name="admin_voters")
     *
     * @param VoterRepository $voterRepository
     *
     * @return Response
     */
    public function votersList(VoterRepository $voterRepository): Response
    {
        $voters = $voterRepository->findAllSortedByPromotion();

        return $this->render('default/admin-voters-list.html.twig', [
            'voters' => $voters,
        ]);
    }

    /**
     * @Route("/admin/verify-results-hash", name="admin_verify_results_hash")
     *
     * @param Request $request
     * @param VoteCounterServiceInterface $counterService
     * @param CandidateRepository $candidateRepository
     *
     * @return Response
     */
    public function verifyResultsHash(Request $request, VoteCounterServiceInterface $counterService, CandidateRepository $candidateRepository): Response
    {
        $isChecksumValid = null;

        if ($request->request->has('checksum')) {
            /** @var Candidate[] $candidates */
            $candidates = $candidateRepository->findAll();
            $candidatesIndexed = [];
            foreach ($candidates as $candidate) {
                $candidatesIndexed[$candidate->getId()] = $candidate;
            }

            $results = [];
            foreach ($request->request as $key => $value) {
                if (substr($key, 0, 6 ) !== "count-") {
                   continue;
                }

                $key = explode('-', $key)[1];

                if (!array_key_exists($key, $candidatesIndexed)) {
                    throw new BadRequestHttpException();
                }

                $results[$key] = [
                    'candidate' => $candidatesIndexed[$key],
                    'count' => $value,
                ];
            }

            $hash = $counterService->hashResults($results, $this->secret);

            $isChecksumValid = hash_equals($hash, $request->request->get('checksum'));
        }

        $candidates = $candidateRepository->findAllShuffled();

        return $this->render('default/admin-verify-results-hash.html.twig', [
            'candidates' => $candidates,
            'isChecksumValid' => $isChecksumValid,
        ]);
    }
}
