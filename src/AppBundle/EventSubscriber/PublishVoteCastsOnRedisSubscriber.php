<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace AppBundle\EventSubscriber;

use AppBundle\Event\VoteCastEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class NotifyAdministratorsSubscriber
 * @package AppBundle\EventSubscriber
 */
class PublishVoteCastsOnRedisSubscriber implements EventSubscriberInterface
{
    public function onVoteCasted(VoteCastEvent $event)
    {
        throw new \Exception("votecasted");
    }

    public static function getSubscribedEvents()
    {
        return [
            VoteCastEvent::NAME => 'onVoteCasted',
        ];
    }

}