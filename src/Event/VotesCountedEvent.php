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
 * Class VotesCountedEvent
 */
class VotesCountedEvent extends Event
{
    public const NAME = 'votes.counted';

    private $results;

    public function __construct($results)
    {
    }
}
