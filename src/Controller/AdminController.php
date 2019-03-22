<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 *
 * @license MIT
 */
namespace App\Controller;

use App\Repository\VoterRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AdminController
 */
class AdminController extends AbstractController
{
    /**
     * @Route("/admin/voters-list", name="admin_voters")
     *
     * @param VoterRepository $voterRepository
     *
     * @return Response
     */
    public function votersListAction(VoterRepository $voterRepository): Response
    {
        $voters = $voterRepository->findAllSortedByPromotion();

        return $this->render('default/admin-voters-list.html.twig', [
            'voters' => $voters,
        ]);
    }
}