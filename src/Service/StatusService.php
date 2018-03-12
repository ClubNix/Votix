<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace App\Service;

/**
 * Class StatusService
 */
class StatusService implements StatusServiceInterface
{
    private $start;
    private $end;

    public function __construct($start, $end)
    {
        $this->start = $start;
        $this->end   = $end;
    }

    public function isVoteOpen() {
        return $this->getCurrentStatus() == self::OPEN;
    }

    public function isVoteClosed() {
        return $this->getCurrentStatus() == self::CLOSED;
    }

    public function getCurrentStatus() {
        $now = time();

        if($now < $this->start) {
            return self::WAITING;
        }

        if($now < $this->end) {
            return self::OPEN;
        }

        return self::CLOSED;
    }

    public function getCurrentStatusMessage()
    {
        switch($this->getCurrentStatus()) {
            case self::OPEN:
                return 'Les votes sont ouverts jusqu\'à 17h13.';
            case self::WAITING:
                return 'Les votes seront ouverts le 18 mars de 00h00 à 17h13.';
            case self::CLOSED:
                return 'Les votes sont terminés. Le résultat sera annoncé par le BDE.';
        }

        return 'Statut inconnu';
    }

}