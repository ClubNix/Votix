<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace AppBundle\Service;

/**
 * Interface StatsServiceInterface
 * @package AppBundle\Service
 */
interface StatsServiceInterface
{
    public function getStats();

    public function getStatsByPromotion();
}