<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace App\Event;

use App\Entity\Voter;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class VoteCastEvent
 */
class VoteCastEvent extends Event
{

    const NAME = 'vote.cast';

    /** @var Voter */
    private $voter;

    /** @var string */
    private $privateKey;

    public function __construct(Voter $voter, $privateKey)
    {
        $this->voter      = $voter;
        $this->privateKey = $privateKey;
    }

    public function getVoter()
    {
        return $this->voter;
    }

    public function getPrivateKey()
    {
        return $this->privateKey;
    }
}
