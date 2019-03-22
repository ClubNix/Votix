<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 *
 * @license MIT
 */
namespace App\Controller;

use App\Service\EncryptionServiceInterface;
use App\Service\StatsService;
use App\Service\StatusService;
use App\Service\VoteCounterServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ProcessController
 */
class ProcessController extends AbstractController
{
    /**
     * @Route("/no/stress", name="no_stress", methods={"GET"})
     *
     * @return Response
     */
    public function voteCountingAction(): Response
    {
        return $this->render('default/no-stress.html.twig');
    }

    /**
     * @Route("/no/stress", name="no_stress_process", methods={"POST"})
     *
     * @param LoggerInterface $logger
     * @param Request $request
     * @param VoteCounterServiceInterface $counterService
     * @param StatsService $statsService
     * @param StatusService $status
     *
     * @return Response
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function voteCountingProcessAction(
        LoggerInterface $logger,
        Request $request,
        VoteCounterServiceInterface $counterService,
        StatsService $statsService,
        StatusService $status
    ): Response {
        // Prevent vote counting if votes are not closed
        if (!$status->isVoteClosed()) {
            $logger->notice('les votes ne sont pas clos');

            return new Response('Les votes ne sont pas encore clos.');
        }

        // Check that required parameters are present
        if (!$request->request->has('key') || !$request->request->has('password')) {
            throw new BadRequestHttpException();
        }

        $privateKey       = $request->request->get('key');
        $receivedPassword = $request->request->get('password');

        if (!$counterService->verifyVoteCountingPassword($receivedPassword)) {
            throw new AccessDeniedHttpException('Le mot de passe ne correspond pas.');
        }

        $results = $counterService->countEncryptedVotes($privateKey);
        $stats   = $statsService->getStats();

        return $this->render('default/results.html.twig', [
            'message' => '',
            'results' => $results,
            'stats'   => $stats,
        ]);
    }

    /**
     * @Route("/arm", name="arm")
     *
     * @param EncryptionServiceInterface $encryptionService
     *
     * @return Response
     */
    public function armAction(EncryptionServiceInterface $encryptionService): Response
    {
        $armed = $encryptionService->isArmed();

        return $this->render('default/arm.html.twig', [
            'armed' => $armed
        ]);
    }

    /**
     * @Route("/arm/download", name="arm_download")
     *
     * @param EncryptionServiceInterface $encryptionService
     * @param LoggerInterface $logger
     *
     * @return BinaryFileResponse
     */
    public function armDownloadAction(EncryptionServiceInterface $encryptionService, LoggerInterface $logger): Response
    {
        if ($encryptionService->isArmed()) {
            $logger->notice('Tentative de régénération de clef');

            throw new AccessDeniedHttpException('Votix est déjà armé, impossible de créer une nouvelle clef.');
        }

        $encryptionService->generateKeys();

        $private_key_file = $encryptionService->getGeneratedKeyFilePath();

        return $this->sendTemporaryFileResponse($private_key_file, 'votix_secret_key.txt');
    }

    /**
     * @Route("/check", name="check", methods={"GET"})
     *
     * @return Response
     */
    public function checkAction(): Response
    {
        return $this->render('default/check.html.twig', ['message' => '']);
    }

    /**
     * @Route("/check", name="check_process", methods={"POST"})
     *
     * @param EncryptionServiceInterface $encryptionService
     * @param Request $request
     *
     * @return Response
     */
    public function checkProcessAction(EncryptionServiceInterface $encryptionService, Request $request): Response
    {
        $key = $request->request->get('key');

        [$success, $message] = $encryptionService->verifyKey($key);

        return $this->render('default/check.html.twig', [
            'success' => $success,
            'message' => $message,
        ]);
    }

    /**
     * @param $file
     * @param $filename
     *
     * @return BinaryFileResponse
     */
    private function sendTemporaryFileResponse(string $file, string $filename): Response
    {
        // dit à PHP d'oublier tout ce qu'il sait sur ce fichier au cas où
        clearstatcache(true, $file);

        $response = new BinaryFileResponse($file);
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Transfer-Encoding', 'Binary');
        $response
            ->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename)
            ->deleteFileAfterSend()
        ;

        return $response;
    }
}