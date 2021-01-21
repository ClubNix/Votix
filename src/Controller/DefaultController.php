<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 *
 * @license MIT
 */
namespace App\Controller;

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

    public function __construct(int $votixStart, int $votixEnd)
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
        $status = $statusService->getCurrentStatus();

        if ($statusService->isVoteUnconfigured()) {
            return $this->redirectToRoute('configure');
        }

        $now = time();

        $progress = $statsService->getStatsByPromotion();

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
     * @Route("/{_locale}/configure", name="configure")
     *
     * @param string $secret
     * @param int $votixStart
     * @param string $linkBase
     * @param string $from
     *
     * @return Response
     */
    public function configured(string $secret, int $votixStart, string $linkBase, string $from): Response
    {
        return $this->render('default/configure.html.twig', [
            'secrets_configured' => $secret !== 'ThisTokenIsNotSoSecretChangeItInDotEnv',
            'link_base_configured' => $linkBase !== 'https://subdomain.example.com/vote/',
            'time_window_configured' => $votixStart !== 0,
            'mailer_configured' => $from !== 'votix@domain.tld',
        ]);
    }
}
