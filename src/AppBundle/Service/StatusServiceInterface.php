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
 * Interface StatusServiceInterface
 * @package AppBundle\Service
 */
interface StatusServiceInterface {
    const OPEN    = 'OPEN';
    const CLOSED  = 'CLOSED';
    const WAITING = 'WAITING';

    public function getCurrentStatus();
    public function getCurrentStatusMessage();
}