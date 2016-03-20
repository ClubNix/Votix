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
 * Class KeysVerifiedEvent
 * @package AppBundle\Event
 */
class KeysVerifiedEvent extends Event {
    const NAME = 'keys.verified';

    private $message;

    public function __construct($success, $message)
    {
        $this->message;
    }
}
