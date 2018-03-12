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
 * Class VotesCountedEvent
 */
class VotesCountedEvent extends Event
{

    const NAME = 'votes.counted';

    private $results;

    public function __construct($results)
    {
        $this->results = $results;
    }
}
