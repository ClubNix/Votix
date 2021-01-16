<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 *
 * @license MIT
 */
namespace App\Controller;

use App\Service\StatsServiceInterface;
use App\Service\StatusServiceInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ScreenApiController
 */
class ScreenApiController extends AbstractController
{
    private StatsServiceInterface $statsService;
    private StatusServiceInterface $statusService;

    public function __construct(
        StatsServiceInterface $statsService,
        StatusServiceInterface $statusService
    ) {
        $this->statsService = $statsService;
        $this->statusService = $statusService;
    }

    /**
     * API json pour les écrans du smig
     *
     * @Route("/live.php", name="live_api")
     * @Cache(smaxage="3", maxage="3", public=true)
     *
     * @return JsonResponse
     */
    public function liveJson(): Response
    {
        $globalStats = $this->statsService->getStats();
        $statsByYear = $this->statsService->getStatsByYear();

        $data = [
            'status'   => $this->translateStatus($this->statusService->getCurrentStatus()),
            'message'  => $this->statusService->getCurrentStatusMessage(),
            'total'    => (int) $globalStats['nb_votants'],
            'ratio'    => $this->truncateFloat($globalStats['ratio_float']),

            'progress_1_label' => 'Ing. 1ère année',
            'progress_1_ratio' => $statsByYear['E1'],
            'progress_2_label' => 'Ing. 2ème année',
            'progress_2_ratio' => $statsByYear['E2'],
            'progress_3_label' => 'Ing. 3ème année',
            'progress_3_ratio' => $statsByYear['E3'],
            'progress_4_label' => 'Ing. 3ème année app.',
            'progress_4_ratio' => $statsByYear['E3A'],
            'progress_5_label' => 'Ing. 4ème année',
            'progress_5_ratio' => $statsByYear['E4'],
            'progress_6_label' => 'Ing. 4ème année app.',
            'progress_6_ratio' => $statsByYear['E4A'],
            'progress_7_label' => 'Ing. 5ème année',
            'progress_7_ratio' => $statsByYear['E5'],
            'progress_8_label' => 'Ing. 5ème année app.',
            'progress_8_ratio' => $statsByYear['E5A'],
            'progress_9_label' => 'Autres',
            'progress_9_ratio' => $statsByYear['AUTRES'],
        ];


        return new JsonResponse($data);
    }

    protected function truncateFloat($number)
    {
        return floor($number * 100) / 100;
    }

    protected function translateStatus(string $status): string
    {
        switch ($status) {
            case StatusServiceInterface::OPEN:
                return 'OUVERT';
            case StatusServiceInterface::CLOSED:
                return 'FERME';
            case StatusServiceInterface::WAITING:
                return 'ATTENTE';
        }

        return '';
    }
}