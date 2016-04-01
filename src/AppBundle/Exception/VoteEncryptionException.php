<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace AppBundle\Exception;

use Exception;

/**
 * Class VoteEncryptionException
 * @package AppBundle\Exception
 */
class VoteEncryptionException extends \DomainException {

    public function __construct($message, $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

}