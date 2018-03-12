<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace App\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class KeysGeneratedEvent
 */
class KeysGeneratedEvent extends Event
{

    const NAME = 'keys.generated';

    public function __construct()
    {
    }
}
