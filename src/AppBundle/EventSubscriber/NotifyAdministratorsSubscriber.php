<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace AppBundle\EventSubscriber;

use AppBundle\Event\KeysGeneratedEvent;
use AppBundle\Event\KeysVerifiedEvent;
use AppBundle\Event\VotesCountedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class NotifyAdministratorsSubscriber
 * @package AppBundle\EventSubscriber
 */
class NotifyAdministratorsSubscriber implements EventSubscriberInterface {
    public function onKeysGenerated(KeysGeneratedEvent $event) {
    }

    public function onKeysVerified(KeysVerifiedEvent $event) {
    }

    public function onVotesCounted(VotesCountedEvent $event) {
    }

    public static function getSubscribedEvents()
    {
        return [
            KeysGeneratedEvent::NAME => 'onKeysGenerated',
            KeysVerifiedEvent::NAME  => 'onKeysVerified',
            VotesCountedEvent::NAME  => 'onVotesCounted',
        ];
    }

}