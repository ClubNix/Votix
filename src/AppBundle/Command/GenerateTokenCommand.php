<?php

namespace AppBundle\Command;

use Broadway\Serializer\SerializableInterface;

class GenerateTokenCommand implements SerializableInterface
{
    /** @var string */
    private $email;

    public function __construct($email)
    {
        $this->email = $email;
    }

    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param array $data
     * @return mixed The object instance
     */
    public static function deserialize(array $data)
    {
        return new GenerateTokenCommand($data['email']);
    }

    /**
     * @return array
     */
    public function serialize()
    {
        return [
            'email' => $this->email,
        ];
    }
}
