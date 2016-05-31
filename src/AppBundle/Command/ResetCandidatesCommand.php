<?php

namespace AppBundle\Command;

use Broadway\Serializer\SerializableInterface;

/**
 * Command object.
 */
class ResetCandidatesCommand implements SerializableInterface
{
    private $candidates;

    public function __construct(array $candidates)
    {
        $this->candidates = $candidates;
    }

    public function getCandidates()
    {
        return $this->candidates;
    }

    /**
     * @param array $data
     * @return mixed The object instance
     */
    public static function deserialize(array $data)
    {
        return new ResetCandidatesCommand($data['candidates']);
    }

    /**
     * @return array
     */
    public function serialize()
    {
        return [
            'cadidates' => $this->candidates,
        ];
    }
}
