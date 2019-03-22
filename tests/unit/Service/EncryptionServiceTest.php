<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace App\Tests\Unit\Service;

use App\Service\EncryptionService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class EncryptionServiceTest
 * @package Tests\AppBundle\Entity
 */
class EncryptionServiceTest extends WebTestCase
{
    private $goodKeysDirectory = __DIR__ . '/../../_data/fixtures';
    private $secretKey         = 'testsecret';
    private $fakevote          = '42';

    public function testIsArmedWithArmedFilesystem()
    {
        $eventDispatcher = $this->getEventDispatcherMock();
        $filesystem      = $this->getArmedFilesystemMock();

        $encryptionService = new EncryptionService($eventDispatcher, $this->goodKeysDirectory, $this->secretKey, $filesystem);

        $this->assertTrue($encryptionService->isArmed());

        return $encryptionService;
    }

    public function testIsArmedWithDisarmedFilesystem()
    {
        $eventDispatcher = $this->getEventDispatcherMock();
        $filesystem      = $this->getDisarmedFilesystemMock();

        $encryptionService = new EncryptionService($eventDispatcher, $this->goodKeysDirectory, $this->secretKey, $filesystem);

        $this->assertFalse($encryptionService->isArmed());
    }

    public function testVerifyKeyWithInvalidKeyOrPassphrase()
    {
        $eventDispatcher = $this->getEventDispatcherMock();
        $filesystem      = $this->getArmedFilesystemMock();

        $encryptionService = new EncryptionService($eventDispatcher, $this->goodKeysDirectory, $this->secretKey, $filesystem);
        [$success] = $encryptionService->verifyKey('tapz');
        $this->assertFalse($success);

        $encryptionService = new EncryptionService($eventDispatcher, $this->goodKeysDirectory, 'bad key', $filesystem);
        [$success] = $encryptionService->verifyKey($this->getPrivateKeyFixture());
        $this->assertFalse($success);
    }

    /**
     * @depends testIsArmedWithArmedFilesystem
     *
     * @param EncryptionService $encryptionService
     */
    public function testVerifyKeyWithValidKeyAndPassphrase($encryptionService)
    {
        [$success] = $encryptionService->verifyKey($this->getPrivateKeyFixture());
        $this->assertTrue($success);
    }

    /**
     * @depends testIsArmedWithArmedFilesystem
     *
     * @param EncryptionService $encryptionService
     * @return string
     */
    public function testEncryption($encryptionService)
    {
        $encrypted = $encryptionService->encryptVote($this->fakevote);

        $this->assertNotNull($encrypted);
        $this->assertNotEquals($this->fakevote, $encrypted);

        return $encrypted;
    }

    /**
     * @depends testIsArmedWithArmedFilesystem
     * @depends testEncryption
     *
     * @param EncryptionService $encryptionService
     * @param $encrypted
     */
    public function testDecryption($encryptionService, $encrypted)
    {
        $decrypted = $encryptionService->decryptVote($encrypted, $this->getPrivateKeyFixture());

        $this->assertNotNull($decrypted);
        $this->assertNotEquals($encrypted, $decrypted);

        $this->assertEquals($this->fakevote, $decrypted);
    }

    public function testGeneratekeys()
    {
        $eventDispatcher = $this->getEventDispatcherMock();
        $filesystem      = $this->getArmedFilesystemMock();

        $filesystem
            ->expects($this->exactly(2))
            ->method('dumpFile');

        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch');

        $encryptionService = new EncryptionService($eventDispatcher, $this->goodKeysDirectory, $this->secretKey, $filesystem);

        $encryptionService->generateKeys();
    }

    private function getEventDispatcherMock()
    {
        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $this
            ->getMockBuilder(EventDispatcher::class)
            ->getMock();

        return $eventDispatcher;
    }

    private function getPrivateKeyFixture()
    {
        return file_get_contents($this->goodKeysDirectory . '/votix_secret_key.txt');
    }

    private function getArmedFilesystemMock()
    {
        $filesystem = $this
            ->getMockBuilder(Filesystem::class)
            ->getMock();

        $filesystem->method('exists')
            ->with($this->goodKeysDirectory . '/public_key.pub')
            ->willReturn(true);

        /** @var Filesystem $filesystem */
        return $filesystem;
    }

    private function getDisarmedFilesystemMock()
    {
        $filesystem = $this
            ->getMockBuilder(Filesystem::class)
            ->getMock();

        $filesystem->method('exists')
            ->with($this->goodKeysDirectory . '/public_key.pub')
            ->willReturn(false);

        /** @var Filesystem $filesystem */
        return $filesystem;
    }
}