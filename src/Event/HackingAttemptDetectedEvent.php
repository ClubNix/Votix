<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 *
 * @license MIT
 */
namespace App\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class HackingAttemptDetectedEvent
 */
class HackingAttemptDetectedEvent extends Event
{

    public const NAME = 'hacking_attempt.detected';

    public function __construct()
    {
    }
}
