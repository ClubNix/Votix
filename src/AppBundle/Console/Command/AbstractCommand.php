<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace AppBundle\Console\Command;

use Broadway\Serializer\SerializableInterface;
use Broadway\Serializer\SimpleInterfaceSerializer;
use Guzzle\Http\Client;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

/**
 * Class VotixAbstractCommand
 * @package AppBundle\Console\Command
 */
abstract class AbstractCommand extends ContainerAwareCommand
{
    /**
     * Returns true if the service id is defined.
     *
     * @param string $id The service id
     *
     * @return bool true if the service id is defined, false otherwise
     */
    protected function has($id)
    {
        return $this->getContainer()->has($id);
    }

    /**
     * Gets a container service by its id.
     *
     * @param string $id The service id
     *
     * @return object The service
     */
    protected function get($id)
    {
        return $this->getContainer()->get($id);
    }

    protected function send(SerializableInterface $command)
    {
        $serializer = new SimpleInterfaceSerializer();
        // Serialize
        $serialized = $serializer->serialize($command);

        $client = new Client();
        $serialized['payload'] += ['life' => 42]; // hack, when payload is an empty array Broadway throws an exception

        $request = $client->post('http://localhost:8000/command', null, $serialized);

        return $request->send();
    }
}