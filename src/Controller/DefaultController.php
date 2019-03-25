<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 *
 * @license MIT
 */
namespace App\Controller;

use App\Service\ArchivesService;
use App\Service\StatsService;
use App\Service\StatusService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class DefaultController
 */
class DefaultController extends AbstractController
{
    private $votixStart;
    private $votixEnd;

    public function __construct($votixStart, $votixEnd)
    {
        $this->votixStart = $votixStart;
        $this->votixEnd = $votixEnd;
    }

    /**
     * @Route("/", name="root")
     * @Route("/{_locale}/index", name="homepage")
     *
     * @param StatsService $statsService
     * @param StatusService $statusService
     *
     * @return Response
     */
    public function index(StatsService $statsService, StatusService $statusService): Response
    {
        $now = time();

        $progress = $statsService->getStatsByPromotion();
        $status   = $statusService->getCurrentStatus();

        return $this->render('default/index.html.twig', [
            'start'        => $this->votixStart,
            'end'          => $this->votixEnd,
            'now'          => $now,
            'all_progress' => $progress,
            'status'       => $status,
        ]);
    }

    /**
     * @Route("/{_locale}/faq", name="faq")
     * @Cache(expires="tomorrow", public=true)
     *
     * @return Response
     */
    public function faq(): Response
    {
        return $this->render('default/faq.html.twig');
    }

    /**
     * @Route("/{_locale}/hall-of-fame", name="hall-of-fame")
     * @Cache(expires="tomorrow", public=true)
     *
     * @param ArchivesService $archivesService
     *
     * @return Response
     */
    public function history(ArchivesService $archivesService): Response
    {
        $data = $archivesService->getArchive();

        return $this->render('default/history.html.twig', [
            'archive' => $data['archives'],
        ]);
    }
}
