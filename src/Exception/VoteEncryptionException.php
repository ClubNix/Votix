<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace App\Exception;

use Exception;

/**
 * Class VoteEncryptionException
 */
class VoteEncryptionException extends \DomainException {

    public function __construct($message, $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
