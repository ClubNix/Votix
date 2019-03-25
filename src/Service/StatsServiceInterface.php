<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 *
 * @license MIT
 */
namespace App\Service;

/**
 * Interface StatsServiceInterface
 */
interface StatsServiceInterface
{
    public function getStats();

    public function getStatsByPromotion();

    public function getStatsByYear();
}