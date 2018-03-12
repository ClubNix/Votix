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
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class ScreenApiController
 */
class ScreenApiController extends Controller
{

    /**
     * API json pour les écrans du smig
     *
     * @Route("/live.php", name="live_api")
     * @Cache(smaxage="3", maxage="3", public=true)
     *
     * @return JsonResponse
     */
    public function liveJsonAction()
    {
        $globalStats = $this->get('votix.stats')->getStats();
        $statsPromos = $this->get('votix.stats')->getStatsByPromotion();

        $perPromo = [];
        foreach($statsPromos as $key => $value) {
            $perPromo[$value['promotion']] = $value;
        }

        $E1  = ['15_E1'];
        $E2  = ['15_E2A', '15_E2B'];
        $E3  = ['15_E3E', '15_E3EP', '15_E3S', '15_E3T'];
        $E3A = ['15_E3FE', '15_E3FR', '15_E3FI'];
        $E4  = [
            '15_E4', '15_E4_DMC', '15_E4_DRIO', '15_E4_ELE', '15_E4_ENE', '15_E4_ENE',
            '15_E4_ENE', '15_E4_GI', '15_E4_IMC', '15_E4_IME', '15_E4_INF', '15_E4_PIM', '15_E4_SE', '15_E4_SI'
        ];
        $E4A = ['15_E4FE', '15_E4FI', '15_E4FR'];
        $E5  = [
            '15_E5', '15_E5_BIO', '15_E5_ELE', '15_E5_ENE', '15_E5_GI', '15_E5_IMC',
            '15_E5_IME', '15_E5_INF', '15_E5_SE', '15_E5_SI', '15_E5_TEL'
        ];
        $E5A = ['15_E5FE', '15_E5FI', '15_E5FR'];
        $AUTRES  = ['15_MOTIS'];

        $data = [
            'status'   => $this->get('votix.status')->getCurrentStatus(),
            'message'  => $this->get('votix.status')->getCurrentStatusMessage(),
            'total'    => (int) $globalStats['nb_votants'],
            'ratio'    => $this->truncatedFloat($globalStats['ratio_float']),

            'progress_1_label' => 'Ing. 1ère année',
            'progress_1_ratio' => $this->getGroupRatio($E1, $perPromo),
            'progress_2_label' => 'Ing. 2ème année',
            'progress_2_ratio' => $this->getGroupRatio($E2, $perPromo),
            'progress_3_label' => 'Ing. 3ème année',
            'progress_3_ratio' => $this->getGroupRatio($E3, $perPromo),
            'progress_4_label' => 'Ing. 3ème année app.',
            'progress_4_ratio' => $this->getGroupRatio($E3A, $perPromo),
            'progress_5_label' => 'Ing. 4ème année',
            'progress_5_ratio' => $this->getGroupRatio($E4, $perPromo),
            'progress_6_label' => 'Ing. 4ème année app.',
            'progress_6_ratio' => $this->getGroupRatio($E4A, $perPromo),
            'progress_7_label' => 'Ing. 5ème année',
            'progress_7_ratio' => $this->getGroupRatio($E5, $perPromo),
            'progress_8_label' => 'Ing. 5ème année app.',
            'progress_8_ratio' => $this->getGroupRatio($E5A, $perPromo),
            'progress_9_label' => 'Autres',
            'progress_9_ratio' => $this->getGroupRatio($AUTRES, $perPromo)
        ];


        return new JsonResponse($data);
    }

    private function getGroupRatio($promotions, $stats)
    {

        $total_votants = 0;
        $total_invites = 0;

        foreach($promotions as $promotion) {
            if(!array_key_exists($promotion, $stats)) {
                continue;
            }

            $total_votants += $stats[$promotion]['nb_votants'];
            $total_invites += $stats[$promotion]['nb_invites'];
        }

        if($total_invites == 0) {
            return 0;
        } else {
            return round( ($total_votants * 100) / $total_invites);
        }
    }

    protected function truncatedFloat($number)
    {
        return floor($number * 100) / 100;
    }
}