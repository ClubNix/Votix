<?php

namespace App\Command;

use Broadway\Serializer\SerializableInterface;

/**
 * Command object.
 */
class DropVotersCommand implements SerializableInterface
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
        return new DropVotersCommand();
    }

    /**
     * @return array
     */
    public function serialize()
    {
        return [];
    }
}
