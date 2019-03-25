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
 * Interface StatusServiceInterface
 */
interface StatusServiceInterface
{
    public const OPEN    = 'OPEN';
    public const CLOSED  = 'CLOSED';
    public const WAITING = 'WAITING';

    public function getCurrentStatus(): string;

    public function getCurrentStatusMessage(): string;
}