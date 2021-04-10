<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 *
 * @license MIT
 */
namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class KeysVerifiedEvent
 */
class KeysVerifiedEvent extends Event
{
    public const NAME = 'keys.verified';

    public function __construct($success, $message)
    {
    }
}
