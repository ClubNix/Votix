<?php

namespace AppBundle\Command;

use Broadway\Serializer\SerializableInterface;

/**
 * Command object.
 */
class CheckStatusCommand implements SerializableInterface
{
    public function __construct()
    {
    }

    /**
     * @param array $data
     * @return mixed The object instance
     */
    public static function deserialize(array $data)
    {
        return new CheckStatusCommand();
    }

    /**
     * @return array
     */
    public function serialize()
    {
        return [];
    }
}
