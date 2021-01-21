<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 *
 * @license MIT
 */
namespace App\Service;

/**
 * Class StatusService
 */
class StatusService implements StatusServiceInterface
{
    /**
     * @var int
     */
    private $start;
    /**
     * @var int
     */
    private $end;

    public function __construct(int $votixStart, int $votixEnd)
    {
        $this->start = $votixStart;
        $this->end = $votixEnd;
    }

    public function isVoteUnconfigured(): bool
    {
        return $this->getCurrentStatus() === self::UNCONFIGURED;
    }

    public function isVoteOpen(): bool
    {
        return $this->getCurrentStatus() === self::OPEN;
    }

    public function isVoteClosed(): bool
    {
        return $this->getCurrentStatus() === self::CLOSED;
    }

    public function getCurrentStatus(): string
    {
        if ($this->start === 0) {
            return self::UNCONFIGURED;
        }

        $now = time();

        if($now < $this->start) {
            return self::WAITING;
        }

        if($now < $this->end) {
            return self::OPEN;
        }

        return self::CLOSED;
    }

    public function getCurrentStatusMessage(): string
    {
        switch ($this->getCurrentStatus()) {
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