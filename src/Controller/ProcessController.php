<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace App\Controller;

use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class ProcessController
 */
class ProcessController extends Controller
{
    /**
     * @Route("/no/stress", name="no_stress")
     * @Method({"GET"})
     *
     * @return Response
     */
    public function voteCountingAction()
    {
        return $this->render('default/no-stress.html.twig');
    }

    /**
     * @Route("/no/stress", name="no_stress_process")
     * @Method({"POST"})
     *
     * @param LoggerInterface $logger
     * @param Request $request
     * @return Response
     */
    public function voteCountingProcessAction(LoggerInterface $logger, Request $request)
    {
        $status = $this->get('votix.status');

        // Prevent vote counting if votes are not closed
        if(!$status->isVoteClosed()) {
            $logger->notice('les votes ne sont pas clos');

            return new Response('Les votes ne sont pas encore clos.');
        }

        // Check that required parameters are present
        if(!$request->request->has('key') or !$request->request->has('password')) {
            throw new BadRequestHttpException();
        }

        $privateKey       = $request->request->get('key');
        $receivedPassword = $request->request->get('password');

        $counterService   = $this->get('votix.counter');
        $statsService     = $this->get('votix.stats');

        if(!$counterService->verifyPassword($receivedPassword)) {
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
     * @return Response
     */
    public function armAction()
    {
        $armed = $this->get('votix.encryption')->isArmed();

        return $this->render('default/arm.html.twig', [
            'armed' => $armed
        ]);
    }

    /**
     * @Route("/arm/download", name="arm_download")
     *
     * @return BinaryFileResponse
     */
    public function armDownloadAction(LoggerInterface $logger)
    {
        $encryption = $this->get('votix.encryption');

        if($encryption->isArmed()) {
            $logger->notice('Tentative de régénération de clef');

            throw new AccessDeniedHttpException('Votix est déjà armé, impossible de créer une nouvelle clef.');
        }

        $encryption->generateKeys();

        $private_key_file = $encryption->getGeneratedKeyFilePath();

        return $this->sendTemporaryFileResponse($private_key_file, 'votix_secret_key.txt');
    }

    /**
     * @Route("/check", name="check")
     * @Method({"GET"})
     *
     * @return Response
     */
    public function checkAction()
    {
        return $this->render('default/check.html.twig', ['message' => '']);
    }

    /**
     * @Route("/check", name="check_process")
     * @Method({"POST"})
     *
     * @param Request $request
     * @return Response
     */
    public function checkProcessAction(Request $request)
    {
        $key = $request->request->get('key');

        list($success, $message) = $this->get('votix.encryption')->verifyKey($key);

        return $this->render('default/check.html.twig', [
            'success' => $success,
            'message' => $message,
        ]);
    }

    /**
     * @param $file
     * @param $filename
     * @return BinaryFileResponse
     */
    private function sendTemporaryFileResponse($file, $filename)
    {
        // dit à PHP d'oublier tout ce qu'il sait sur ce fichier au cas où
        clearstatcache(true, $file);

        $response = new BinaryFileResponse($file);
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Transfer-Encoding', 'Binary');
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename
        );
        $response->deleteFileAfterSend(true);

        return $response;
    }
}