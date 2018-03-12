<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DefaultController
 */
class DefaultController extends Controller
{
    /**
     * @Route("/", name="root")
     * @Route("/{_locale}/index", name="homepage")
     *
     * @return Response
     */
    public function indexAction()
    {
        $start = $this->getParameter('votix_start');
        $end   = $this->getParameter('votix_end');
        $now   = time();

        $progress = $this->get('votix.stats')->getStatsByPromotion();
        $status   = $this->get('votix.status')->getCurrentStatus();

        return $this->render('default/index.html.twig', [
            'start'        => $start,
            'end'          => $end,
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
    public function faqAction()
    {
        return $this->render('default/faq.html.twig');
    }

    /**
     * @Route("/{_locale}/hall-of-fame", name="hall-of-fame")
     * @Cache(expires="tomorrow", public=true)
     *
     * @return Response
     */
    public function historyAction()
    {
        $data = $this->get('votix.archives')->getArchive();

        return $this->render('default/history.html.twig', [
            'archive' => $data['archives'],
        ]);
    }
}
