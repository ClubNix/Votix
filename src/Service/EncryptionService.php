<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 *
 * @license MIT
 */
namespace App\Service;

use App\Event\KeysGeneratedEvent;
use App\Event\KeysVerifiedEvent;
use App\Exception\VoteEncryptionException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Zend\Crypt\Exception\ExceptionInterface as ZendCryptExceptionInterface;
use Zend\Crypt\PublicKey\Rsa;
use Zend\Crypt\PublicKey\RsaOptions;

/**
 * Class EncryptionService
 */
class EncryptionService implements EncryptionServiceInterface
{
    /**
     * @var Number of bits for the RSA key pair
     */
    private const PRIVATE_KEY_BITS = 2048;

    /**
     * @var string RSA passphrase
     */
    private $secretKey;

    /**
     * @var string Path to directory where to generate keys and keep the public key
     */
    private $keysDirectory;

    /**
     * @var EventDispatcherInterface Event dispatcher to dispatch encryption events
     */
    private $eventDispatcher;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /***
     * EncryptionService constructor.
     *
     * @param EventDispatcherInterface $eventDispatcher
     * @param string $keysDirectory
     * @param string $secretKey
     * @param Filesystem $filesystem
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        $keysDirectory,
        $secretKey,
        Filesystem $filesystem
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->keysDirectory   = $keysDirectory;
        $this->secretKey       = $secretKey;
        $this->filesystem      = $filesystem;
    }

    /**
     * Check if the keys have already been generated
     *
     * @return bool true if the application public key is detected
     */
    public function isArmed(): bool
    {
        return $this->filesystem->exists($this->keysDirectory . '/public_key.pub');
    }

    /**
     * Checks if the passed private key can decrypt an encrypted text from the stored
     * public key.
     *
     * @param string $key Private key to test with the stored public key.
     *
     * @return array First element boolean $success, second element string message.
     */
    public function verifyKey($key): array
    {
        $success = false;
        $message = 'ERROR';

        $text = 'This is the message to encrypt';

        try {
            $rsa     = $this->getRsa($key);
            $encrypt = $rsa->encrypt($text);
            $decrypt = $rsa->decrypt($encrypt);

            if ($text === $decrypt) {
                $message = 'Encryption and decryption performed successfully!';
                $success = true;
            }
        } catch(ZendCryptExceptionInterface $e) {
            $message = $e->getMessage();
        }

        $this->eventDispatcher->dispatch(KeysVerifiedEvent::NAME, new KeysVerifiedEvent($success, $message));

        return [$success, $message];
    }

    /**
     * Decrypt a vote using a private key.
     *
     * @param string $vote Ballot to decrypt.
     * @param string $key Private key to use to decrypt the ballot.
     *
     * @return string Decrypted vote.
     */
    public function decryptVote($vote, $key): string
    {
        try {
            $decrypted = $this->getRsa($key)->decrypt($vote);
        } catch(ZendCryptExceptionInterface $exception) {
            throw new VoteEncryptionException('Vote cannot be decrypted', $exception->getCode(), $exception);
        }

        return $decrypted;
    }

    /**
     * Encrypt a vote using the stored public key.
     *
     * @param string $vote Vote to encrypt.
     *
     * @return string Encrypted vote.
     *
     * @throws VoteEncryptionException If the vote cannot be encrypted
     */
    public function encryptVote(string $vote): string
    {
        try {
            $encrypted = $this->getRsa()->encrypt($vote);
        } catch(ZendCryptExceptionInterface $exception) {
            throw new VoteEncryptionException('Vote cannot be encrypted', $exception->getCode(), $exception);
        }

        return $encrypted;
    }

    /**
     * Generate a RSA key pair for encryption and store it in the filesystem.
     * Caller must remove the private key from the filesystem.
     * Dispatches a App\Event\KeysGeneratedEvent event after generation.
     *
     * @see KeysGeneratedEvent
     *
     * @see getGeneratedKeyFilePath
     */
    public function generateKeys(): void
    {
        $rsaOptions = new RsaOptions([
            'pass_phrase' => $this->secretKey,
        ]);

        $rsaOptions->generateKeys(['private_key_bits' => self::PRIVATE_KEY_BITS]);

        $this->filesystem->dumpFile($this->keysDirectory . '/private_key.pem', $rsaOptions->getPrivateKey());
        $this->filesystem->dumpFile($this->keysDirectory . '/public_key.pub',  $rsaOptions->getPublicKey());

        $this->eventDispatcher->dispatch(KeysGeneratedEvent::NAME, new KeysGeneratedEvent());
    }

    public function encryptSignature($signature): array
    {
        $rsaOptions = new RsaOptions([
            'pass_phrase' => $this->secretKey,
        ]);

        $rsaOptions->generateKeys(['private_key_bits' => self::PRIVATE_KEY_BITS]);

        $rsa = new Rsa($rsaOptions);

        return [
            'encrypted'   => $rsa->encrypt($signature),
            'private_key' => $rsaOptions->getPrivateKey(),
        ];
    }

    /**
     * Get the private key previously generated.
     *
     * @return string Private key path in the filesystem.
     */
    public function getGeneratedKeyFilePath(): string
    {
        return $this->keysDirectory . '/private_key.pem';
    }

    /**
     * Returns a configured Rsa instance.
     *
     * @param string|null $key Private key to use. null if the Rsa instance will only encrypt.
     *
     * @return Rsa
     */
    private function getRsa($key = null): Rsa
    {
        $options = [
            'public_key'    => $this->keysDirectory . '/public_key.pub',
            'pass_phrase'   => $this->secretKey,
            'binary_output' => false
        ];

        if(null !== $key) {
            $options['private_key'] = $key;
        }

        return Rsa::factory($options);
    }
}