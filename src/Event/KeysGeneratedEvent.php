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
 * Class KeysGeneratedEvent
 */
class KeysGeneratedEvent extends Event
{

    public const NAME = 'keys.generated';

    public function __construct()
    {
    }
}
