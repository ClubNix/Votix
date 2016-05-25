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
 * Class KeysGeneratedEvent
 * @package AppBundle\Event
 */
class KeysGeneratedEvent extends Event
{

    const NAME = 'keys.generated';

    public function __construct()
    {
    }
}
