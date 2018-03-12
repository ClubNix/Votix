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
 * Class VoteVoidedEvent
 */
class VoteVoidedEvent extends Event
{
    const NAME = 'vote.voided';

    public function __construct()
    {

    }
}
