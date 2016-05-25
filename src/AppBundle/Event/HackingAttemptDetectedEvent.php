<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace AppBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class HackingAttemptDetectedEvent
 * @package AppBundle\Event
 */
class HackingAttemptDetectedEvent extends Event
{

    const NAME = 'hacking_attempt.detected';

    public function __construct()
    {
    }
}
