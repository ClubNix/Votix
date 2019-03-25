<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 *
 * @license MIT
 */
namespace App\Service;

use App\Repository\VoterRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

/**
 * Class StatsService
 */
class StatsService implements StatsServiceInterface
{
    private $voterRepository;

    public function __construct(VoterRepository $voterRepository)
    {
        $this->voterRepository = $voterRepository;
    }

    /**
     * @return mixed
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getStats()
    {
        return $this->voterRepository->getStats();
    }

    public function getStatsByPromotion()
    {
        return $this->voterRepository->getStatsByPromotion();
    }

    public function getStatsByYear()
    {
        $statsByPromotion = $this->voterRepository->getStatsByPromotion();

        $perYear = [];
        foreach ($statsByPromotion as $key => $value) {
            $year = $this->findYear($value['promotion']);
            $perYear[$year][$value['promotion']] = $value;
        }

        return [
            'E1' => $this->getGroupRatio($perYear['E1']),
            'E2' => $this->getGroupRatio($perYear['E2']),
            'E3' => $this->getGroupRatio($perYear['E3']),
            'E3A' => $this->getGroupRatio($perYear['E3A']),
            'E4' => $this->getGroupRatio($perYear['E4']),
            'E4A' => $this->getGroupRatio($perYear['E4A']),
            'E5' => $this->getGroupRatio($perYear['E5']),
            'E5A' => $this->getGroupRatio($perYear['E5A']),
            'AUTRES' => $this->getGroupRatio($perYear['AUTRES']),
        ];
    }

    private function findYear(string $promotion): string
    {
        if (preg_match('/^.._E1.*$/', $promotion)) {
            return 'E1';
        }

        if (preg_match('/^.._E2.*$/', $promotion)) {
            return 'E2';
        }

        if (preg_match('/^.._E3$|.._E3[^F]+.*$/', $promotion)) {
            return 'E3';
        }

        if (preg_match('/^.._E3F.*$/', $promotion)) {
            return 'E3A';
        }

        if (preg_match('/^.._E4$|.._E4[^F]+.*$/', $promotion)) {
            return 'E4';
        }

        if (preg_match('/^.._E4F.*$/', $promotion)) {
            return 'E4A';
        }

        if (preg_match('/^.._E5$|.._E5[^F]+.*$/', $promotion)) {
            return 'E5';
        }

        if (preg_match('/^.._E5F.*$/', $promotion)) {
            return 'E5A';
        }

        return 'AUTRES';
    }

    private function getGroupRatio(array $promotions)
    {
        $total_votants = 0;
        $total_invites = 0;

        foreach ($promotions as $promotion) {

            $total_votants += $promotion['nb_votants'];
            $total_invites += $promotion['nb_invites'];
        }

        // prevents division per 0
        if ($total_invites === 0) {
            return 0;
        }

        return round( ($total_votants * 100) / $total_invites);
    }
}
