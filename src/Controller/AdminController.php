<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace App\Controller;

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
 * Class AdminController
 */
class AdminController extends Controller
{
    /**
     * @Route("/admin/voters-list", name="admin_voters")
     *
     * @return Response
     */
    public function votersListAction()
    {
        $voterRepository = $this->get('votix.voter_repository');

        $voters = $voterRepository->findAllSortedByPromotion();

        return $this->render('default/admin-voters-list.html.twig', [
            'voters' => $voters,
        ]);
    }
}