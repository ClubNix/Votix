<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace App\Controller;

use Broadway\CommandHandling\SimpleCommandBus;
use Broadway\Serializer\SimpleInterfaceSerializer;
use Monolog\Handler\BufferHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CommandController
 */
class CommandController extends Controller
{
    /**
     * @Route("/command", name="command")
     *
     * @return Response
     */
    public function commandAction()
    {
        // Setup the command handler
        $commandHandler = $this->get('votix.command_handler');

        $mem1 = fopen('php://memory', 'r+');

        $logger = new Logger('command');
        $bufferHandler = new BufferHandler(new StreamHandler($mem1));
        $logger->setHandlers([$bufferHandler]);

        $commandHandler->setLogger($logger);

        // Create a command bus and subscribe the command handler at the command bus
        $commandBus = new SimpleCommandBus();
        $commandBus->subscribe($commandHandler);
        // Create and dispatch the command!

        // Setup the simple serializer
        $serializer = new SimpleInterfaceSerializer();

        $command = $serializer->deserialize($_POST);

        $commandBus->dispatch($command);

        $bufferHandler->close();

        rewind($mem1);
        $text = stream_get_contents($mem1);

        return new Response($text);
    }
}