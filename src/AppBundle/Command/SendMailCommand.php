<?php

namespace AppBundle\Command;

use Broadway\Serializer\SerializableInterface;

/**
 * Command object.
 */
class SendMailCommand implements SerializableInterface
{
    /** @var string */
    private $template;

    /** @var string|null */
    private $receiver;

    /** @var boolean */
    private $dryRun;

    public function __construct($template, $receiver = null, $dryRun = true)
    {
        $this->template = $template;
        $this->receiver = $receiver;
        $this->dryRun   = $dryRun;
    }

    public function getTemplate()
    {
        return $this->template;
    }

    public function getReceiver()
    {
        return $this->receiver;
    }

    public function isDryRun() {
        return $this->dryRun;
    }

    /**
     * @param array $data
     * @return mixed The object instance
     */
    public static function deserialize(array $data)
    {
        return new SendMailCommand($data['template'], $data['receiver']);
    }

    /**
     * @return array
     */
    public function serialize()
    {
        return [
            'template' => $this->template,
            'receiver' => $this->receiver,
        ];
    }
}
