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
 * Class VoteVoidedEvent
 */
class VoteVoidedEvent extends Event
{
    public const NAME = 'vote.voided';

    public function __construct()
    {

    }
}
